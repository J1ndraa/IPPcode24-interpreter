<?php

/**
 * IPP - PHP Project Student
 * @author Halva Jindřich (xhalva05)
 * @file UnknownVarException.php
 * @brief Exception for usage of unknown variable, returns error code 54
 * @date 2024-04-10
 */

namespace IPP\Student;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for an invalid source XML format
 */
class UnknownVarException extends IPPException
{
    public function __construct(string $message = "Unknown variable used", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::VARIABLE_ACCESS_ERROR, $previous, false);
    }
}


?>