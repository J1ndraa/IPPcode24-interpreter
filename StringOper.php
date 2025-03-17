<?php
/**
 * IPP - PHP Project Student
 * @author Halva Jindřich (xhalva05)
 * @file StringOper.php
 * @brief Class for string operations as Concat, getchar and setchar
 * @date 2024-04-14
 */

namespace IPP\Student;

/**
 * @brief 
 */
class StringOper{
    /** @var array<mixed, string> */
    public array $GF;  //global frame
    /** @var array<mixed, string> */
    public array $TF;  //temporary frame
    /** @var array<mixed, string> */
    public array $LF;  //local frame

    /**
     * @brief Constructor for StringOper
     * @param array<array<mixed, string>> $frames array of frames
     */
    public function __construct(array $frames){
        $this->GF = $frames[0];
        $this->TF = $frames[1];
        $this->LF = $frames[2];
    }

    /**
     * @brief Method for string operations
     * @param string $inst_name name of instruction
     * @param mixed $var destination variable
     * @param mixed $symb1 symbol 1
     * @param mixed $symb2 symbol 2
     * @return array<array<mixed, string>> updated frames 
     */
    public function StringOperation($inst_name, $var, $symb1, $symb2){
        $split_var = explode("@", $var->value);
        $result = array();
        //check if destination variable exists
        $this->VarDefined($split_var);

        //check if operands are variables or constants
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
            $result = $this->ComputeStringOperation($inst_name, $var, $symb1_type, $symb2_type, $symb1_value, $symb2_value);

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
            $result = $this->ComputeStringOperation($inst_name, $var, $symb1_type, $symb2_type, $symb1->value, $symb2_value);
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
            $result = $this->ComputeStringOperation($inst_name, $var, $symb1_type, $symb2_type, $symb1_value, $symb2->value);
        }
        else if($symb1->type !== "var" && $symb2->type !== "var"){ //both operands are constants
            $result = $this->ComputeStringOperation($inst_name, $var, $symb1->type, $symb2->type, $symb1->value, $symb2->value);
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
     * @brief Method for handling string operations
     * @param string $inst_name name of instruction
     * @param mixed $var destination variable
     * @param string $symb1_type type of symbol 1
     * @param string $symb2_type type of symbol 2
     * @param mixed $symb1_value value of symbol 1
     * @param mixed $symb2_value value of symbol 2
     * @return array<mixed, string> with result of operation [value, type]
     */
    public function ComputeStringOperation($inst_name, $var, $symb1_type, $symb2_type, $symb1_value, $symb2_value){
        if($inst_name === "GETCHAR"){ //get character from string
            if($symb1_type !== "string" || $symb2_type !== "int"){
                throw new OperandTypeException;
            }
            else{
                if($symb2_value < 0 || $symb2_value >= strlen($symb1_value)){
                    throw new StringOperationException;
                }
                else{
                    return array($symb1_value[$symb2_value], "string");
                }
            }
        }
        else if($inst_name === "SETCHAR"){ //replace character in a string    
            $split_var = explode("@", $var->value);
            $var_type = $this->GetVarType($split_var);
            $var_value = $this->GetVarValue($split_var);
            if($var_type !== "string" || $symb1_type !== "int" || $symb2_type !== "string"){
                throw new OperandTypeException;
            }
            else{
                if($symb1_value < 0 || $symb1_value >= strlen($var_value)){
                    throw new StringOperationException;
                }
                else{
                    $var_value[$symb1_value] = $symb2_value;
                    return array($var_value, "string");
                }
            }
        }
        else if($inst_name === "CONCAT"){ //concatenate two strings
            if($symb1_type !== "string" || $symb2_type !== "string"){
                throw new OperandTypeException;
            }
            else{
                return array($symb1_value.$symb2_value, "string");
            }
        }
        //cannot happen, just for PHPStan
        return array("", "");
    }


    /**
     * @brief Method for checking if variable is defined
     * @param array<string> $split variable split into frame and name
     * @return void
     */
    public function VarDefined($split){
        if($split[0] === "GF"){
            if(!array_key_exists($split[1], $this->GF)){
                throw new UnknownVarException;}}
        else if($split[0] === "TF"){
            if(!array_key_exists($split[1], $this->TF)){
                throw new UnknownVarException;}}
        else if($split[0] === "LF"){
            if(!array_key_exists($split[1], $this->LF)){
                throw new UnknownVarException;}}
    }

    /**
     * @brief Method for getting data type of variable
     * @param array<mixed, string> $split variable split into frame and name 
     * @return string data type of variable
     */
    public function GetVarType($split){
        if($split[0] === "GF"){
            return $this->GF[$split[1]][1];}
        else if($split[0] === "TF"){
            return $this->TF[$split[1]][1];}
        else if($split[0] === "LF"){
            return $this->LF[$split[1]][1];}
        else return ""; //cannot happen
    }
    /**
     * @brief Method for getting value of variable
     * @param array<mixed, string> $split variable split into frame and name
     * @return mixed value of variable
     */
    public function GetVarValue($split){
        if($split[0] === "GF"){
            return $this->GF[$split[1]][0];}
        else if($split[0] === "TF"){
            return $this->TF[$split[1]][0];}
        else if($split[0] === "LF"){
            return $this->LF[$split[1]][0];}
        else return ""; //cannot happen
    }
}//end of class StringOper
?>