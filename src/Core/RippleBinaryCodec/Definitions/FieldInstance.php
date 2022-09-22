<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Definitions;

use XRPL_PHP\Core\RippleBinaryCodec\Types\SerializedType;
use XRPL_PHP\Core\RippleBinaryCodec\Types\StArray;

/**
 * A collection of serialization information about a specific field type.
 */
class FieldInstance
{
    private int $nth;

    private bool $isVariableLengthEncoded;

    private bool $isSerialized;

    private bool $isSigningField;

    private string $type;

    private string $name;

    private FieldHeader $fieldHeader;

    private int $ordinal;

    private string $associatedType;

    public function __construct(FieldInfo $fieldInfo, string $fieldName, FieldHeader $fieldHeader)
    {
        $this->nth = $fieldInfo->getNth();
        $this->isVariableLengthEncoded = $fieldInfo->isVariableLengthEncoded();
        $this->isSerialized = $fieldInfo->isSerialized();
        $this->isSigningField = $fieldInfo->isSigningField();
        $this->type = $fieldInfo->getType();
        $this->name = $fieldName;
        $this->fieldHeader = $fieldHeader;
        $this->ordinal = $this->fieldHeader->getTypeCode() << 16 | $this->nth;
        $this->associatedType = SerializedType::getTypeByName($this->type)::class;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getHeader(): FieldHeader
    {
        return $this->fieldHeader;
    }

    public function isVariableLengthEncoded(): bool
    {
        return $this->isVariableLengthEncoded;
    }

    public function isSigningField(): bool
    {
        return $this->isSigningField();
    }
}