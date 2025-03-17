<?php

/**
 * IPP - PHP Project Student
 * @author Halva Jindřich (xhalva05)
 * @file FrameAccessException.php
 * @brief Exception for wrong usage of frame, returns error code 55
 * @date 2024-04-10
 */

namespace IPP\Student;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for wrong access to frame
 */
class FrameAccessException extends IPPException
{
    public function __construct(string $message = "Frame access error", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::FRAME_ACCESS_ERROR, $previous, false);
    }
}


?>