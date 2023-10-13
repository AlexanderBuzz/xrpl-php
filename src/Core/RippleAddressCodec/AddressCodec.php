<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Core\RippleAddressCodec;

use XRPL_PHP\Core\Buffer;

class AddressCodec extends CodecWithXrpAlphabet
{
    public const PREFIX_BYTES = [
        'main' => [0x05, 0x44], // 5, 68
        'test' => [0x04, 0x93], // 4, 147
    ];

    public const MAX_32_BIT_UNSIGNED_INT = 4294967295;

    public function __construct()
    {
        parent::__construct(Utils::XRPL_ALPHABET);
    }

    /**
     * @psalm-param 4294967295 $tag
     */
    public function classicAddressToXAddress(string $classicAddress, int $tag, bool $isTestnet = false): string
    {
        $accountBuffer = $this->decodeAccountId($classicAddress);
        return $this->encodeXAddress($accountBuffer, $tag, $isTestnet);
    }

    public function encodeXAddress(Buffer $accountId, $tag, bool $test = false): string
    {
        $flag = $tag === false ? 0 : ($tag <= self::MAX_32_BIT_UNSIGNED_INT ? 1 : 2);
        if ($flag === 2) {
            throw new \Exception('Invalid tag');
        }
        if ($tag === false) {
            $tag = 0;
        }

        $bytes = array_merge($test ? self::PREFIX_BYTES['test'] : self::PREFIX_BYTES['main']);
        $bytes = array_merge($bytes, $accountId->toArray());
        $bytes = array_merge($bytes, [
            $flag,
            $tag & 0xff,
            ($tag >> 8) & 0xff,
            ($tag >> 16) & 0xff,
            ($tag >> 24) & 0xff,
            0, 0, 0, 0
        ]);

        $hex = array_map(function ($item) {
            return sprintf('%02X', $item);
        }, $bytes);

        return $this->encodeChecked(Buffer::from(join($hex)));
    }

    public function xAddressToClassicAddress(string $xAddress): array
    {
        list($accountId, $tag, $test) = array_values($this->decodeXAddress($xAddress));
        $classicAddress = $this->encodeAccountID($accountId);
        return [
            'classicAddress' => $classicAddress,
            'tag' => $tag,
            'test' => $test,
        ];
    }

    public function decodeXAddress(string $xAddress): array
    {
        $decoded = $this->decodeChecked($xAddress);
        $test = $this->isBufferForTestAddress($decoded);
        $accountId = $decoded->slice(2, 22);
        $tag = $this->tagFromBuffer($decoded);
        return [
            'accountId' => $accountId,
            'tag' => $tag,
            'test' => $test,
        ];
    }

    public function isValidXAddress(string $xAddress): bool
    {
        try {
            $this->decodeXAddress($xAddress);
        } catch (\Throwable $e) {
            return false;
        }
        return true;
    }

    private function isBufferForTestAddress(Buffer $buffer): bool
    {
        $decodedPrefix = $buffer->slice(0, 2)->toArray();
        if (self::PREFIX_BYTES['main'] === $decodedPrefix) {
            return false;
        } else if (self::PREFIX_BYTES['test'] === $decodedPrefix) {
            return true;
        }

        throw new \Exception('Invalid X-address: bad prefix');
    }

    private function tagFromBuffer(Buffer $buffer): int|false
    {
        $buf = $buffer->toArray();
        $flag = $buf[22];
        if ($flag >= 2) {
            throw new \Exception('Unsupported X-address');
        }

        if ($flag === 1) {
            // Little-endian to big-endian
            return $buf[23] + $buf[24] * 0x100 + $buf[25] * 0x10000 + $buf[26] * 0x1000000;
        }

        if ($flag === 0) {
            throw new \Exception('flag must be zero to indicate no tag');
        }

        if ('0000000000000000' !== $buffer->slice(23, 23 + 8)->toString()) {
            throw new \Exception('remaining bytes must be zero');
        }

        return false;
    }
}