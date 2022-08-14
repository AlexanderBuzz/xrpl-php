<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleKeyPairs;

use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\MathUtilities;
use XRPL_PHP\Core\RippleAddressCodec\AddressCodec;

class AbstractKeyPairService
{
    protected const PREFIX_ED25519 = 'ED';

    protected const PREFIX_SECP156K1 = '00';

    protected AddressCodec $addressCodec;

    public function __construct()
    {
        $this->addressCodec = new AddressCodec();
    }

    /*
    public function generateSeed(?Buffer $entropy, ?string $type): string
    {
        $entropyBuffer = (isset($options['entropy'])) ? $options['entropy']->slice(0, 16) : Buffer::random(16);
        $type = ($type === 'ed25519') ? 'ed25519' : 'secp256k1';

        return Utilities::encodeSeed($entropyBuffer, $type);
    }
    */

    public function hash(string $value): Buffer
    {
        return MathUtilities::sha512Half($value);
    }
}