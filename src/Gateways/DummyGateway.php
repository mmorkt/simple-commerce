<?php

namespace DoubleThreeDigital\SimpleCommerce\Gateways;

use DoubleThreeDigital\SimpleCommerce\Contracts\Gateway;
use DoubleThreeDigital\SimpleCommerce\Gateways\Extend\GatewayCharge;

class DummyGateway implements Gateway
{
    public function name(): string
    {
        return 'Dummy';
    }

    public function prepare(array $data): array
    {
        return [];
    }

    public function purchase(array $data, $request): GatewayCharge
    {
        // if ($data['card_number'] === '1212 1212 1212 1212') return null;

        return $this->getCharge([]);
    }

    public function purchaseRules(): array
    {
        return [
            'card_number'   => 'required|string',
            'expiry_month'  => 'required',
            'expiry_year'   => 'required',
            'cvc'           => 'required',
        ];
    }

    public function getCharge(array $data): GatewayCharge
    {
        return new GatewayCharge('123456789abcdefg', (string) now()->subDays(14), [
            'last_four' => '4242',
            'last_four' => '4242',
        ]);
    }

    public function refundCharge(array $data): array
    {
        return [];
    }
}
