<?php

/**
 * IPP - PHP Project Student
 * @author Halva Jindřich (xhalva05)
 * @file OperandTypeException.php
 * @brief Exception for usage of wrong operand types, returns error code 53
 * @date 2024-04-10
 */

namespace IPP\Student;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for an invalid usage of operand types
 */
class OperandTypeException extends IPPException
{
    public function __construct(string $message = "Wrong operands type", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::OPERAND_TYPE_ERROR, $previous, false);
    }
}


?>