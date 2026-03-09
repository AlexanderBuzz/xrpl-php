<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Models;

class ErrorResponse
{
    protected string $type = 'response';

    public function __construct(protected int|string|null $id, protected int $statusCode, protected string $error, protected string|int|null $errorCode = null, protected ?string $errorMessage = null)
    {
    }

    public function getStatus(): string
    {
        return 'error';
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getError(): string
    {
        return $this->error;
    }
}