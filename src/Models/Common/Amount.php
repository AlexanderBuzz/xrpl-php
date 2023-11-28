<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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