<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use BI\BigInteger;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

abstract class UnsignedInt extends  SerializedType
{
    protected BigInteger $value;

    public function __construct(?Buffer $bytes = null)
    {
        parent::__construct($bytes);

        if ($bytes === null) {
            new BigInteger();
        } else {
            $this->value = new BigInteger($bytes->toString(), 16);
            $one = $this->value->toString();
            $test = 1;
        }
    }

    public function toBytes(): Buffer
    {
        $hexStr = $this->value->toHex();
        return Buffer::from($hexStr);
    }
}