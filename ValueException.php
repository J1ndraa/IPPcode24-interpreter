<?php

/**
 * IPP - PHP Project Student
 * @author Halva Jindřich (xhalva05)
 * @file ValueException.php
 * @brief Exception for usage of wrong value, returns error code 56
 * @date 2024-04-10
 */

namespace IPP\Student;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for an invalid value
 */
class ValueException extends IPPException
{
    public function __construct(string $message = "Wrong value", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::VALUE_ERROR, $previous, false);
    }
}


?>