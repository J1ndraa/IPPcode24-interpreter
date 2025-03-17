<?php

/**
 * IPP - PHP Project Student
 * @author Halva Jindřich (xhalva05)
 * @file XMLStructureException.php
 * @brief Exception for an invalid source XML format, returns error code 32
 * @date 2024-04-09
 */

namespace IPP\Student;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for an invalid source XML format
 */
class XMLStructureException extends IPPException
{
    public function __construct(string $message = "Wrong structure of XML source", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::INVALID_SOURCE_STRUCTURE, $previous, false);
    }
}


?>