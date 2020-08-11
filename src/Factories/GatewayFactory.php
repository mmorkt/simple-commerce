<?php

namespace DoubleThreeDigital\SimpleCommerce\Factories;

use DoubleThreeDigital\SimpleCommerce\Contracts\Gateway;

class GatewayFactory
{
    public function make($gateway): Gateway
    {
        $instance = new $gateway();

        return $instance;
    }
}