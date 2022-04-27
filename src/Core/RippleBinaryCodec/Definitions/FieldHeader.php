<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Definitions;

use Ds\Hashable;
use XRPL_PHP\Core\Buffer;

class FieldHeader implements Hashable
{
    private int $typeCode;

    private int $fieldCode;

    public function __construct(int $typeCode, int $fieldCode)
    {
        $this->typeCode = $typeCode;
        $this->fieldCode = $fieldCode;
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
        } else {
            if ($this->fieldCode < 16) {
                // 2 byte case where first byte contains filler+field code, second byte contains typeCode
                $header[] = $this->fieldCode;
                $header[] = $this->typeCode;
            } else {
                // 3 byte case where first byte is filler, 2nd byte is type code, third byte is field code
                $header[] = 0;
                $header[] = $this->typeCode;
                $header[] = $this->fieldCode;
            }
        }
        return Buffer::from($header);
        /*
         *  header = []
        if self.type_code < 16:
            if self.field_code < 16:
                header.append(self.type_code << 4 | self.field_code)
            else:
                header.append(self.type_code << 4)
                header.append(self.field_code)
        elif self.field_code < 16:
            header += [self.field_code, self.type_code]
        else:
            header += [0, self.type_code, self.field_code]

        return bytes(header)
         */
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
        return $this->typeCode . $this->fieldCode;
    }

    public function equals($obj): bool
    {
        return ($this->typeCode === $obj->getTypeCode() && $this->fieldCode === $obj->getFieldCode());
    }
}