<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions;

use Ds\Hashable;
use Hardcastle\Buffer\Buffer;

/**
 * The type and field ordinal that identify a field on the wire.
 *
 * Together these two numbers form the header byte or bytes that precede every
 * field value.
 */
class FieldHeader implements Hashable
{
    public function __construct(private int $typeCode, private int $fieldCode)
    {
    }

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

    public function hash()
    {
        return $this->typeCode . ":" . $this->fieldCode;
    }

    public function equals($obj): bool
    {
        return ($this->typeCode === $obj->getTypeCode() && $this->fieldCode === $obj->getFieldCode());
    }
}