<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions;

use Hardcastle\Buffer\Buffer;

/**
 * The type and field ordinal that identify a field on the wire.
 *
 * Together these two numbers form the header byte or bytes that precede every
 * field value. hash() gives them as one string ("typeCode:fieldCode"), usable
 * as an array key, which is how Definitions maps a header back to its field.
 */
class FieldHeader
{
    public function __construct(private int $typeCode, private int $fieldCode)
    {
    }

    /**
     * The header as it is written to the stream.
     * Type and field ordinal share one byte where both are below 16, and spill
     * into extra bytes otherwise.
     */
    public function toBytes(): Buffer
    {
        $header = [];

        if ($this->typeCode < 16) {
            if ($this->fieldCode < 16) {
                // single byte case where high bits contain type code, low bits contain field code
                $header[] = $this->typeCode << 4 | $this->fieldCode;
            } else {
                // 2 byte case where first byte contains type code + filler, second byte contains field code
                $header[] = $this->typeCode << 4;
                $header[] = $this->fieldCode;
            }
        } else if ($this->fieldCode < 16) {
            // 2 byte case where first byte contains filler+field code, second byte contains typeCode
            $header[] = $this->fieldCode;
            $header[] = $this->typeCode;
        } else {
            // 3 byte case where first byte is filler, 2nd byte is type code, third byte is field code
            $header[] = 0;
            $header[] = $this->typeCode;
            $header[] = $this->fieldCode;
        }
        return Buffer::from($header);
    }

    /**
     * @return int
     */
    public function getTypeCode(): int
    {
        return $this->typeCode;
    }

    /**
     * @param int $typeCode
     */
    public function setTypeCode(int $typeCode): void
    {
        $this->typeCode = $typeCode;
    }

    /**
     * @return int
     */
    public function getFieldCode(): int
    {
        return $this->fieldCode;
    }

    /**
     * @param int $fieldCode
     */
    public function setFieldCode(int $fieldCode): void
    {
        $this->fieldCode = $fieldCode;
    }

    /**
     * The header as an array key: "typeCode:fieldCode".
     */
    public function hash(): string
    {
        return $this->typeCode . ":" . $this->fieldCode;
    }

    /**
     * Whether two headers name the same field. Anything that is not a
     * FieldHeader is not equal.
     */
    public function equals(mixed $obj): bool
    {
        return $obj instanceof self
            && $this->typeCode === $obj->getTypeCode()
            && $this->fieldCode === $obj->getFieldCode();
    }
}