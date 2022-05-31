<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use BI\BigInteger;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

class AccountId extends Hash160
{
    public function __construct(?Buffer $bytes = null)
    {
        if ($bytes === null) {
            parent::__construct(Buffer::alloc(static::$width));
        } else {
            parent::__construct($bytes);
        }
    }

    public function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        return new AccountId($parser->read(static::$width));
    }

    public function fromJson(SerializedType $value, ?int $number): SerializedType
    {
        // TODO: Implement fromValue() method.
    }
}