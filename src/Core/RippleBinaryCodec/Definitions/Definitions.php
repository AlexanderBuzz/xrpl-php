<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions;

use Exception;

/**
 * The field, type and enum tables the codec works against, read from
 * definitions.json.
 *
 * Maps field names to their ordinal and back, which is what makes a transaction
 * serializable. Other networks supply their own set; see fromArray() and
 * fromFile().
 */
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
     * Reverse lookups, built with "first definition wins": where a definitions
     * set carries two names for one ordinal, the first one listed decodes.
     */
    private array $reverseLookups = [];

    /**
     * Definitions constructor.
     *
     * Without arguments this loads the bundled XRP Ledger definitions. Pass a
     * definitions array to build an instance for a different network - see
     * fromArray() and fromFile().
     *
     * @param array|null $definitions A decoded definitions.json
     * @throws Exception
     */
    public function __construct(?array $definitions = null)
    {
        $this->definitions = $definitions ?? self::loadDefaultDefinitions();

        foreach (['TYPES', 'LEDGER_ENTRY_TYPES', 'TRANSACTION_RESULTS', 'TRANSACTION_TYPES', 'FIELDS'] as $section) {
            if (!isset($this->definitions[$section])) {
                throw new Exception("Definitions are missing the section {$section}.");
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

            // First definition wins, so the name listed first decodes an
            // ordinal two fields share.
            $this->fieldIdNameMap[$fieldHeader->getTypeCode() . ":" . $fieldHeader->getFieldCode()] ??= $fieldName;
        }
    }


    /**
     * Build an instance from a decoded definitions.json.
     *
     * This is the entry point for other networks: a Xahau package passes its
     * own definitions here instead of relying on the bundled ones.
     *
     * @param array $definitions
     * @return Definitions
     * @throws Exception
     */
    public static function fromArray(array $definitions): Definitions
    {
        return new Definitions($definitions);
    }

    /**
     * Build an instance from a definitions.json file.
     *
     * @param string $path
     * @return Definitions
     * @throws Exception
     */
    public static function fromFile(string $path): Definitions
    {
        if (!file_exists($path)) {
            throw new Exception("Definitions file not found: {$path}");
        }

        $definitions = json_decode(file_get_contents($path), true);
        if (!is_array($definitions)) {
            throw new Exception("Definitions file does not contain valid JSON: {$path}");
        }

        return new Definitions($definitions);
    }

    /**
     * The bundled XRP Ledger definitions, verbatim from a rippled node's
     * server_definitions (see scripts/sync-definitions.php). Nothing is merged
     * in: another network, Xahau included, brings its own set through
     * fromFile() or fromArray().
     *
     * @return array
     * @throws Exception
     */
    private static function loadDefaultDefinitions(): array
    {
        $path = getenv('XRPL_PHP_DEFINITIONS_FILE_PATH') ?: __DIR__ . "/definitions.json";
        if (!file_exists($path)) {
            throw new Exception("Definitions file not found.");
        }

        return json_decode(file_get_contents($path), true);
    }

    /**
     * The flags a transaction type accepts, name => value. The key
     * "universal" holds the flags every type accepts. Empty when the
     * definitions carry no TRANSACTION_FLAGS section or the type has none.
     *
     * @param string $transactionType
     * @return array<string, int>
     */
    public function getTransactionFlags(string $transactionType): array
    {
        /** @var array<string, int> */
        return $this->definitions['TRANSACTION_FLAGS'][$transactionType] ?? [];
    }

    /**
     * The values AccountSet accepts in SetFlag and ClearFlag, name => value.
     *
     * @return array<string, int>
     */
    public function getAccountSetFlags(): array
    {
        /** @var array<string, int> */
        return $this->definitions['ACCOUNT_SET_FLAGS'] ?? [];
    }

    /**
     * The flags a ledger entry type carries, name => value. Empty when the
     * definitions carry no LEDGER_ENTRY_FLAGS section or the type has none.
     *
     * @param string $ledgerEntryType
     * @return array<string, int>
     */
    public function getLedgerEntryFlags(string $ledgerEntryType): array
    {
        /** @var array<string, int> */
        return $this->definitions['LEDGER_ENTRY_FLAGS'][$ledgerEntryType] ?? [];
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