<?php declare(strict_types = 1);

namespace XRPL_PHP\RippleAddressCodec;

use Exception;
use Lessmore92\Buffer\Buffer;

class Codec
{
    private string $alphabet;

    private BaseX $baseCodec;

    public function __construct(string $alphabet)
    {
        $this->alphabet = $alphabet;
        $this->baseCodec = new BaseX($alphabet);
    }

    public function encode(Buffer $bytes, array $versions, int $expectedLength): string
    {
        return $this->encodeVersioned($bytes, $versions, $expectedLength);
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
        $payloadLength = $options['expectedLength'] ?: $withoutSum->getSize() - $versionLengthGuess;

        $versionBytes = $withoutSum->slice(0, (-1 * $payloadLength))->getDecimal();

        $payload = $withoutSum->slice((-1 * $payloadLength));

        foreach ($versions as $key => $version) {
            $version = is_array($versions[$key]) ? $versions[$key] : [$versions[$key]];
            if ($version === $versionBytes)
            {
                return [
                    'version' => $version,
                    'bytes'   => $payload,
                    'type'    => $types ? $types[$key] : null,
                ];
            }
        }

    }

    public function encodeChecked(Buffer $buffer): string
    {
        //TODO: uff...
        $check = $this->sha256($this->sha256($buffer))->slice(0, 4);

        return $this->encodeRaw($check);
    }

    public function decodeChecked(string $base58string)
    {
        $buffer = $this->decodeRaw($base58string);

        if ($buffer->getSize() < 5) {
            throw new Exception('invalid_input_size: decoded data must have length >= 5');
        }

        if (!$this->verifyCheckSum($buffer)) {
            throw new Exception('checksum_invalid');
        }

        return $buffer->slice(0, -4);
    }

    private function encodeVersioned(Buffer $bytes, array $versions, int $expectedLength): string
    {
        if ($bytes->getSize() !== $expectedLength) {
            throw new Exception('unexpected_payload_length: bytes.length does not match expectedLength. Ensure that the bytes are a Buffer.');
        }

        foreach ($versions as $version) {
            $bytes->prepend(sprintf('%02X', $version)); //TOTO: remove const
        }

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

    private function verifyCheckSum(Buffer $bytes)
    {
        $computed = substr(hash('sha256', hash('sha256', substr($bytes->getBinary(), 0, -4), true), true), 0, 4);
        $checksum = substr($bytes->getBinary(), -4);
        return $computed === $checksum;
    }

    private function sha256(Buffer $bytes): Buffer
    {
        return new Buffer();
        //return unpack('C*', substr(hash('sha256', hash('sha256', $bytes->getBinary(), true), true), 0, 4));;
    }
}
