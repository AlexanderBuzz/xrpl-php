<?php

namespace XRPL_PHP\Models\Transactions;

use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\MathUtilities;

class Hash256
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getHash(): string
    {
        $hashBuffer = MathUtilities::sha512Half(Buffer::from($this->value, 'hex'));
        return $hashBuffer->toString();
    }

    public function toValue(): string
    {
        return $this->getHash();
    }
}