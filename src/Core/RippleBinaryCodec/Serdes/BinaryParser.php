<?php

namespace XRPL_PHP\Core\RippleBinaryCodec\Serdes;

use XRPL_PHP\Core\Buffer;
use function PHPUnit\Framework\throwException;

/**
 * BinaryParser is used to compute fields and values from a HexString
 *
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/ripple-binary-codec/src/serdes/binary-parser.ts
 */
class BinaryParser
{
    private Buffer $bytes;

    public function __construct(string $hexBytes)
    {
        $this->bytes = Buffer::from($hexBytes, 'hex');
    }

    public function peek(): int
    {
        if ($this->bytes->getLength() > 0) {
            return $this->bytes->toArray()[0];
        }

        throw new \Exception('Buffer is empty');
    }

    public function skip(int $number): void
    {
        if ($this->bytes->getLength() >= $number) {
            $this->bytes = $this->bytes->slice($number);
        }

        throw new \Exception('Trying to skip more elements than the buffer has');
    }

    public function read(int $number): Buffer
    {
        if ($this->bytes->getLength() >= $number) {
            $slice = $this->bytes->slice(0, $number);
            $this->skip($number);

            return $slice;
        }

        throw new \Exception('Trying to read more elements than the buffer has');
    }

    public function readUIntN(int $number): int
    {
        if ($number > 0 && $number <= 4) {
            $stdArray = $this->read($number)->toArray();
            $reducer = function ($carry, $item) {

            };
            return array_reduce($stdArray, $reducer);
        }

        throw new \Exception('Invalid number');
    }

}