<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Core\RippleKeyPairs;

use BN\BN;
use Elliptic\EC;
use Exception;
use Hardcastle\Buffer\Buffer;
use Hardcastle\XRPL_PHP\Core\MathUtilities;

/**
 * secp256k1 signing, the algorithm the ledger started with and still the one
 * behind most existing accounts.
 */
class Secp256k1KeyPairService extends AbstractKeyPairService implements KeyPairServiceInterface
{
    private static ?Secp256k1KeyPairService $instance = null;

    private readonly EC $elliptic;

    public function __construct()
    {
        $this->type = AbstractKeyPairService::PREFIX_SECP156K1;
        $this->elliptic = new EC(KeyPair::EC);

        parent::__construct();
    }

    /**
     * The shared instance.
     */
    public static function getInstance(): Secp256k1KeyPairService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * A new random seed for this algorithm.
     */
    public function generateSeed(?Buffer $entropy = null): string
    {
        if (is_null($entropy)) {
            $entropy = Buffer::random(16);
        }

        return $this->addressCodec->encodeSeed($entropy, 'secp256k1');
    }

    /**
     * Derive the key pair a seed stands for.
     * secp256k1 goes through an intermediate root key and a sequence, so the same
     * seed can yield further pairs; the ledger uses the first.
     */
    public function deriveKeyPair(Buffer|string $seed, bool $validator = false, int $accountIndex = 0): KeyPair
    {
        if (is_string($seed)) {
            $decoded = $this->addressCodec->decodeSeed($seed);
            $seed = Buffer::from($decoded['bytes']);
        }

        $privateKey = $this->derivePrivateKey($seed, $validator, $accountIndex);
        $publicKey = $this->derivePublicKey(new BN($privateKey, 16));

        return new KeyPair(
            strtoupper($publicKey),
            strtoupper(self::PREFIX_SECP156K1 . $privateKey)
        );
    }

    /**
     * Sign a message, returning a DER encoded signature.
     */
    public function sign(Buffer|string $message, string $privateKey): string
    {
        $messageBytes = ($message instanceof Buffer) ? $message->toUtf8() : $message;

        $hash = MathUtilities::sha512Half($messageBytes);
        $signed = $this->elliptic->sign(
            bin2hex($hash->toUtf8()),
            $privateKey,
            'hex',
            ['canonical' => true]
        )->toDER('hex');

        return strtoupper($signed);
    }

    /**
     * Check a signature against a message and a public key.
     *
     * Only a fully canonical signature passes, the form rippled accepts since
     * RequireFullyCanonicalSig: strict DER and an s in the lower half of the
     * group order. elliptic-php alone also accepts n - s and DER with padding,
     * and each of those is a second signature over the same message - in a
     * transaction, a second blob with another hash.
     */
    public function verify(Buffer|string $message, string $signature, string $publicKey): bool
    {
        if (!$this->isFullyCanonical($signature)) {
            return false;
        }

        $messageBytes = ($message instanceof Buffer) ? $message->toUtf8() : $message;

        $hash = MathUtilities::sha512Half($messageBytes);

        return $this->elliptic->verify(bin2hex($hash->toUtf8()), $signature, $publicKey, 'hex');
    }

    /**
     * Whether a DER signature is fully canonical, as rippled's
     * ecdsaCanonicality() decides it: a sequence of exactly two positive,
     * minimally encoded integers r and s, both nonzero and below the group
     * order n, and s no greater than n - s.
     */
    private function isFullyCanonical(string $signature): bool
    {
        if (strlen($signature) % 2 !== 0 || !ctype_xdigit($signature)) {
            return false;
        }

        $der = (string) hex2bin($signature);
        $length = strlen($der);
        if ($length < 8 || $length > 72 || ord($der[0]) !== 0x30 || ord($der[1]) !== $length - 2) {
            return false;
        }

        $position = 2;
        $r = self::readDerInteger($der, $position);
        $s = self::readDerInteger($der, $position);
        if ($r === null || $s === null || $position !== $length) {
            return false;
        }

        $r = new BN(bin2hex($r), 16);
        $s = new BN(bin2hex($s), 16);

        return !$r->isZero() && $r->cmp($this->elliptic->n) < 0
            && !$s->isZero() && $s->cmp($this->elliptic->nh) <= 0;
    }

    /**
     * Read one DER INTEGER at $position and move past it; null if it is not
     * there, longer than 33 bytes, negative or padded with a needless zero.
     */
    private static function readDerInteger(string $der, int &$position): ?string
    {
        if ($position + 2 > strlen($der) || ord($der[$position]) !== 0x02) {
            return null;
        }

        $length = ord($der[$position + 1]);
        $value = substr($der, $position + 2, $length);
        if ($length < 1 || $length > 33 || strlen($value) !== $length) {
            return null;
        }
        if ((ord($value[0]) & 0x80) !== 0) {
            return null;
        }
        if (ord($value[0]) === 0 && ($length === 1 || (ord($value[1]) & 0x80) === 0)) {
            return null;
        }

        $position += 2 + $length;

        return $value;
    }

    /**
     * Calculate a valid secp256k1 secret key by hashing a seed value;
    if the result isn't a valid key, increment a seq value and try
    again.
     *
     * @param Buffer $seed
     * @param bool $validator
     * @param int $accountIndex
     * @return string 32 bit Private / secret key
     * @throws Exception
     */
    private function derivePrivateKey(Buffer $seed, bool $validator = false, int  $accountIndex = 0): string
    {
        $privateGen = $this->deriveScalar($seed);

        //root key
        if ($validator) {
            return str_pad($privateGen->toString(16), 64, '0', STR_PAD_LEFT);
        }

        $publicGen = $this->elliptic->g->mul($privateGen);

        return $this->deriveScalar(Buffer::from($publicGen->encodeCompressed('hex'), 'hex'), $accountIndex)
            ->add($privateGen)
            ->mod($this->elliptic->n)
            ->toString(16);
    }

    private function derivePublicKey(BN $privateKey): string
    {
        return $this->elliptic->g->mul($privateKey)->encodeCompressed('hex');
    }

    private function deriveScalar(Buffer $seed, ?int $discriminator = null): BN
    {
        $zeroBN = new BN(0);
        $seqBN = $zeroBN->_clone();

        while (true) {
            $buffer = Buffer::from($seed);

            if (is_int($discriminator)) {
                $buffer->appendHex(str_pad(dechex($discriminator), 8, '0', STR_PAD_LEFT));
            }

            $seqHex = str_pad($seqBN->toString(16), 8, '0', STR_PAD_LEFT);
            $buffer->appendHex($seqHex);

            $hash = MathUtilities::sha512Half($buffer);
            $hashBN = new BN(bin2hex($hash->toUtf8()), 16);

            if($hashBN->cmp($zeroBN) != 0 && $hashBN->cmp($this->elliptic->n) < 0) {
                return $hashBN;
            }

            $seqBN = $seqBN->add(new BN(1));
        }
    }
}