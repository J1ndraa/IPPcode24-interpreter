<?php

/**
 * IPP - PHP Project Student
 * @author Halva Jindřich (xhalva05)
 * @file StringOperationException.php
 * @brief Exception for invalid string operation, returns error code 58
 * @date 2024-04-10
 */

namespace IPP\Student;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for invalid string operation
 */
class StringOperationException extends IPPException
{
    public function __construct(string $message = "String operation error", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::STRING_OPERATION_ERROR, $previous, false);
    }
}


?>