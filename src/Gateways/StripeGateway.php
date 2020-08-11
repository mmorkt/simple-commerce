<?php

namespace DoubleThreeDigital\SimpleCommerce\Gateways;

use DoubleThreeDigital\SimpleCommerce\Contracts\Gateway;
use DoubleThreeDigital\SimpleCommerce\Exceptions\StripeSecretMissing;
use DoubleThreeDigital\SimpleCommerce\Facades\Currency;
use DoubleThreeDigital\SimpleCommerce\Gateways\Extend\GatewayCharge;
use Statamic\Facades\Site;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Stripe;

class StripeGateway implements Gateway
{
    public function name(): string
    {
        return 'Stripe';
    }

    public function prepare(array $data): array
    {
        $this->setUpWithStripe();

        $intent = PaymentIntent::create([
            'amount'   => $data['grand_total'],
            'currency' => Currency::get(Site::current())['code'],
        ]);

        return [
            'intent'        => $intent->id,
            'client_secret' => $intent->client_secret,
        ];
    }

    public function purchase(array $data, $request): GatewayCharge
    {
        $this->setUpWithStripe();

        $paymentMethod = PaymentMethod::retrieve($data['payment_method']);

        return new GatewayCharge($paymentMethod->id, $paymentMethod->created, [
            'card' => [
                'brand' => $paymentMethod->card->brand,
                'country' => $paymentMethod->card->country,
                'expiry_month' => $paymentMethod->card->exp_month,
                'expiry_year' => $paymentMethod->card->exp_year,
                'last_four' => $paymentMethod->card->last4,
            ],
            'stripe_metadata' => $paymentMethod->metadata,
        ]);
    }

    public function purchaseRules(): array
    {
        return [
            'payment_method' => 'required|string',
        ];
    }

    public function getCharge(array $data): GatewayCharge
    {
        // TODO: finish this function

        return new GatewayCharge(1, 2, 3);
    }

    public function refundCharge(array $data): array
    {
        // TODO: finish this function

        return [];
    }

    protected function setUpWithStripe()
    {
        if (!env('STRIPE_SECRET')) {
            throw new StripeSecretMissing(__('simple-commerce::gateways.stripe.stripe_secret_missing'));
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));
    }
}
