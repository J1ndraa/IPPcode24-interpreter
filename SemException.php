<?php

/**
 * IPP - PHP Project Student
 * @author Halva Jindřich (xhalva05)
 * @file SemException.php
 * @brief Exception for semantic errors, returns error code 52
 * @date 2024-04-10
 */

namespace IPP\Student;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for an invalid source XML format
 */
class SemException extends IPPException
{
    public function __construct(string $message = "Semantic error while processing source code", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::SEMANTIC_ERROR, $previous, false);
    }
}


?>