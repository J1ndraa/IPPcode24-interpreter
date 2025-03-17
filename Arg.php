<?php
/**
 * IPP - PHP Project Student
 * @author Halva Jindřich (xhalva05)
 * @file Arg.php
 * @brief Class holds information and methods about arguments in XML
 * @date 2024-04-09
 */

namespace IPP\Student;

/**
 * @brief Class holds information about arguments in XML
 */
class Arg{
    public string $type;
    public string $value;

    public function __construct(string $type, string $value){
        $this->type = $type;
        $this->value = $value;
    }
}


?>