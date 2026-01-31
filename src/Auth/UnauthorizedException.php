<?php

namespace RentalPlatform\Auth;

/**
 * Unauthorized Exception
 * 
 * Thrown when a user attempts to perform an action they are not authorized for
 */
class UnauthorizedException extends \Exception
{
    /**
     * Constructor
     * 
     * @param string $message Exception message
     * @param int $code Exception code (defaults to 403 Forbidden)
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = "Unauthorized access",
        int $code = 403,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
