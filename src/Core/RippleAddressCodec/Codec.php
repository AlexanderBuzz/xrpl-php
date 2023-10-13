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

use Exception;
use XRPL_PHP\Core\Buffer;

class Codec
{
    private string $alphabet;

    private BaseX $baseCodec;

    public function __construct(string $alphabet)
    {
        $this->alphabet = $alphabet;
        $this->baseCodec = new BaseX($alphabet);
    }

    public function encode(Buffer $bytes, array $options): string
    {
        return $this->encodeVersioned($bytes, $options['versions'], $options['expectedLength']);
    }

    public function decode(string $base58String, array $options): array
    {
        $withoutSum = $this->decodeChecked($base58String);

        $defaultOptions = [
            'versionTypes' => ['ed25519', 'secp256k1'],
            'version' => [[1, 225, 75], 33],
            'expectedLength' => 16
        ];

        $options = array_replace($defaultOptions, $options);

        $versions = $options['versions'];
        $types = $options['versionTypes'];


        if (count($options['versions']) > 1 && !$options['expectedLength']) {
            throw new Exception('expectedLength is required because there are >= 2 possible versions');
        }

        $versionLengthGuess = is_numeric($versions[0]) ? 1 : sizeof($versions[0]);
        $payloadLength = $options['expectedLength'] ?: $withoutSum->getLength() - $versionLengthGuess;

        $versionBytes = $withoutSum->slice(0, -$payloadLength)->toArray();
        $payload = $withoutSum->slice(-$payloadLength)->toArray();

        foreach ($versions as $key => $version) {
            $version = is_array($versions[$key]) ? $versions[$key] : [$versions[$key]];
            if ($version === $versionBytes) {
                return [
                    'version' => $version,
                    'bytes' => $payload,
                    'type' => $types ? $types[$key] : null,
                ];
            }
        }
    }

    public function encodeChecked(Buffer $bytes): string
    {
        $check = $this->sha256($this->sha256($bytes))->slice(0,4);
        return $this->encodeRaw(Buffer::concat([$bytes->toArray(), $check->toArray()]));
    }

    public function decodeChecked(string $base58string): Buffer
    {
        $buffer = $this->decodeRaw($base58string);

        if ($buffer->getLength() < 5) {
            throw new Exception('invalid_input_size: decoded data must have length >= 5');
        }

        if (!$this->verifyCheckSum($buffer)) {
            throw new Exception('Checksum does not validate');
        }

        return $buffer->slice(0, -4);
    }

    private function encodeVersioned(Buffer $bytes, array $versions, int $expectedLength): string
    {
        if ($bytes->getLength() !== $expectedLength) {
            throw new Exception('unexpected_payload_length: bytes.length does not match expectedLength. Ensure that the bytes are a Buffer.');
        }

        $bytes->prependBuffer(Buffer::from($versions));

        return $this->encodeChecked($bytes);
    }

    private function encodeRaw(Buffer $bytes): string
    {
        return $this->baseCodec->encode($bytes);
    }

    private function decodeRaw(string $base58string): Buffer
    {
        return $this->baseCodec->decode($base58string);
    }

    private function sha256(Buffer $bytes): Buffer
    {
        $binaryValue = hex2bin($bytes->toString());
        $binaryHash = hash('sha256', $binaryValue, true);
        $hexValue = bin2hex($binaryHash);
        return Buffer::from($hexValue);
    }

    private function verifyCheckSum(Buffer $bytes): bool
    {
        $computed = $this->sha256($this->sha256($bytes->slice(0,-4)))->slice(0,4);
        $checksum = $bytes->slice(-4);
        return $computed->toString() === $checksum->toString(); //TODO: Perhaps make Buffer comparable
    }
}
