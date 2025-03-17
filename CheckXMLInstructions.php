<?php
/**
 * IPP - PHP Project Student
 * @author Halva Jindřich (xhalva05)
 * @file CheckXMLInstructions.php
 * @brief Check format of instructions
 * @date 2024-04-08
 */

namespace IPP\Student;

use IPP\Core\Exception\XMLException;

/**
 * @brief Check of XML instructions
 */
class CheckXMLInstructions{

    /**
     * @brief Check of XML instructions, if they are in correct form, check number and types of arguments
     * @param mixed $Program - XML program
     * @return void
     */
    public function XMLInstCheck($Program){
        //Regular expressions for checking values and types
        $pattern_label_value = '/^([a-zA-Z]|[-_$&%*!?])([a-zA-Z0-9]|[_$&%*!?-])*$/';
        $pattern_var_value = '/^(GF|LF|TF)@([a-zA-Z]|[_$&%*!?-])([a-zA-Z0-9]|[_$&%*!?-])*$/';
        $pattern_symb = '/^(int|string|bool|nil|var){1}$/';
        $pattern_symb_value = '/^([a-zA-Z0-9-]|[_$=&%*!?´@ěščřžýáíéúůĚŠČŘŽÝÁÍÉÚŮńňŇḿḾ¨+()`;\'\":.,~|§\{\}\[\]]|[<>\/]|[\\\d{3}])*$/';
        $inst_num = count($Program->instructions);

        //loop through all instructions
        for($i = 0; $i < $inst_num; $i++){
            $inst = $Program->instructions[$i];
            $inst_name = strtoupper($inst->opcode);
            $inst_order = $inst->order;
            $inst_args = $inst->args;
            $inst_args_num = count($inst_args);
            switch($inst_name){

                //no arguments... easy
                case "CREATEFRAME": 
                case "PUSHFRAME":
                case "POPFRAME":
                case "BREAK":
                case "RETURN":
                    if($inst_args_num != 0){
                        throw new XMLStructureException;
                    }
                    break;

                // <var>
                case "DEFVAR":
                case "POPS" :
                    if($inst_args_num != 1){
                        throw new XMLStructureException;
                    }
                    if($inst_args[0]->type != "var" || preg_match($pattern_var_value, $inst_args[0]->value) !== 1){
                        throw new XMLStructureException;
                    }
                    break;

                // <symb>
                case "EXIT":
                case "DPRINT":
                case "WRITE":
                case "PUSHS":
                    if($inst_args_num != 1){
                        throw new XMLStructureException;
                    }
                    if(preg_match($pattern_symb, $inst_args[0]->type) !== 1 || preg_match($pattern_symb_value, $inst_args[0]->value) !== 1){
                        throw new XMLStructureException;
                    }
                    break;

                // <label>
                case "CALL":
                case "LABEL":
                case "JUMP":
                    if($inst_args_num != 1){
                        throw new XMLStructureException;
                    }
                    if($inst_args[0]->type != "label" || preg_match($pattern_label_value, $inst_args[0]->value) !== 1){
                        throw new XMLStructureException();
                    }
                    break;

                // <var> <symb>
                case "MOVE":
                case "INT2CHAR":
                case "STRLEN":
                case "TYPE":
                case "NOT":
                    if($inst_args_num != 2){
                        throw new XMLStructureException;
                    }
                    if($inst_args[0]->type != "var" || preg_match($pattern_var_value, $inst_args[0]->value) !== 1){
                        throw new XMLStructureException;
                    }
                    if(preg_match($pattern_symb, $inst_args[1]->type) !== 1 || preg_match($pattern_symb_value, $inst_args[1]->value) !== 1){
                        
                        throw new XMLStructureException;
                    }
                    break;

                // <var> <type>
                case "READ":
                    if($inst_args_num != 2){
                        throw new XMLStructureException;
                    }
                    if($inst_args[0]->type != "var" || $inst_args[1]->type != "type" || ($inst_args[1]->value != "int" && $inst_args[1]->value != "string" && $inst_args[1]->value != "bool")){
                        throw new XMLStructureException;
                    }
                    break;
                    
                // <var> <symb1> <symb2>
                case "ADD":
                case "SUB":
                case "MUL":
                case "IDIV":
                case "LT":
                case "GT":
                case "EQ":
                case "AND":
                case "OR":
                case "STRI2INT":
                case "CONCAT":
                case "GETCHAR":
                case "SETCHAR":
                    if($inst_args_num != 3){
                        throw new XMLStructureException;
                    }
                    if($inst_args[0]->type != "var" || preg_match($pattern_var_value, $inst_args[0]->value) !== 1){
                        throw new XMLStructureException;
                    }
                    if(preg_match($pattern_symb, $inst_args[1]->type) !== 1 || preg_match($pattern_symb_value, $inst_args[1]->value) !== 1 ||
                        preg_match($pattern_symb, $inst_args[2]->type) !== 1 || preg_match($pattern_symb_value, $inst_args[2]->value) !== 1){
                        throw new XMLStructureException;
                    }
                    break;

                // <label> <symb1> <symb2>
                case "JUMPIFEQ":
                case "JUMPIFNEQ":
                    if($inst_args_num != 3){
                        throw new XMLStructureException;
                    }
                    if($inst_args[0]->type != "label" || preg_match($pattern_label_value, $inst_args[0]->value) !== 1){
                        throw new XMLStructureException();
                    }
                    if(preg_match($pattern_symb, $inst_args[1]->type) !== 1 || preg_match($pattern_symb_value, $inst_args[1]->value) !== 1 ||
                        preg_match($pattern_symb, $inst_args[2]->type) !== 1 || preg_match($pattern_symb_value, $inst_args[2]->value) !== 1){
                        throw new XMLStructureException;
                    }
                    break; 
                
                case "DPRINT":
                case "BREAK":
                    break;
                default:
                    throw new XMLStructureException;
            }
        }
    }
}