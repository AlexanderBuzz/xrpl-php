<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Definitions;

use Ds\Map;

class Definitions
{
    public static ?Definitions $instance = null;

    private array $definitions;

    private array $typeOrdinals;

    private array $fieldHeaderMap;

    private array $ledgerEntryTypes;

    private array $transactionResults;

    private array $transactionTypes;

    private Map $fieldInfoMap;

    private Map $fieldIdNameMap;

    public function __construct()
    {
        $path = dirname(__FILE__) ."/definitions.json";
        $this->definitions = json_decode(file_get_contents($path), true);

        $this->typeOrdinals = $this->definitions['TYPES'];
        $this->ledgerEntryTypes = $this->definitions['LEDGER_ENTRY_TYPES'];
        $this->transactionResults = $this->definitions['TRANSACTION_RESULTS'];
        $this->transactionTypes = $this->definitions['TRANSACTION_TYPES'];

        $this->fieldInfoMap = new Map();
        $this->fieldIdNameMap = new Map();

        foreach ($this->definitions['FIELDS'] as $field) {
            $fieldName = $field[0];
            $metadata = new FieldInfo(
                $field[1]["nth"],
                $field[1]["isVLEncoded"],
                $field[1]["isSerialized"],
                $field[1]["isSigningField"],
                $field[1]["type"],
            );
            $fieldHeader = new FieldHeader($this->typeOrdinals[$metadata->getType()], $metadata->getNth());

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
        return $this->typeOrdinals[$typeName];
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

    public function mapSpecificFieldFromValue(string $fieldName, string $value): int
    {
        switch ($fieldName) {
            case "LedgerEntryType":
                $lookup = $this->ledgerEntryTypes;
                break;
            case "TransactionResult":
                $lookup = $this->transactionResults;
                break;
            case "TransactionType":
                $lookup = $this->transactionTypes;
                break;
            default:
                return 0; //TODO: check
        }

        return (isset($lookup[$value])) ? $lookup[$value] : 0; //TODO: check
    }

    public function mapValueToSpecificField(string $fieldName, string $value): string
    {
        switch ($fieldName) {
            case "LedgerEntryType":
                $lookup = array_flip($this->ledgerEntryTypes);
                break;
            case "TransactionResult":
                $lookup = array_flip($this->transactionResults);
                break;
            case "TransactionType":
                $lookup = array_flip($this->transactionTypes);
                break;
            default:
                return "";
        }

        return (isset($lookup[(int) $value])) ? $lookup[(int) $value] : "";
    }
}