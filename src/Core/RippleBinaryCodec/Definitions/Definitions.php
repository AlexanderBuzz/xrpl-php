<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions;

use Exception;

class Definitions
{
    public static ?Definitions $instance = null;

    private array $definitions = [];

    private array $typeOrdinals = [];

    private array $fieldHeaderMap = [];

    private array $ledgerEntryTypes = [];

    private array $transactionResults = [];

    private array $transactionTypes = [];

    private array $fieldInfoMap = [];

    private array $fieldIdNameMap = [];

    /**
     * Permissions that a DelegateSet transaction can grant on top of the
     * transaction level permissions (which are the transaction type ordinal
     * plus one). Mirrors granularPermissions in xrpl.js.
     */
    public const GRANULAR_PERMISSIONS = [
        'TrustlineAuthorize' => 65537,
        'TrustlineFreeze' => 65538,
        'TrustlineUnfreeze' => 65539,
        'AccountDomainSet' => 65540,
        'AccountEmailHashSet' => 65541,
        'AccountMessageKeySet' => 65542,
        'AccountTransferRateSet' => 65543,
        'AccountTickSizeSet' => 65544,
        'PaymentMint' => 65545,
        'PaymentBurn' => 65546,
        'MPTokenIssuanceLock' => 65547,
        'MPTokenIssuanceUnlock' => 65548,
    ];

    private array $delegatablePermissions = [];

    /**
     * Reverse lookups, built with "first definition wins" so that Xahau
     * entries never shadow a mainline ordinal.
     */
    private array $reverseLookups = [];

    /**
     * Definitions constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $path = getenv('XRPL_PHP_DEFINITIONS_FILE_PATH') ?: __DIR__ . "/definitions.json";
        if (file_exists($path)) {
            $fileContents = file_get_contents($path);
        } else {
            throw new Exception("Definitions file not found.");
        }

        $this->definitions = json_decode($fileContents, true);

        $hooksPath = __DIR__ . "/../../../Hooks/hooksDefinitions.json";
        if (file_exists($hooksPath)) {
            $hooksDefinitions = json_decode(file_get_contents($hooksPath), true);

            // Xahau reuses ordinals that the XRP Ledger assigns to different
            // transaction types and fields (e.g. URITokenMint and
            // XChainAddClaimAttestation are both 45, Xahau's Blob and the
            // XRPL's DIDDocument are both Blob:26). The Xahau definitions are
            // therefore only added where the XRPL has nothing of that name -
            // they never overwrite a mainline entry. Encoding Xahau
            // transactions keeps working; decoding an ambiguous ordinal
            // resolves to the XRPL name.
            $this->definitions['TYPES'] += $hooksDefinitions['TYPES'];
            $this->definitions['LEDGER_ENTRY_TYPES'] += $hooksDefinitions['LEDGER_ENTRY_TYPES'];
            $this->definitions['TRANSACTION_RESULTS'] += $hooksDefinitions['TRANSACTION_RESULTS'] ?? [];
            $this->definitions['TRANSACTION_TYPES'] += $hooksDefinitions['TRANSACTION_TYPES'] ?? [];

            $knownFields = array_column($this->definitions['FIELDS'], 0);
            foreach ($hooksDefinitions['FIELDS'] as $field) {
                if (!in_array($field[0], $knownFields, true)) {
                    $this->definitions['FIELDS'][] = $field;
                }
            }
        }

        $this->typeOrdinals = $this->definitions['TYPES'];
        $this->ledgerEntryTypes = $this->definitions['LEDGER_ENTRY_TYPES'];
        $this->transactionResults = $this->definitions['TRANSACTION_RESULTS'];
        $this->transactionTypes = $this->definitions['TRANSACTION_TYPES'];

        // A DelegateSet permission is either a granular permission or a
        // transaction type ordinal incremented by one.
        $this->delegatablePermissions = self::GRANULAR_PERMISSIONS;
        foreach ($this->transactionTypes as $name => $ordinal) {
            $this->delegatablePermissions[$name] = $ordinal + 1;
        }

        $this->reverseLookups = [
            'LedgerEntryType' => $this->buildReverseLookup($this->ledgerEntryTypes),
            'TransactionResult' => $this->buildReverseLookup($this->transactionResults),
            'TransactionType' => $this->buildReverseLookup($this->transactionTypes),
            'PermissionValue' => $this->buildReverseLookup($this->delegatablePermissions),
        ];

        foreach ($this->definitions['FIELDS'] as $field) {
            $fieldName = $field[0];
            $fieldInfo = new FieldInfo(
                $field[1]["nth"],
                $field[1]["isVLEncoded"],
                $field[1]["isSerialized"],
                $field[1]["isSigningField"],
                $field[1]["type"],
            );
            $fieldHeader = new FieldHeader($this->typeOrdinals[$fieldInfo->getType()], $fieldInfo->getNth());

            $this->fieldInfoMap[$fieldName] = $fieldInfo;
            $this->fieldHeaderMap[$fieldName] = $fieldHeader;

            // First definition wins, so a Xahau field never shadows the
            // mainline field that shares its ordinal (see the merge above).
            $this->fieldIdNameMap[$fieldHeader->getTypeCode() . ":" . $fieldHeader->getFieldCode()] ??= $fieldName;
        }
    }

    public static function getInstance(): Definitions
    {
        if (static::$instance === null) {
            static::$instance = new Definitions();
        }

        return static::$instance;
    }

    public function getFieldHeaderFromName(string $fieldName): FieldHeader
    {
        if (!isset($this->fieldHeaderMap[$fieldName])) {
            throw new Exception("Field $fieldName not found in definitions.");
        }

        return $this->fieldHeaderMap[$fieldName];
    }

    public function getFieldNameFromHeader(FieldHeader $fieldHeader): string
    {
        $key = $fieldHeader->getTypeCode() . ":" . $fieldHeader->getFieldCode();

        if (!isset($this->fieldIdNameMap[$key])) {
            throw new Exception("Field with header $key not found in definitions.");
        }

        return $this->fieldIdNameMap[$key];
    }

    public function getFieldInstance(string $fieldName): FieldInstance
    {
        if (!isset($this->fieldInfoMap[$fieldName])) {
            throw new Exception("Field $fieldName not found in definitions.");
        }

        $fieldInfo = $this->fieldInfoMap[$fieldName];
        $fieldHeader = $this->getFieldHeaderFromName($fieldName);

        return new FieldInstance($fieldInfo, $fieldName, $fieldHeader);
    }

    public function mapSpecificFieldFromValue(string $fieldName, string $value): int|string
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
            case "PermissionValue":
                $lookup = $this->delegatablePermissions;
                break;
            default:
                return $value;
        }

        if (isset($lookup[$value])) {
            return $lookup[$value];
        }

        // An unknown name would otherwise be cast to the ordinal 0, which
        // silently turns e.g. an unknown transaction type into a Payment.
        if (!preg_match('/^-?\d+$/', $value)) {
            throw new Exception("Unknown {$fieldName}: {$value}");
        }

        return $value;
    }

    public function mapValueToSpecificField(string $fieldName, string|int $value): string
    {
        if (!isset($this->reverseLookups[$fieldName])) {
            return "";
        }

        return $this->reverseLookups[$fieldName][(int)$value] ?? "";
    }

    /**
     * Build a value => name lookup where the first name wins
     *
     * @param array $lookup
     * @return array
     */
    private function buildReverseLookup(array $lookup): array
    {
        $reversed = [];
        foreach ($lookup as $name => $ordinal) {
            $reversed[$ordinal] ??= $name;
        }

        return $reversed;
    }
}