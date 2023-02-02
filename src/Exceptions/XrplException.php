<?php declare(strict_types=1);

namespace XRPL_PHP\Exceptions;

use Exception;

/**
 * Base Error class for xrpl.js. All Errors thrown by xrpl.js should throw
 * XrplErrors.
 */
class XrplException extends Exception
{
    protected string $name;
    protected ?string $data;

    /**
     * Construct an XrplError.
     *
     * @param string $message
     * @param string|null $data
     */
    public function __construct(string $message = '', ?string $data = '') {
        $this->name = self::class;
        $this->data = $data;

        parent::__construct($message);
    }

    /**
     * Converts the Error to a human-readable String form.
     *
     * @return string The String output of the Error.
     */
    public function __toString(): string
    {
        $result = "[$this->message($this->message";
        if (!empty($this->data)) {
            $result .= $this->data;
        }
        $result .= ")]";

        return $result;
    }
}