<?php

namespace XRPL_PHP\Core\RippleBinaryCodec\Serdes;

use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Definitions\FieldInstance;
use XRPL_PHP\Core\RippleBinaryCodec\Types\SerializedType;

class BinarySerializer
{
    private Buffer $bytes;

    public function __construct(Buffer $bytes)
    {
        $this->bytes = $bytes;
    }

    public function put(string $hexBytes)
    {
        $this->bytes->appendHex($hexBytes);
    }

    public function writeFieldAndValue(FieldInstance $field, SerializedType $value)
    {
        $fieldHeaderHex = $field->getHeader()->toBytes()->toString();
        $this->bytes->appendHex($fieldHeaderHex);

        if ($field->isVariableLengthEncoded()) {
            $this->writeLengthEncoded($value);
        } else {
            $this->bytes->appendHex($value->toBytes()->toString());
        }
    }

    public function writeLengthEncoded(SerializedType $value): void
    {

    }

    /**
     * @return Buffer
     */
    public function getBytes(): Buffer
    {
        return $this->bytes;
    }
}