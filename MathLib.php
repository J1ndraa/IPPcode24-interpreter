<?php
/**
 * IPP - PHP Project Student
 * @author Halva Jindřich (xhalva05)
 * @file MathLib.php
 * @brief Class for mathematic operations... arithmetics, logic, etc.
 * @date 2024-04-13
 */

namespace IPP\Student;

/**
 * @brief Class for mathematic operations... arithmetics, logic, etc.
 */
class MathLib{
    /** @var array<mixed, mixed> */
    public array $GF;  //global frame
    /** @var array<mixed, mixed> */
    public array $TF;  //temporary frame
    /** @var array<mixed, mixed> */
    public array $LF;  //local frame

    /**
     * @brief Constructor for MathLib
     * @param array<array<mixed, mixed>> $frames array of frames... on index one is GF, on index 2 is TF, on index 3 is LF
     */
    public function __construct($frames){
        $this->GF = $frames[0];
        $this->TF = $frames[1];
        $this->LF = $frames[2];
    }

    /**
     * @brief Method for mathematic operations
     * @param string $inst_name name of instruction
     * @param mixed $var destination variable
     * @param mixed $symb1 symbol 1
     * @param mixed $symb2 symbol 2
     * @return array<array<mixed, string>> updated frames
     */
    public function MathOperation($inst_name, $var, $symb1, $symb2){
        $split_var = explode("@", $var->value);
        $result = array();
        //check if destination variable exists
        $this->VarDefined($split_var);

        //1. check if operands are variables or constants
        //2. get data type and value of operands
        //3. compute result of operation
        //4. write data to destination variable
        if($symb1->type === "var" && $symb2->type === "var"){ //both operands are variables
            $split_symb1 = explode("@", $symb1->value);
            $split_symb2 = explode("@", $symb2->value);
            $this->VarDefined($split_symb1);
            $this->VarDefined($split_symb2);
            $symb1_type = $this->GetVarType($split_symb1);
            $symb2_type = $this->GetVarType($split_symb2);
            $symb1_value = $this->GetVarValue($split_symb1);
            $symb2_value = $this->GetVarValue($split_symb2);
            if($symb1_value === null || $symb2_value === null){ //uninitialized variable
                throw new SemException;
            }
            $result = $this->ComputeOperation($inst_name, $symb1_type, $symb2_type, $symb1_value, $symb2_value);
        }
        else if($symb1->type !== "var" && $symb2->type === "var"){ //first operand is constant, second is variable
            $split_symb2 = explode("@", $symb2->value);
            $this->VarDefined($split_symb2);
            $symb1_type = $symb1->type;
            $symb2_type = $this->GetVarType($split_symb2);
            $symb2_value = $this->GetVarValue($split_symb2);
            if($symb2_value === null){ //uninitialized variable
                throw new SemException;
            }
            $result = $this->ComputeOperation($inst_name, $symb1_type, $symb2_type, $symb1->value, $symb2_value);
        }
        else if($symb1->type === "var" && $symb2->type !== "var"){ //first operand is variable, second is constant
            $split_symb1 = explode("@", $symb1->value);
            $this->VarDefined($split_symb1);
            $symb1_type = $this->GetVarType($split_symb1);
            $symb2_type = $symb2->type;
            $symb1_value = $this->GetVarValue($split_symb1);
            if($symb1_value === null){ //uninitialized variable
                throw new SemException;
            }
            $result = $this->ComputeOperation($inst_name, $symb1_type, $symb2_type, $symb1_value, $symb2->value);
        }
        else if($symb1->type !== "var" && $symb2->type !== "var"){ //both operands are constants
            $result = $this->ComputeOperation($inst_name, $symb1->type, $symb2->type, $symb1->value, $symb2->value);
        }

        //write data to destination variable
        if($split_var[0] === "GF"){
            $this->GF[$split_var[1]][0] = $result[0];
            $this->GF[$split_var[1]][1] = $result[1];}
        else if($split_var[0] === "TF"){
            $this->TF[$split_var[1]][0] = $result[0];
            $this->TF[$split_var[1]][1] = $result[1];}
        else if($split_var[0] === "LF"){
            $this->LF[$split_var[1]][0] = $result[0];
            $this->LF[$split_var[1]][1] = $result[1];}

        //return updated frames
        return array($this->GF, $this->TF, $this->LF);
    } 

    /**
     * @brief Method for computing result of operations
     * @param string $inst_name name of instruction
     * @param string $symb1_type type of symbol 1
     * @param string $symb2_type type of symbol 2
     * @param mixed $symb1_value value of symbol 1
     * @param mixed $symb2_value value of symbol 2
     * @return mixed result of operation
     */
    public function ComputeOperation($inst_name, $symb1_type, $symb2_type, $symb1_value, $symb2_value){
        if($inst_name === "EQ" && ($symb1_type === "nil" || $symb2_type === "nil")){
            if($symb1_type === $symb2_type){
                return array(true, "bool");
            }
            else{
                return array(false, "bool");
            }
        }
        else if($inst_name === "ADD" || $inst_name === "SUB" || $inst_name === "MUL" || $inst_name === "IDIV"){
            if($symb1_type !== "int" || $symb2_type !== "int"){ //operand types are not integers
                throw new OperandTypeException;
            }
            return array($this->ArithmeticOperation($inst_name, $symb1_value, $symb2_value), "int");
        }
        else if($inst_name === "LT" || $inst_name === "GT" || $inst_name === "EQ"){
            if($symb1_type !== $symb2_type){ //operand types are not the same
                throw new OperandTypeException;
            }
            if($symb1_type !== "int" && $symb1_type !== "bool" && $symb1_type !== "string"){ //operand types are not integers, booleans or strings
                throw new OperandTypeException;
            }
            return array($this->RelationOperation($inst_name, $symb1_value, $symb2_value, $symb1_type), "bool");
        }
        else if($inst_name === "AND" || $inst_name === "OR"){
            if($symb1_type !== "bool" || $symb2_type !== "bool"){ //operand types are not booleans
                throw new OperandTypeException;
            }
            return array($this->LogicOperation($inst_name, $symb1_value, $symb2_value), "bool");
        }
        else if($inst_name === "STRI2INT"){
            if($symb1_type !== "string" || $symb2_type !== "int"){ //operand types are not string and integer
                throw new OperandTypeException;
            }
            if(strlen($symb1_value) <= $symb2_value || $symb2_value < 0){ //index out of range
                throw new StringOperationException;
            }
            return array(mb_ord(substr($symb1_value, $symb2_value, 1), "UTF-8"), "int");
        }
    }

    /**
     * @brief Method for checking if variable is defined
     * @param mixed $split variable to check
     * @return void 
     */
    public function VarDefined($split){
        if($split[0] === "GF"){
            if(!array_key_exists($split[1], $this->GF)){
                throw new UnknownVarException;}}
        else if($split[0] === "TF"){
            if(!array_key_exists($split, $this->TF)){
                throw new UnknownVarException;}}
        else if($split[0] === "LF"){
            if(!array_key_exists($split[1], $this->LF)){
                throw new UnknownVarException;}}
    }

    /**
     * @brief Method for getting data type of variable
     * @param mixed $split variable to check
     * @return string data type of variable
     */
    public function GetVarType($split){
        if($split[0] === "GF"){
            return $this->GF[$split[1]][1];}
        else if($split[0] === "TF"){
            return $this->TF[$split[1]][1];}
        else if($split[0] === "LF"){
            return $this->LF[$split[1]][1];}
        return ""; //cannot happen
    }
    /**
     * @brief Method for getting value of variable
     * @param mixed $split variable to check
     * @return mixed value of variable
     */
    public function GetVarValue($split){
        if($split[0] === "GF"){
            if($this->GF[$split[1]][0] === "bool"){
                if($this->GF[$split[1]][1] === true){
                    return "true";}
                else{
                    return "false";}}
            return $this->GF[$split[1]][0];}
        else if($split[0] === "TF"){
            if($this->TF[$split[1]][0] === "bool"){
                if($this->GF[$split[1]][1] === true){
                    return "true";}
                else{
                    return "false";}}
            return $this->TF[$split[1]][0];}
        else if($split[0] === "LF"){
            if($this->LF[$split[1]][0] === "bool"){
                if($this->GF[$split[1]][1] === true){
                    return "true";}
                else{
                    return "false";}}
            return $this->LF[$split[1]][0];}
    }

    /**
     * @brief Method for computing result of arithmetic operations
     * @param string $inst_name name of instruction
     * @param int $symb1_value value of symbol 1
     * @param mixed $symb2_value value of symbol 2
     * @return int result of arithmetic operation
     */
    public function ArithmeticOperation($inst_name, $symb1_value, $symb2_value){
        if($inst_name === "ADD"){
            return $symb1_value + $symb2_value;}
        else if($inst_name === "SUB"){
            return $symb1_value - $symb2_value;}
        else if($inst_name === "MUL"){
            return $symb1_value * $symb2_value;}
        else if($inst_name === "IDIV"){
            if($symb2_value === "0"){ //division by zero
                throw new OperandValException;
            }
            return intdiv($symb1_value, $symb2_value);}
        return 0;
    }

    /**
     * @brief Method for computing result of relation operations
     * @param string $inst_name name of instruction
     * @param mixed $symb1_value value of symbol 1
     * @param mixed $symb2_value value of symbol 2
     * @param string $type type of symbols
     * @return bool result of relation operation
     */
    public function RelationOperation($inst_name, $symb1_value, $symb2_value, $type){
        if($type === "int"){
            if($inst_name === "LT"){
                return $symb1_value < $symb2_value;}
            else if($inst_name === "GT"){
                return $symb1_value > $symb2_value;}
            else if($inst_name === "EQ"){
                return $symb1_value === $symb2_value;}
        }
        else if($type === "bool"){ //false is less than true
            if($inst_name === "LT"){
                if($symb1_value === "false" && $symb2_value === "true"){
                    return true;}
                else{
                    return false;}}
            else if($inst_name === "GT"){
                if($symb1_value === "true" && $symb2_value === "false"){
                    return true;}
                else{
                    return false;}}
            else if($inst_name === "EQ"){
                if($symb1_value === $symb2_value){
                    return true;}
                else{
                    return false;}
            }
        }
        else if($type === "string"){
            if($inst_name === "LT"){
                $strcmp = strcmp($symb1_value, $symb2_value);
                if($strcmp < 0){
                    return true;}
                else{
                    return false;}}
            else if($inst_name === "GT"){
                $strcmp = strcmp($symb1_value, $symb2_value);
                if($strcmp > 0){
                    return true;}
                else{
                    return false;}}
            else if($inst_name === "EQ"){
                $strcmp = strcmp($symb1_value, $symb2_value);
                if($strcmp === 0){
                    return true;}
                else{
                    return false;}
            }
        }
        return false;
    }

    /**
     * @brief Method for computing result of logic operations
     * @param string $inst_name name of instruction
     * @param string $symb1_value value of symbol 1
     * @param string $symb2_value value of symbol 2
     * @return bool result of logic operation
     */
    public function LogicOperation($inst_name, $symb1_value, $symb2_value){
        if($inst_name === "AND"){
            if($symb1_value === "true" && $symb2_value === "true"){    
                return true;}
            else{
                return false;}
        }
        else if($inst_name === "OR"){
            if($symb1_value === "true" || $symb2_value === "true"){    
                return true;}
            else{
                return false;}}
        return false;
    }
}//end of class


?>