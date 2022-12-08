<?php

namespace XRPL_PHP\Models\Transaction\TransactionTypes;

class Address
{
    public function __toString(): string
    {
        return $this->value;
    }
}