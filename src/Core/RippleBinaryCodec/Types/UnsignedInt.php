<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use Brick\Math\BigInteger;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

abstract class UnsignedInt extends  SerializedType
{
    protected BigInteger $value;

    public function __construct(?Buffer $bytes = null)
    {
        parent::__construct($bytes);

        if ($bytes === null) {
            $this->value = BigInteger::of(0);
        } else {
            $this->value = BigInteger::of($bytes->toDecimalString());
        }
    }

    public function toBytes(): Buffer
    {
        //$hexStr = $this->value->toHex();
        //$hexStr = dechex($this->value->toHex());
        //return Buffer::from($hexStr, 'hex');
        return $this->bytes;
    }


    public function toHex(): string
    {
        return strtoupper($this->toBytes()->toString());
    }

    public function toJson(): array|string|int //TODO: does this need to be abstract?
    {
        return parent::toJson(); // TODO: Change the autogenerated stub
        //return $this->valueOf();
    }

    //abstract function valueOf(): int | BigInteger;

    public function valueOf(): int
    {
        return $this->value->toInt(); //TODO: check for overflows, will crash on 64
    }
}