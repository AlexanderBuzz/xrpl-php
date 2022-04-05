<?php declare(strict_types = 1);

namespace XRPL_PHP\RippleAddressCodec;

use Lessmore92\Buffer\Buffer;
use XRPL_PHP\RippleAddressCodec\CodecWithXrpAlphabet;
use XRPL_PHP\RippleAddressCodec\Utils;

class RippleAddressCodec extends CodecWithXrpAlphabet
{
    public const PREFIX_BYTES = [
      'main' => [0x05, 0x44], // 5, 68
      'test' => [0x04, 0x93], // 4, 147
    ];

    public const MAX_32_BIT_UNSIGNED_INT = 4294967295;

    public function __contruct()
    {
        parent::__construct(Utils::XRPL_ALPHABET);
    }

    public function classicAddressToXAddress(string $classicAddress, $tag, bool $test = false): string
    {
        $accountBuffer = $this->decodeAccountID($classicAddress);
        return $this->encodeXAddress($accountBuffer, $tag, $test);
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
        $bytes = array_merge($bytes, $accountId->getDecimal());
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

        return $this->encodeChecked(Buffer::hex(join($hex)));
    }

    public function xAddressToClassicAddress()
    {

    }

    public function decodeXAddress()
    {

    }

    public function isValidXAddress()
    {

    }

    private function isBufferForTestAddress(): bool
    {

    }

    private function tagFromBuffer()
    {

    }
}