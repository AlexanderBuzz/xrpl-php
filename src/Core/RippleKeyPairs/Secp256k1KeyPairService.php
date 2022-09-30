<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleKeyPairs;

use BN\BN;
use Elliptic\EC;
use Exception;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\MathUtilities;

class Secp256k1KeyPairService extends AbstractKeyPairService implements KeyPairServiceInterface
{
    private static ?Secp256k1KeyPairService $instance = null;

    private EC $elliptic;

    public function __construct()
    {
        $this->type = AbstractKeyPairService::PREFIX_SECP156K1;
        $this->elliptic = new EC(KeyPair::EC);

        parent::__construct();
    }

    public static function getInstance(): Secp256k1KeyPairService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function generateSeed(?Buffer $entropy = null): string
    {
        if (is_null($entropy)) {
            $entropy = Buffer::random(16);
        }

        return $this->addressCodec->encodeSeed($entropy, 'secp256k1');
    }

    /**
     * @throws Exception Error
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

    public function sign(Buffer|string $message, string $privateKey): string
    {
        if (!is_string($message)) {
            $message = $message->toString();
        }

        $hash = MathUtilities::sha512Half($message);
        $signed = $this->elliptic->sign(
            $hash->toString(),
            //substr($privateKey, 2),
            $privateKey,//substr($privateKey, 2),
            'hex',
            ['canonical' => true]
        )->toDER('hex');

        return Buffer::from($signed)->toString();
    }

    public function verify(Buffer|string $message, string $signature, string $publicKey): bool
    {
        if (!is_string($message)) {
            $message = $message->toString();
        }

        $hash = MathUtilities::sha512Half($message);

        return $this->elliptic->verify($hash->toString(), $signature, $publicKey, 'hex');
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
        $order = $this->elliptic->n;
        $privateGen = $this->deriveScalar($seed);

        //root key
        if ($validator) {
            return $privateGen->toString('hex');
        }

        $publicGen = $this->elliptic->g->mul($privateGen);

        return $this->deriveScalar(Buffer::from($publicGen->encodeCompressed('hex')), $accountIndex)
            ->add($privateGen)
            ->mod($this->elliptic->n)
            ->toString('hex');
    }

    private function derivePublicKey(BN $privateKey): string
    {
        return $this->elliptic->g->mul($privateKey)->encodeCompressed('hex');
    }

    private function deriveScalar(Buffer $seed, ?int $discriminator = null): BN
    {
        $seedArray = $seed->toArray();
        $zeroBN = new BN(0);
        $seqBN = $zeroBN->_clone();

        while (true) {
            $buffer = Buffer::from($seedArray);

            if (is_int($discriminator)) {
                $buffer->appendHex('00000000');
            }

            $seqHex = str_pad($seqBN->toString('hex'), 8, '00', STR_PAD_LEFT);
            $buffer->appendHex($seqHex);

            $hash = MathUtilities::sha512Half($buffer);
            $hashBN = new BN($hash->toString(), 16);

            if($hashBN->cmp($zeroBN) != 0 && $hashBN->cmp($this->elliptic->n) < 0) {
                return $hashBN;
            }

            $seqBN = $seqBN->add(1);
        }
    }
}