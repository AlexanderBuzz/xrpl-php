<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Core\RippleBinaryCodec;

use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\StObject;

/**
 * Turns bytes into JSON, and holds the definitions the codec works against.
 */
class Binary
{
    protected ?Definitions $definitions = null;

    /**
     * The definitions this codec works against.
     *
     * Defaults to the bundled XRP Ledger definitions. A package for another
     * network passes its own set into the constructor.
     *
     * @param Definitions|null $definitions
     * @throws \Exception
     */
    public function __construct(?Definitions $definitions = null)
    {
        $this->definitions = $definitions;
    }

    /**
     * @return Definitions
     * @throws \Exception
     */
    public function getDefinitions(): Definitions
    {
        return $this->definitions ??= Definitions::getInstance();
    }

    /**
     * A parser over the given bytes, carrying this codec's definitions.
     */
    public function makeParser(string $bytes): BinaryParser
    {
        return new BinaryParser($bytes, $this->getDefinitions());
    }

    /*
    public function serializeObject(string $jsonObject, array $options = [])
    {
        //TODO: This is old an needs to revamped completely
        [
            'prefix' => $prefix,
            'suffix' => $suffix,
            'signingFieldsOnly' => $signingFieldsOnly //default false!
        ] = $options;

        $bytesList = new BytesList();

        if ($prefix) {
            $bytesList->push($prefix);
        }


        $bytesList->push(STObject::fromJson($jsonObject)->toBytes());

        if ($prefix) {
            $bytesList->push($suffix);
        }

      return $bytesList->toBytes();

    }
    */
    /**
     * Read one object out of a parser and return its JSON form.
     *
     * @param BinaryParser $parser
     */
    public function readJson(BinaryParser $parser): array|int|string //xrpl.js: JsonObject, defined in serialized-type.js
    {
        $type = new StObject();

        return $parser->readType($type)->toJson(); // currently implementing this
    }

    /*
    public function signingData(array $jsonObject): Buffer
    {
        $paddedPrefix = str_pad((string)HashPrefix::TRANSACTION_SIGN, 8, '00', STR_PAD_LEFT);
        return $this->serializeObject(
            $jsonObject,
        )
    }

    public function multiSigningData()
    {
        //TODO: implement function
    }

    public function signingClaimData()
    {
        //TODO: implement function
    }
    */

    /**
     * Decode a hex string into the JSON form of the object it holds.
     */
    public function binaryToJson(string $bytes): array
    {
        $parser = $this->makeParser($bytes);

        return $this->readJson($parser);
    }
}