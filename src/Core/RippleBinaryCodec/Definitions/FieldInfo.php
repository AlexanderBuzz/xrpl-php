<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Definitions;

/**
 * Model object for field info from the "fields" section of definitions.json.
 */
class FieldInfo
{
    private int $nth;

    private bool $isVariableLengthEncoded;

    private bool $isSerialized;

    private bool $isSigningField;

    private string $type;

    public function __construct(
        int    $nth,
        bool   $isVariableLengthEncoded,
        bool   $isSerialized,
        bool   $isSigningField,
        string $typeName
    )
    {
        $this->nth = $nth;
        $this->isVariableLengthEncoded = $isVariableLengthEncoded;
        $this->isSerialized = $isSerialized;
        $this->isSigningField = $isSigningField;
        $this->type = $typeName;
    }

    /**
     * @return int
     */
    public function getNth(): int
    {
        return $this->nth;
    }

    /**
     * @param int $nth
     */
    public function setNth(int $nth): void
    {
        $this->nth = $nth;
    }

    /**
     * @return bool
     */
    public function isVariableLengthEncoded(): bool
    {
        return $this->isVariableLengthEncoded;
    }

    /**
     * @param bool $isVariableLengthEncoded
     */
    public function setIsVariableLengthEncoded(bool $isVariableLengthEncoded): void
    {
        $this->isVariableLengthEncoded = $isVariableLengthEncoded;
    }

    /**
     * @return bool
     */
    public function isSerialized(): bool
    {
        return $this->isSerialized;
    }

    /**
     * @param bool $isSerialized
     */
    public function setIsSerialized(bool $isSerialized): void
    {
        $this->isSerialized = $isSerialized;
    }

    /**
     * @return bool
     */
    public function isSigningField(): bool
    {
        return $this->isSigningField;
    }

    /**
     * @param bool $isSigningField
     */
    public function setIsSigningField(bool $isSigningField): void
    {
        $this->isSigningField = $isSigningField;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }
}