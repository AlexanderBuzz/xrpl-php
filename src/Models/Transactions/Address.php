<?php

namespace XRPL_PHP\Models\Transactions;

class Address
{
    public function __toString(): string
    {
        return $this->value;
    }
}