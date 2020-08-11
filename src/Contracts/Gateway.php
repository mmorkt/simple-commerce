<?php

namespace DoubleThreeDigital\SimpleCommerce\Contracts;

use DoubleThreeDigital\SimpleCommerce\Gateways\Extend\GatewayCharge;

interface Gateway
{
    public function name(): string;

    public function prepare(array $data): array;

    public function purchase(array $data, $request): GatewayCharge;

    public function purchaseRules(): array;

    public function getCharge(array $data): GatewayCharge;

    public function refundCharge(array $data): array;
}
