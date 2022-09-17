<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Common;

class Amount
{
    public function __construct(
        public string $currency,
        public string $issuer,
        public string $value
    ) {}

    public function toValue(): array
    {
        return [
            'currency' => $this->currency,
            'issuer' => $this->issuer,
            'value' => $this->value
        ];
    }
}