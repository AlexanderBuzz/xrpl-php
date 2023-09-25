<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Core\RippleBinaryCodec\Serdes;

use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\MathUtilities;
use XRPL_PHP\Core\RippleBinaryCodec\Definitions\FieldInstance;
use XRPL_PHP\Core\RippleBinaryCodec\Types\SerializedType;

class BinarySerializer
{
    private Buffer $bytes;

    public function __construct(Buffer $bytes)
    {
        $this->bytes = $bytes;
    }

    public function put(string $hexBytes): void
    {
        $this->bytes->appendHex($hexBytes);
    }

    public function write(Buffer $bytes): void
    {
        $this->bytes->appendBuffer($bytes);
    }

    public function writeFieldAndValue(FieldInstance $field, SerializedType $value): void
    {
        $fieldHeaderHex = $field->getHeader()->toBytes()->toString();
        $this->put($fieldHeaderHex);

        if ($field->isVariableLengthEncoded()) {
            $this->writeLengthEncoded($value);
        } else {
            $this->write($value->toBytes());
        }
    }

    public function writeLengthEncoded(SerializedType $value): void
    {
        $buffer = $value->toBytes();
        $this->write($this->encodeVariableLength($buffer->getLength()));
        $this->write($buffer);
    }

    private function encodeVariableLength(int $length): Buffer
    {
        if ($length <= 192) {
            return Buffer::from([$length]);
        } else if ($length <= 12480) {
            $length -= 193;
            $byte1 = 193 + MathUtilities::unsignedRightShift($length, 8);
            $byte2 = $length & 0xff;
            return Buffer::from([$byte1, $byte2]);
        } else if ($length <= 918744) {
            $length -= 12481;
            $byte1 = 241 + MathUtilities::unsignedRightShift($length, 16);
            $byte2 = MathUtilities::unsignedRightShift($length, 8) & 0xff;
            $byte3 = $length & 0xff;
            return Buffer::from([$byte1, $byte2, $byte3]);
    }

        throw new \Exception('Overflow error');
    }

    /**
     * @return Buffer
     */
    public function getBytes(): Buffer
    {
        return $this->bytes;
    }
}