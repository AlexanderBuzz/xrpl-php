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

class CodecWithXrpAlphabet extends Codec
{
    public const ACCOUNT_ID = 0; // Account address (20 bytes)

    public const ACCOUNT_PUBLIC_KEY = 0x23; // Account public key (33 bytes)

    public const FAMILY_SEED = 0x21; // 33; Seed value (for secret keys) (16 bytes)

    public const NODE_PUBLIC = 0x1c; // 28; Validation public key (33 bytes)

    public const ED25519_SEED = [0x01, 0xe1, 0x4b]; // [1, 225, 75]

    /**
     * @param string $alphabet
     */
    public function __construct(string $alphabet)
    {
        parent::__construct(Utils::XRPL_ALPHABET);
    }

    /**
     * @param Buffer $entropy
     * @param string $type
     * @return string
     * @throws \Exception
     */
    public function encodeSeed(Buffer $entropy, string $type): string
    {
        if ($entropy->getLength() !== 16) {
            throw new \Exception('entropy must have length 16');
        }

        $options = [
            'expectedLength' => 16,
            // for secp256k1, use `FAMILY_SEED`
            'versions' => $type === 'ed25519' ? self::ED25519_SEED : [self::FAMILY_SEED],
        ];

        return $this->encode($entropy, $options);
    }

    /**
     * @param string $seed
     * @param array $options
     * @return array
     * @throws \Exception
     */
    public function decodeSeed(string $seed, array $options = []): array
    {
        $options = array_replace([
            'versionTypes' => ['ed25519', 'secp256k1'],
            "versions" => [self::ED25519_SEED, self::FAMILY_SEED],
            "expectedLength" => 16,
        ], $options);

        return $this->decode($seed, $options);
    }

    /**
     * @param Buffer $bytes
     * @return string
     */
    public function encodeAccountId(Buffer $bytes): string
    {
        $options = [
            'versions' => [self::ACCOUNT_ID],
            'expectedLength' => 20
        ];
        return $this->encode($bytes, $options);
    }

    /**
     * @param string $accountId
     * @return Buffer
     * @throws \Exception
     */
    public function decodeAccountId(string $accountId): Buffer
    {
        $options = [
            'versions' => [self::ACCOUNT_ID],
            'expectedLength' => 20
        ];
        return Buffer::from($this->decode($accountId, $options)['bytes']);
    }

    /**
     * @param Buffer $bytes
     * @return string
     */
    public function encodeNodePublic(Buffer $bytes): string
    {
        $options = [
            'versions' => [self::NODE_PUBLIC],
            'expectedLength' => 33
        ];
        return $this->encode($bytes, $options);
    }

    /**
     * @param string $base58string
     * @return Buffer
     * @throws \Exception
     */
    public function decodeNodePublic(string $base58string): Buffer
    {
        $options = [
            'versions' => [self::NODE_PUBLIC],
            'expectedLength' => 33
        ];
        return Buffer::from($this->decode($base58string, $options)['bytes']);
    }

    /**
     * @param Buffer $bytes
     * @return string
     */
    public function encodeAccountPublic(Buffer $bytes): string
    {
        $options = [
            'versions' => [self::ACCOUNT_PUBLIC_KEY],
            'expectedLength' => 33
        ];
        return $this->encode($bytes, $options);
    }

    /**
     * @param string $base58string
     * @return Buffer
     * @throws \Exception
     */
    public function decodeAccountPublic(string $base58string): Buffer
    {
        $options = [
            'versions' => [self::ACCOUNT_PUBLIC_KEY],
            'expectedLength' => 33
        ];
        return Buffer::from($this->decode($base58string, $options)['bytes']);
    }

    /**
     * @param string $address
     * @return bool
     */
    public function isValidClassicAddress(string $address): bool
    {
        try {
            $this->decodeAccountId($address);
        } catch (\Throwable $e) {
            return false;
        }
        return true;
    }
}
