<?php declare(strict_types = 1);

namespace XRPL_PHP\RippleAddressCodec;

use Lessmore92\Buffer\Buffer;
use phpDocumentor\Reflection\Types\Callable_;

class CodecWithXrpAlphabet extends Codec
{
    public const ACCOUNT_ID = 0; // Account address (20 bytes)

    public const ACCOUNT_PUBLIC_KEY = 0x23; // Account public key (33 bytes)

    public const FAMILY_SEED = 0x21; // 33; Seed value (for secret keys) (16 bytes)

    public const NODE_PUBLIC = 0x1c; // 28; Validation public key (33 bytes)

    public const ED25519_SEED = [0x01, 0xe1, 0x4b]; // [1, 225, 75]

    public function __construct(string $alphabet)
    {
        parent::__construct(Utils::XRPL_ALPHABET);
    }

    public function encodeSeed(Buffer $entropy, string $type): string
    {
        if ($entropy->getSize() !== 16)
        {
            throw new \Exception('entropy must have length 16');
        }

        $options = [
            'expectedLength' => 16,
            // for secp256k1, use `FAMILY_SEED`
            'versions'       => $type === 'ed25519' ? self::ED25519_SEED : [self::FAMILY_SEED],
        ];

        return $this->encode($entropy, $options);
    }

    public function decodeSeed(string $seed, array $options = []): array
    {
        $options = array_replace([
            'versionTypes'   => ['ed25519', 'secp256k1'],
            "versions"       => [self::ED25519_SEED, self::FAMILY_SEED],
            "expectedLength" => 16,
        ], $options);

        return $this->decode($seed, $options);
    }

    public function encodeAccountId(Buffer $bytes): string
    {
        $options = [
            'versions' => [self::ACCOUNT_ID],
            'expectedLength' => 20
        ];
        return $this->encode($bytes, $options);
    }

    public function decodeAccountId(string $accountId): Buffer
    {
        $options = [
            'versions' => [self::ACCOUNT_ID],
            'expectedLength' => 20
        ];
        return $this->decode($accountId, $options)['bytes'];
    }

    public function decodeNodePublic(string $base58string): Buffer
    {
        $options = [
            'versions' => [self::NODE_PUBLIC],
            'expectedLength' => 33
        ];
        return $this->decode($base58string, $options)['bytes'];
    }

    public function encodeNodePublic(Buffer $bytes): string
    {
        $options = [
            'versions' => [self::NODE_PUBLIC],
            'expectedLength' => 33
        ];
        return $this->encode($bytes, $options);
    }

    public function decodeAccountPublic (string $base58string): Buffer
    {
        $options = [
            'versions' => [self::ACCOUNT_PUBLIC_KEY],
            'expectedLength' => 33
        ];
        return $this->decode($base58string, $options)['bytes'];
    }

    public function encodeAccountPublic(Buffer $bytes): string
    {
        $options = [
            'versions' => [self::ACCOUNT_PUBLIC_KEY],
            'expectedLength' => 33
        ];
        return $this->encode($bytes, $options);
    }

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