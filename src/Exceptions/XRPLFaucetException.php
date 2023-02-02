<?php declare(strict_types=1);

namespace XRPL_PHP\Exceptions;

/**
 * Error thrown when a client cannot generate a wallet from the testnet/devnet
 * faucets, or when the client cannot infer the faucet URL (i.e. when the Client
 * is connected to mainnet).
 */
class XRPLFaucetException extends XrplException {}