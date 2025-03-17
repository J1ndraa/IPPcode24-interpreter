<?php
/**
 * IPP - PHP Project Student
 * @author Halva Jindřich (xhalva05)
 * @file Instruction.php
 * @brief Class holds information and methods about instruction
 * @date 2024-04-09
 */

namespace IPP\Student;

/**
 * Class Instruction
 * @brief Class holds information and methods about instruction
 */
class Instruction{
    public string $opcode;
    public int $order;
    /** @var array<mixed, mixed> */
    public $args = array();

    /**
     * @brief Constructor for Instruction
     * @param string $opcode - opcode of instruction
     * @param int $order - order of instruction
     */
    public function __construct($opcode, $order){
        $this->opcode = $opcode;
        $this->order = $order;
        $this->args = array();
    }

    /**
     * @brief Method for adding argument to instruction object
     * @param mixed $arg argument to add
     * @return void
     */
    public function addArg($arg){
        $this->args = $arg;
    }
}

