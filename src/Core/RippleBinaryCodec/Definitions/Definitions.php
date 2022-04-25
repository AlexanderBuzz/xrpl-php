<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Definitions;

use Ds\Map;

class Definitions
{
    public static ?Definitions $instance = null;

    public array $test = [];

    public array $typeOrdinalMap;

    public array $fieldHeaderMap;

    public Map $fieldInfoMap;

    public Map $fieldIdNameMap;

    public function __construct()
    {
        $path = dirname(__FILE__) ."/definitions.json";
        $this->test = json_decode(file_get_contents($path), true);

        $this->fieldInfoMap = new Map();
        $this->fieldIdNameMap = new Map();
        $this->typeOrdinalMap = $this->test['TYPES'];

        foreach ($this->test['FIELDS'] as $field) {
            $fieldName = $field[0];
            $metadata = new FieldInfo(
                $field[1]["nth"],
                $field[1]["isVLEncoded"],
                $field[1]["isSerialized"],
                $field[1]["isSigningField"],
                $field[1]["type"],
            );
            $fieldHeader = new FieldHeader($this->typeOrdinalMap[$metadata->getType()], $metadata->getNth());

            $this->fieldInfoMap->put($fieldName, $metadata);
            $this->fieldIdNameMap->put($fieldHeader, $fieldName);
            //TODO: make array and map mix more concise
            $this->fieldHeaderMap[$fieldName] = $fieldHeader;

        }
    }

    public static function getInstance(): Definitions
    {
        if(static::$instance === null) {
            static::$instance = new Definitions();
        }

        return static::$instance;
    }

    public function getTypeOrdinal(string $typeName): int
    {
        //Java
        //return typeOrdinalMap.get(typeName);
        return $this->typeOrdinalMap[$typeName];
    }

    public function getFieldHeaderFromName(string $fieldName): FieldHeader
    {
        return $this->fieldHeaderMap[$fieldName];
    }

    public function getFieldNameFromHeader(FieldHeader $fieldHeader): string
    {
        return $this->fieldIdNameMap->get($fieldHeader);
    }

    public function getFieldInstance(string $fieldName): FieldInstance
    {
        $fieldInfo = $this->fieldInfoMap->get($fieldName);
        $fieldHeader = $this->getFieldHeaderFromName($fieldName);

        return new FieldInstance($fieldInfo, $fieldName, $fieldHeader);
    }
}