<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Core\RippleKeyPairs;

use Hardcastle\Buffer\Buffer;
use Hardcastle\XRPL_PHP\Core\MathUtilities;
use Hardcastle\XRPL_PHP\Core\RippleAddressCodec\AddressCodec;
use Hardcastle\XRPL_PHP\Core\CoreUtilities;

class AbstractKeyPairService
{
    protected const PREFIX_ED25519 = 'ED';

    protected const PREFIX_SECP156K1 = '00';

    protected AddressCodec $addressCodec;

    protected string $type;

    public function __construct()
    {
        $this->addressCodec = new AddressCodec();
    }

    public function deriveAddress(Buffer|string $publicKey): string
    {
        //TODO: Check if this works properly
        return CoreUtilities::deriveAddress($publicKey);
    }

    public function getType(): string
    {
        return $this->type;
    }
}