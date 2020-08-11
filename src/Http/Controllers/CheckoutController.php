<?php

namespace DoubleThreeDigital\SimpleCommerce\Http\Controllers;

use DoubleThreeDigital\SimpleCommerce\Contracts\CartRepository;
use DoubleThreeDigital\SimpleCommerce\Events\PostCheckout;
use DoubleThreeDigital\SimpleCommerce\Events\Precheckout;
use DoubleThreeDigital\SimpleCommerce\Exceptions\CustomerNotFound;
use DoubleThreeDigital\SimpleCommerce\Facades\Coupon;
use DoubleThreeDigital\SimpleCommerce\Facades\Customer;
use DoubleThreeDigital\SimpleCommerce\Factories\GatewayFactory;
use DoubleThreeDigital\SimpleCommerce\Gateways\Extend\GatewayCharge;
use DoubleThreeDigital\SimpleCommerce\Http\Requests\Checkout\StoreRequest;
use DoubleThreeDigital\SimpleCommerce\SessionCart;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Session;

class CheckoutController extends BaseActionController
{
    use SessionCart;

    protected CartRepository $cart;
    protected StoreRequest $request;
    protected array $excludedKeys = ['_token', '_params', '_redirect'];

    public function store(StoreRequest $request)
    {
        $this->cart = $this->getSessionCart();
        $this->request = $request;

        $this
            ->handleValidation()
            ->attachCustomer()
            ->processPayment()
            ->redeemCoupon()
            ->processOrderFields()
            ->completeOrder();

        return $this->withSuccess($request);
    }

    protected function handleValidation()
    {
        $gateway = (new GatewayFactory)->make($this->request->get('gateway'));

        $this->request->validate($gateway->purchaseRules());
        // $this->request->validate($this->cart->entry()->blueprint()->fields()->validator()->rules());
        $this->request->validate([
            'name' => ['sometimes', 'string'],
            'email' => ['sometimes', 'email'],
        ]);

        return $this;
    }

    protected function attachCustomer()
    {
        if ($this->request->has('name') && $this->request->has('email')) {
            try {
                $customer = Customer::findByEmail($this->request->get('email'));
            } catch (CustomerNotFound $e) {
                $customer = Customer::make()
                    ->data([
                        'name'  => $this->request->get('name'),
                        'email' => $this->request->get('email'),
                    ])
                    ->save();
            }

            $this->cart->update([
                'customer' => $customer->id,
            ]);

            $this->excludedKeys[] = 'name';
            $this->excludedKeys[] = 'email';
        }

        return $this;
    }

    protected function processPayment()
    {
        event(new PreCheckout($this->requestData()));

        $gateway = (new GatewayFactory)->make($this->request->get('gateway'));
        $purchase = $gateway->purchase($this->requestData(), $this->request);

        if ($purchase instanceof GatewayCharge) {
            $this->cart->update([
                'gateway' => $this->request->get('gateway'),
                'gateway_data' => $purchase->toArray(),
            ]);
        }

        // TODO: if instance of GatewayError, return with error bag

        $this->excludedKeys[] = 'gateway';
        
        foreach ($gateway->purchaseRules() as $key => $value) {
            $this->excludedKeys[] = $key;
        }

        return $this;
    }

    protected function redeemCoupon()
    {
        if (isset($this->cart->data['coupon'])) {
            $coupon = Coupon::find($this->cart->data['coupon']);

            $coupon->update([
                'redeemed' => $coupon->data['redeemed']++,
            ]);
        }

        return $this;
    }

    protected function processOrderFields()
    {
        $data = $this->requestData();

        foreach ($data as $key => $value) {
            if ($value === 'on') {
                $value = true;
            }

            if ($value === 'off') {
                $value = false;
            }

            $data[$key] = $value;
        }

        $this->cart->update($data);

        return $this;
    }

    protected function completeOrder()
    {
        $this->cart->markAsCompleted();
        
        event(new PostCheckout($this->requestData(), $this->excludedKeys));

        $this->forgetSessionCart();

        return $this;
    }

    protected function requestData()
    {
        return Arr::except($this->request->all(), $this->excludedKeys);
    }
}
