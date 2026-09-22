<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Models\PaymentChannel\ChannelVerifyRequest;
use Hardcastle\XRPL_PHP\Models\PaymentChannel\ChannelVerifyResponse;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

/**
 * A claim signed locally has to pass rippled's own check. channel_verify is a
 * public method and needs no channel to exist, so this runs against the
 * Testnet without funding anything.
 */
#[Group('integration')]
final class PaymentChannelClaimVerifyTest extends TestCase
{
    private const TESTNET_URL = 'https://s.altnet.rippletest.net:51234';

    private const CHANNEL = '5DB01B7FFED6B67E6B0414DED11E051D2EE2B7619CE0EAA6286D67A3A4D5BDB3';

    public function testRippledVerifiesLocallySignedClaims(): void
    {
        $client = new JsonRpcClient(self::TESTNET_URL);

        foreach ([Wallet::generate(), Wallet::fromSeed('snGHNrPbHrdUcszeuDEigMdC1Lyyd')] as $wallet) {
            $signature = $wallet->signPaymentChannelClaim(self::CHANNEL, '1000000');

            $verified = $client->syncRequest(
                new ChannelVerifyRequest(self::CHANNEL, '1000000', $wallet->getPublicKey(), $signature)
            );
            $this->assertInstanceOf(ChannelVerifyResponse::class, $verified);
            $this->assertTrue($verified->getResult()['signature_verified'], $wallet->getPublicKey());

            $tampered = $client->syncRequest(
                new ChannelVerifyRequest(self::CHANNEL, '1000001', $wallet->getPublicKey(), $signature)
            );
            $this->assertInstanceOf(ChannelVerifyResponse::class, $tampered);
            $this->assertFalse($tampered->getResult()['signature_verified']);
        }
    }
}
