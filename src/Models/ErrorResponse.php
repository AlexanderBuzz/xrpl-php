<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Models;

class ErrorResponse
{
    protected int|string|null $id = null;

    protected int $statusCode;

    protected string $type = 'response';

    protected string $error;

    protected string|int|null $errorCode;

    protected ?string $errorMessage;

    public function __construct(
        int|string|null $id,
        int $statusCode,
        string $error,
        string|int|null $errorCode = null,
        ?string $errorMessage = null
    ) {
        $this->id = $id;
        $this->statusCode = $statusCode;
        $this->error = $error;
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
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