<?php
/**
 * IPP - PHP Project Student
 * @author Halva Jindřich (xhalva05)
 * @file Program.php
 * @brief Class holds information and methods about program
 * @date 2024-04-09
 */

namespace IPP\Student;

class Program{
    public string $language;
    /** @var array<mixed, string> */
    public array $instructions = array();

    /**
     * @brief Constructor for Program
     * @param string $language language of program
     */
    public function __construct($language){
        $this->language = $language;
        $this->instructions = array();
    }

    /**
     * @brief Method for adding instruction to program object
     * @param mixed $inst instruction to add
     * @return void
     */
    public function addInstruction($inst){
        $this->instructions = $inst;
    }
}

?>