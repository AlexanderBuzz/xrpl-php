<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Definitions;

use XRPL_PHP\Core\RippleBinaryCodec\Types\SerializedType;

/**
 * A collection of serialization information about a specific field type.
 */
class FieldInstance
{
    private readonly int $nth;

    private readonly bool $isVariableLengthEncoded;

    private readonly bool $isSerialized;

    private readonly bool $isSigningField;

    private readonly string $type;

    private readonly int $ordinal;

    private readonly string $name;

    private FieldHeader $fieldHeader;

    private readonly string $associatedType;

    /**
     *
     *
     * @param FieldInfo $fieldInfo
     * @param string $fieldName
     * @param FieldHeader $fieldHeader
     * @throws \Exception
     */
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
        return $this->isSigningField;
    }

    public function getOrdinal(): int
    {
        return $this->ordinal;
    }

    public function buildField(string $name, FieldInfo $fieldInfo, int $typeOrdinal): FieldInstance
    {

    }
}