<?php

namespace DoubleThreeDigital\SimpleCommerce\Gateways\Extend;

class GatewayCharge
{
    public $id;
    public $date;
    public $metadata;

    public function __construct($id, $date, $metadata)
    {
        $this->id = $id;
        $this->date = $date;
        $this->metadata = $metadata;
    }
}