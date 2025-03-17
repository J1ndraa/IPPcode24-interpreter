<?php
/**
 * IPP - PHP Project Student
 * @author Halva Jindřich (xhalva05)
 * @file Interpreter.php
 * @brief Main class for interpreting XML code
 * @date 2024-04-08
 */

 namespace IPP\Student;

 use IPP\Core\AbstractInterpreter;
 use IPP\Core\Exception\NotImplementedException;
 use IPP\Core\Exception\XMLException;
 use IPP\Core\Interface\SourceReader;
 use IPP\Core\Settings;

/**
* @brief Main class for interpreting XML code
*/
class Interpreter extends AbstractInterpreter
{
    /** @var array<mixed> */
    public $FRAME_STACK = array(); //frame stack for local frames
    /** @var array<mixed> */
    public $GF = array();          //global frame
    /** @var array<mixed> */
    public $TF = array();          //temporary frame
    public bool $TF_set = false;   //if temporary frame is set
    /** @var array<mixed> */
    public $DATA_STACK = array();  //data stack
    /** @var array<int> */
    public $CALL_STACK = array();  //call stack
    /** @var array<string, int> */
    public $LABEL_STACK = array(); //label stack

    /**
     * @brief Main function for interpreting XML code
     * @return int Return code
     */
    public function execute(): int
    {
        //get DOM document
        $dom = $this->source->getDOMDocument();

        //check xml structure
        $Xml_check = new XMLCheck();
        $Program = new Program("");
        $Program = $Xml_check->checkXML($dom);
        
        //check format of instructions and arguments
        $Xml_check_2 = new CheckXMLInstructions();
        $Xml_check_2->XMLInstCheck($Program);

        //get label map
        $Label_map = new LabelMap($Program);
        $this->LABEL_STACK = $Label_map->GetLabelMap();

        //start interpreting
        return $this->StartInterpret($Program);
    }

    /**
     * @brief Interpreting program
     * @param mixed $Program - XML program
     */
    protected function StartInterpret($Program) : int{
        $inst_count = count($Program->instructions);
        //loop through all instructions
        for($act_inst_num = 0; $act_inst_num < $inst_count; $act_inst_num++){
            $inst = $Program->instructions[$act_inst_num];
            $inst_name = strtoupper($inst->opcode);
            $inst_order = $inst->order;
            $inst_args = $inst->args;

            switch($inst_name){
                
                //no arguments -----------------------------------------------------
                case "CREATEFRAME": //define new temporary frame
                    $this->TF = array();
                    $this->TF_set = true;
                    break;
                case "PUSHFRAME": //new local frame on FRAME_STACK, undefine TF
                    if($this->TF_set){
                        array_push($this->FRAME_STACK, $this->TF);
                        $this->TF_set = false;
                    }
                    else{
                        throw new FrameAccessException;
                    }
                    break;
                case "POPFRAME": //pop local frame from FRAME_STACK
                    if(empty($this->FRAME_STACK)){
                        throw new FrameAccessException;
                    }
                    else{
                        $this->TF = array_pop($this->FRAME_STACK);
                        $this->TF_set = true;
                    }
                    break;
                case "RETURN":
                    if(empty($this->CALL_STACK)){
                        throw new ValueException;
                    }
                    else{
                        $act_inst_num = array_pop($this->CALL_STACK);
                    }
                    break;

                // <var> -----------------------------------------------------------
                case "DEFVAR":
                    $split = explode("@", $inst_args[0]->value);
                    if($split[0] === "GF" ){ //global frame variable
                        $this->ValidDefvar($split, "GF");
                    }
                    else if($split[0] === "TF" ){ //temporary frame variable
                        $this->ValidDefvar($split, "TF");
                    }
                    else if($split[0] === "LF" ){ //local frame variable
                        $this->ValidDefvar($split, "LF");
                    }
                    else{
                        throw new XMLStructureException;
                    }
                    break;
                case "POPS" :
                    if(count($this->DATA_STACK) !== 0){
                        $last_item = array_pop($this->DATA_STACK);
                        $split = explode("@", $inst_args[0]->value);
                        $this->IsVarDefined($split);
                        if($split[0] === "GF" ){
                            $this->GF[$split[1]][0] = $last_item[0];
                            $this->GF[$split[1]][1] = $last_item[1];
                        }
                        else if($split[0] === "TF" ){
                            $this->TF[$split[1]][0] = $last_item[0];
                            $this->TF[$split[1]][1] = $last_item[1];
                        }
                        else if($split[0] === "LF" ){
                            $last_index = count($this->FRAME_STACK) - 1;
                            $this->FRAME_STACK[$last_index][$split[1]][0] = $last_item[0];
                            $this->FRAME_STACK[$last_index][$split[1]][1] = $last_item[1];
                        }
                    }
                    else{
                        throw new ValueException;
                    }
                    break;

                // <symb> ----------------------------------------------------------
                case "EXIT":
                    if($inst_args[0]->type !== "int"){
                        throw new OperandTypeException;
                    }
                    if($inst_args[0]->value >= 0 && $inst_args[0]->value <= 9){
                        return $inst_args[0]->value;
                    }
                    else{
                        throw new OperandValException;
                    }
                case "WRITE":
                    if(($inst_args[0]->type === "var" && $inst_args[0]->value === "nil@nil") || ($inst_args[0]->type === "nil")){
                        $this->stdout->writeString("");
                        break;
                    }
                    else if($inst_args[0]->type === "string"){
                        $this->ReplaceString($inst_args[0]->value);
                        break;
                    }
                    else if($inst_args[0]->type === "var"){
                        $split = explode("@", $inst_args[0]->value);
                        if($split[0] === "GF" ){
                            $this->WriterVar($split, $this->GF);
                            break;
                        }
                        else if($split[0] === "TF" ){
                            $this->WriterVar($split, $this->TF);
                            break;
                        }
                        else if($split[0] === "LF" ){
                            if(empty($this->FRAME_STACK)){
                                throw new FrameAccessException;
                            }
                            $this->WriterVar($split, end($this->FRAME_STACK));
                            break;
                        }
                    }
                    else if($inst_args[0]->type === "bool"){
                        if($inst_args[0]->value == "true"){
                            $this->stdout->writeString("true");
                        }
                        else{
                            $this->stdout->writeString("false");
                        }
                        break;
                    }
                    $this->stdout->writeString($inst_args[0]->value);
                    break;
                case "PUSHS":
                    if($inst_args[0]->type === "var"){ //variable's gonna be pushed
                        $split = explode("@", $inst_args[0]->value);
                        $this->IsVarDefined($split);
                        if($split[0] === "GF" ){
                            $this->DATA_STACK = $this->PushsVar($split, $this->GF, $this->DATA_STACK);
                        }
                        else if($split[0] === "TF" ){
                            if(!$this->TF_set){
                                throw new FrameAccessException;
                            }
                            $this->DATA_STACK = $this->PushsVar($split, $this->TF, $this->DATA_STACK);
                        }
                        else if($split[0] === "LF" ){
                            if(empty($this->FRAME_STACK)){
                                throw new FrameAccessException;
                            }
                            $last_index = count($this->FRAME_STACK) - 1;
                            $this->DATA_STACK = $this->PushsVar($split, $this->FRAME_STACK[$last_index], $this->DATA_STACK);
                        }
                    }
                    else{ //constant
                        if($inst_args[0]->type === "bool"){
                            if($inst_args[0]->value == "true"){
                                $this->DATA_STACK[] = array(true, "bool");
                            }
                            else{
                                $this->DATA_STACK[] = array(false, "bool");
                            }
                        }
                        else{
                            $this->DATA_STACK[] = array($inst_args[0]->value, $inst_args[0]->type);
                        }
                    }
                    break;

                // <label> ---------------------------------------------------------
                case "CALL":
                    $this->CALL_STACK[] = $act_inst_num;
                    if(array_key_exists($inst_args[0]->value, $this->LABEL_STACK)){
                        $act_inst_num = $this->LABEL_STACK[$inst_args[0]->value];
                    }
                    else{
                        throw new SemException;
                    }
                    break;
                case "LABEL": //label is solved in extern file "LabelMap.php"
                    break;
                case "JUMP":
                    if(array_key_exists($inst_args[0]->value, $this->LABEL_STACK)){
                        $act_inst_num = $this->LABEL_STACK[$inst_args[0]->value];
                    }
                    else{
                        throw new SemException;
                    }
                    break;

                // <var> <symb> ----------------------------------------------------
                case "MOVE":
                    $split = explode("@", $inst_args[0]->value);
                    if($split[0] === "GF" ){ //global frame destination variable
                        $this->GF = $this->MoveFunction($inst_args, $split, $this->GF);
                    }                   
                    else if($split[0] === "TF"){ //temporary frame destination variable
                        if(!$this->TF_set){
                            throw new FrameAccessException;
                        }
                        $this->TF = $this->MoveFunction($inst_args, $split, $this->TF);
                    }
                    else if($split[0] === "LF"){ //local frame destination variable
                        if(empty($this->FRAME_STACK)){
                            throw new FrameAccessException;
                        }
                        $last_index = count($this->FRAME_STACK) - 1;
                        $this->FRAME_STACK[$last_index] = $this->MoveFunction($inst_args, $split, $this->FRAME_STACK[$last_index]);
                    }
                    else{
                        throw new XMLStructureException;
                    }
                    break;
                case "INT2CHAR":
                    throw new NotImplementedException;
                case "NOT":
                    $split_dest = explode("@", $inst_args[0]->value);
                    $this->IsVarDefined($split_dest);
                    //source variable ---------------------------------------------------------------
                    if($inst_args[1]->type === "var"){ //symbol is variable
                        $split = explode("@", $inst_args[1]->value);
                        $this->IsVarDefined($split);
                        if($split[0] === "GF" ){
                            $this->GF = $this->NotFuncForVar($split_dest, $split, $this->GF);
                        }
                        else if($split[0] === "TF" ){
                            if($this->TF[$split[1]][1] !== "bool"){
                                throw new OperandTypeException;
                            }
                            $this->TF = $this->NotFuncForVar($split_dest, $split, $this->TF);
                        }
                        else if($split[0] === "LF" ){
                            $last_index = count($this->FRAME_STACK) - 1;
                            if($this->FRAME_STACK[$last_index][$split[1]][1] !== "bool"){
                                throw new OperandTypeException;
                            }
                            $this->FRAME_STACK[$last_index] = $this->NotFuncForVar($split_dest, $split, $this->FRAME_STACK[$last_index]);
                        }
                    //-------------------------------------------------------------------------------
                    }
                    else if($inst_args[1]->type === "bool"){ //constant is bool
                        if($inst_args[1]->value == "true"){
                            $inst_args[1]->value = false;
                        }
                        else{
                            $inst_args[1]->value = true;
                        }
                        $this->GF[$split_dest[1]][0] = $inst_args[1]->value;
                        $this->GF[$split_dest[1]][1] = "bool";
                    }
                    else{
                        throw new OperandTypeException;
                    }
                    break;
                case "STRLEN":
                    $split = explode("@", $inst_args[0]->value);
                    if($split[0] === "GF" ){
                        $this->GF = $this->StrlenFunction($inst_args, $split, $this->GF);
                    }
                    else if($split[0] === "TF" ){
                        if(!$this->TF_set){
                            throw new FrameAccessException;
                        }
                        $this->TF = $this->StrlenFunction($inst_args, $split, $this->TF);
                    }
                    else if($split[0] === "LF" ){
                        if(empty($this->FRAME_STACK)){ //local frame is empty
                            throw new FrameAccessException;
                        }
                        $last_index = count($this->FRAME_STACK) - 1;
                        $this->FRAME_STACK[$last_index] = $this->StrlenFunction($inst_args, $split,  $this->FRAME_STACK[$last_index]);
                    }

                    break;
                case "TYPE":
                    $split = explode("@", $inst_args[0]->value);
                    $this->IsVarDefined($split);
                    $type = $inst_args[1]->type; //if symb is constant, $type variable wont change anymore

                    if($inst_args[1]->type === "var"){ //symb is variable
                        $split_2 = explode("@", $inst_args[1]->value);
                        $this->IsVarDefined($split_2);
                        if($split_2[0] === "GF" ){
                            $type = $this->GF[$split_2[1]][1];
                        }
                        else if($split_2[0] === "TF" ){
                            $type = $this->TF[$split_2[1]][1];
                        }
                        else if($split_2[0] === "LF" ){
                            $last_index = count($this->FRAME_STACK) - 1;
                            $type = $this->FRAME_STACK[$last_index][$split_2[1]][1];
                        }
                        if($type === null){
                            $type = "";
                        }
                    }

                    if($split[0] === "GF" ){
                        $this->GF[$split[1]][0] = $type;
                        $this->GF[$split[1]][1] = "type";
                    }
                    else if($split[0] === "TF" ){

                        $this->TF[$split[1]][0] = $type;
                        $this->TF[$split[1]][1] = "type";
                    }
                    else if($split[0] === "LF" ){
                        $last_index = count($this->FRAME_STACK) - 1;
                        $this->FRAME_STACK[$last_index][$split[1]][0] = $type;
                        $this->FRAME_STACK[$last_index][$split[1]][1] = "type";
                    }
                    break;

                // <var> <type> ----------------------------------------------------
                case "READ":
                    $split = explode("@", $inst_args[0]->value);
                    if($split[0] === "GF"){
                        $this->GF = $this->ReadFunction($inst_args, $split, $this->GF);
                    }
                    else if($split[0] === "TF"){
                        if(!$this->TF_set){
                            throw new FrameAccessException;
                        }
                        $this->TF = $this->ReadFunction($inst_args, $split, $this->TF);
                    }
                    else if($split[0] === "LF"){
                        if(empty($this->FRAME_STACK)){ //local frame is empty
                            throw new FrameAccessException;
                        }
                        $last_index = count($this->FRAME_STACK) - 1;
                        $this->FRAME_STACK[$last_index] = $this->ReadFunction($inst_args, $split, $this->FRAME_STACK[$last_index]);
                    }
                    break;

                // <var> <symb1> <symb2> -------------------------------------------
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
                    $last_index = count($this->FRAME_STACK) - 1;
                    if(empty($this->FRAME_STACK)){
                        $last_index = count($this->FRAME_STACK);
                        $frames_arr = array($this->GF, $this->TF, array());
                    }
                    else{
                        $frames_arr = array($this->GF, $this->TF, $this->FRAME_STACK[$last_index]);
                    }
                    $Math_oper = new MathLib($frames_arr);
                    $frames_arr = $Math_oper->MathOperation($inst_name, $inst_args[0], $inst_args[1], $inst_args[2]);
                    $this->GF = $frames_arr[0];
                    $this->TF = $frames_arr[1];
                    if(!empty($this->FRAME_STACK)){
                        $this->FRAME_STACK[$last_index] = $frames_arr[2];
                    }
                    break;
                case "CONCAT":
                case "GETCHAR":
                case "SETCHAR":
                    $last_index = count($this->FRAME_STACK) - 1;
                    if(empty($this->FRAME_STACK)){
                        $last_index = count($this->FRAME_STACK);
                        $frames_arr = array($this->GF, $this->TF, array());
                    }
                    else{
                        $frames_arr = array($this->GF, $this->TF, $this->FRAME_STACK[$last_index]);
                    }
                    $String_oper = new StringOper($frames_arr);
                    $frames_arr = $String_oper->StringOperation($inst_name, $inst_args[0], $inst_args[1], $inst_args[2]);
                    $this->GF = $frames_arr[0];
                    $this->TF = $frames_arr[1];
                    if(!empty($this->FRAME_STACK)){
                        $this->FRAME_STACK[$last_index] = $frames_arr[2];
                    }
                    break;

                // <label> <symb1> <symb2> -----------------------------------------
                case "JUMPIFEQ":
                case "JUMPIFNEQ":
                    $equal = false;
                    if($inst_name === "JUMPIFEQ"){
                        $equal = true;
                    }
                    $split_1 = explode("@", $inst_args[1]->value);
                    $split_2 = explode("@", $inst_args[2]->value);

                    if($split_1[0] === "LF" || $split_2[0] === "LF"){ //if variables are in local frame, check if frame stack is empty
                        if(empty($this->FRAME_STACK)){
                            throw new FrameAccessException;
                        }
                    }
                    $last_index = count($this->FRAME_STACK) - 1;

                    if($inst_args[1]->type === "var" && $inst_args[2]->type === "var"){ //both operands are variables
                        //every combination of frames
                        if($split_1[0] === "GF" && $split_2[0] === "GF" && array_key_exists($split_1[1], $this->GF) && array_key_exists($split_2[1], $this->GF)){
                            $act_inst_num = $this->JumpIfEqVarCompar($inst_args, $split_1, $split_2, $act_inst_num, $this->GF, $this->GF, $equal);
                        }
                        else if($split_1[0] === "GF" && $split_2[0] === "TF" && array_key_exists($split_1[1], $this->GF) && array_key_exists($split_2[1], $this->TF)){
                            $act_inst_num = $this->JumpIfEqVarCompar($inst_args, $split_1, $split_2, $act_inst_num, $this->GF, $this->TF, $equal);
                        }
                        else if($split_1[0] === "GF" && $split_2[0] === "LF" && array_key_exists($split_1[1], $this->GF) && array_key_exists($split_2[1], $this->FRAME_STACK[$last_index])){
                            $act_inst_num = $this->JumpIfEqVarCompar($inst_args, $split_1, $split_2, $act_inst_num, $this->GF, $this->FRAME_STACK[$last_index],$equal);
                        }
                        else if($split_1[0] === "TF" && $split_2[0] === "GF" && array_key_exists($split_1[1], $this->TF) && array_key_exists($split_2[1], $this->GF)){
                            $act_inst_num = $this->JumpIfEqVarCompar($inst_args, $split_1, $split_2, $act_inst_num, $this->TF, $this->GF, $equal);
                        }
                        else if($split_1[0] === "TF" && $split_2[0] === "TF" && array_key_exists($split_1[1], $this->TF) && array_key_exists($split_2[1], $this->TF)){
                            $act_inst_num = $this->JumpIfEqVarCompar($inst_args, $split_1, $split_2, $act_inst_num, $this->TF, $this->TF, $equal);
                        }
                        else if($split_1[0] === "TF" && $split_2[0] === "LF" && array_key_exists($split_1[1], $this->TF) && array_key_exists($split_2[1], $this->FRAME_STACK[$last_index])){
                            $act_inst_num = $this->JumpIfEqVarCompar($inst_args, $split_1, $split_2, $act_inst_num, $this->TF, $this->FRAME_STACK[$last_index], $equal);
                        }
                        else if($split_1[0] === "LF" && $split_2[0] === "GF" && array_key_exists($split_1[1], $this->FRAME_STACK[$last_index]) && array_key_exists($split_2[1], $this->GF)){
                            $act_inst_num = $this->JumpIfEqVarCompar($inst_args, $split_1, $split_2, $act_inst_num, $this->FRAME_STACK[$last_index], $this->GF, $equal);
                        }
                        else if($split_1[0] === "LF" && $split_2[0] === "TF" && array_key_exists($split_1[1], $this->FRAME_STACK[$last_index]) && array_key_exists($split_2[1], $this->TF)){
                            $act_inst_num = $this->JumpIfEqVarCompar($inst_args, $split_1, $split_2, $act_inst_num, $this->FRAME_STACK[$last_index], $this->TF, $equal);
                        }
                        else if($split_1[0] === "LF" && $split_2[0] === "LF" && array_key_exists($split_1[1], $this->FRAME_STACK[$last_index]) && array_key_exists($split_2[1], $this->FRAME_STACK[$last_index])){
                            $act_inst_num = $this->JumpIfEqVarCompar($inst_args, $split_1, $split_2, $act_inst_num, $this->FRAME_STACK[$last_index], $this->FRAME_STACK[$last_index], $equal);
                        }
                    }
                    else if($inst_args[1]->type === "var" && $inst_args[2]->type !== "var"){ //first operand is variable, second is constant
                        if($split_1[0] === "GF"){
                            $act_inst_num = $this->JumpIfEqVarSymb($inst_args, $split_1, $act_inst_num, $this->GF, $equal);
                        }
                        else if($split_1[0] === "TF"){
                            $act_inst_num = $this->JumpIfEqVarSymb($inst_args, $split_1, $act_inst_num, $this->TF, $equal);
                        }
                        else if($split_1[0] === "LF"){
                            $act_inst_num = $this->JumpIfEqVarSymb($inst_args, $split_1, $act_inst_num, $this->FRAME_STACK[$last_index], $equal);
                        }
                    }
                    else if($inst_args[1]->type !== "var" && $inst_args[2]->type === "var"){ //first operand is constant, second is variable
                        if($split_2[0] === "GF"){
                            $act_inst_num = $this->JumpIfEqSymbVar($inst_args, $split_2, $act_inst_num, $this->GF, $equal);
                        }
                        else if($split_2[0] === "TF"){
                            $act_inst_num = $this->JumpIfEqSymbVar($inst_args, $split_2, $act_inst_num, $this->TF, $equal);
                        }
                        else if($split_2[0] === "LF"){
                            $act_inst_num = $this->JumpIfEqSymbVar($inst_args, $split_2, $act_inst_num, $this->FRAME_STACK[$last_index], $equal);
                        }
                    }
                    else if($inst_args[1]->type !== "var" && $inst_args[2]->type !== "var"){
                        if($inst_args[1]->type === $inst_args[2]->type){
                            if($equal){
                                if($inst_args[1]->value == $inst_args[2]->value){
                                    if(array_key_exists($inst_args[0]->value, $this->LABEL_STACK)){
                                        $act_inst_num = $this->LABEL_STACK[$inst_args[0]->value];
                                    }
                                    else{ //label not on LABEL_STACK
                                        throw new SemException;}}}
                            else{
                                if($inst_args[1]->value != $inst_args[2]->value){
                                    if(array_key_exists($inst_args[0]->value, $this->LABEL_STACK)){
                                        $act_inst_num = $this->LABEL_STACK[$inst_args[0]->value];
                                    }
                                    else{ //label not on LABEL_STACK
                                        throw new SemException;}}}
                        }
                        else{ //different types
                            throw new OperandTypeException;
                        }
                    }
                    break;
                // debugging instructions ---------------------------------------------------
                case "DPRINT":
                case "BREAK":
                    break;
                default: //cannot happen
                    return 99;
            }
        }
        return 0;
    }

    /**
     * @brief Function for reading input
     * @param mixed $inst_args - instruction arguments
     * @param mixed $split - split instruction
     * @param mixed $frame - frame
     * @return mixed Return updated frame
     */
    public function ReadFunction($inst_args, $split, $frame){
        if(array_key_exists($split[1], $frame)){ //check if variable is defined
            if($inst_args[1]->value === "bool"){
                $input = $this->input->readBool();
                if($input !== null){
                    $frame[$split[1]][0] = $input;
                    $frame[$split[1]][1] = $inst_args[1]->value;
                    return $frame;
                }
            }
            else if($inst_args[1]->value === "int"){
                $input = $this->input->readInt();
                if($input !== null){
                    $frame[$split[1]][0] = $input;
                    $frame[$split[1]][1] = $inst_args[1]->value;
                    return $frame;
                }
            }
            else if($inst_args[1]->value === "string"){
                $input = $this->input->readString();
                if($input !== null){
                    $frame[$split[1]][0] = $input;
                    $frame[$split[1]][1] = $inst_args[1]->value;
                    return $frame;
                }
            } //wrong type or empty input, return nil
            $frame[$split[1]][0] = "nil@nil";
            $frame[$split[1]][1] = "nil";
            return $frame;
        }
        else{ //variable not defined
            throw new UnknownVarException;
        }
    }

    /**
     * @brief Check if variable is defined, if not, push it to the frame
     * @param mixed $split - split instruction
     * @param mixed $frame - frame
     * @return void
     */
    public function ValidDefvar($split, $frame){
        if($frame === "GF"){
            if(!array_key_exists($split[1], $this->GF)){
                $this->GF[$split[1]] = [null,null];
            }
            else{ //redefinition of variable
                throw new SemException;
            }
        }
        if($frame === "TF"){
            if(!$this->TF_set){
                throw new FrameAccessException;
            }
            if(!array_key_exists($split[1], $this->TF)){
                $this->TF[$split[1]] = [null,null];
            }
            else{ //redefinition of variable
                throw new SemException;
            }
        }
        if($frame === "LF"){
            if(empty($this->FRAME_STACK)){
                throw new FrameAccessException;
            }
            $last_index = count($this->FRAME_STACK) - 1;
            if(!array_key_exists($split[1], $this->FRAME_STACK[$last_index])){
                $this->FRAME_STACK[$last_index][$split[1]] = [null,null];
            }
            else{ //redefinition of variable
                throw new SemException;
            }
        }
    }

    /**
     * @brief Replace special escape sequencies in string
     * @param string $subject - string to be exchanged
     * @return void
     */
    public function ReplaceString($subject){
        $search = array('\\000', '\\009', '\\010', '\\011', '\\013', '\\032','\\035', '\\092');
        $replace = array("", "\t", "\n", "\v", "\r", " ", "#", "\\");
        $this->stdout->writeString(str_replace($search, $replace, $subject));
    }

    /**
     * @brief Write variable to stdout
     * @param mixed $split - split instruction
     * @param mixed $frame - frame
     * @return void
     */
    public function WriterVar($split, $frame){
        if(array_key_exists($split[1], $frame)){
            if($frame[$split[1]][0] === null){ //uninitialized variable
                throw new SemException;
            }
            if($frame[$split[1]][0] === "nil" || $frame[$split[1]][1] === "nil"){
                $this->stdout->writeString("");
            }
            else if($frame[$split[1]][1] === "string"){
                $this->ReplaceString($frame[$split[1]][0]);
            }
            else if($frame[$split[1]][1] === "bool"){
                if($frame[$split[1]][0] == true){
                    $this->stdout->writeString("true");
                }
                else{
                    $this->stdout->writeString("false");
                }
            }
            else{
                $this->stdout->writeString($frame[$split[1]][0]);
            }
        }
        else{
            throw new UnknownVarException;
        }
    }

    /**
     * @brief function for MOVE instruction, move variable or constant to destination variable
     * @param mixed $inst_args - instruction arguments
     * @param mixed $split - split instruction
     * @param mixed $dest_frame - destination frame
     * @return array<mixed> Return updated destination frame
     */
    public function MoveFunction($inst_args, $split, $dest_frame) : array {
        if(array_key_exists($split[1], $dest_frame)){ //check if destination variable is defined

            if($inst_args[1]->type === "var"){ //source is variable

                $split_2 = explode("@", $inst_args[1]->value);
                if($split_2[0] === "GF" ){ //global frame variable
                    if(array_key_exists($split_2[1], $this->GF)){ //check if source variable is defined
                        if($this->GF[$split_2[1]][0] === null){
                            throw new SemException;
                        }
                        $dest_frame[$split[1]][0] = $this->GF[$split_2[1]][0];
                        $dest_frame[$split[1]][1] = $this->GF[$split_2[1]][1];
                    }
                    else{ //source is not defined
                        throw new UnknownVarException;
                    }
                }
                else if($split_2[0] === "TF" ){ //temporary frame variable
                    if(array_key_exists($split_2[1], $this->TF)){ //check if source variable is defined
                        if($this->TF[$split_2[1]][0] === null){
                            throw new SemException;
                        }
                        $dest_frame[$split[1]][0] = $this->TF[$split_2[1]][0];
                        $dest_frame[$split[1]][1] = $this->TF[$split_2[1]][1];
                    }
                    else{ //source is not defined
                        throw new UnknownVarException;
                    }
                }
                else if($split_2[0] === "LF" ){ //local frame variable
                    if(empty($this->FRAME_STACK)){
                        throw new FrameAccessException;
                    }
                    $last_index = count($this->FRAME_STACK) - 1;

                    if(array_key_exists($split_2[1], $this->FRAME_STACK[$last_index])){ //check if source variable is defined
                        if($this->FRAME_STACK[$last_index][$split_2[1]][0] === null){
                            throw new SemException;
                        }
                        $dest_frame[$split[1]][0] = $this->FRAME_STACK[$last_index][$split_2[1]][0];
                        $dest_frame[$split[1]][1] = $this->FRAME_STACK[$last_index][$split_2[1]][1];
                    }
                    else{ //source is not defined
                        throw new UnknownVarException;
                    }
                }
            }
            else{ //source is constant
                $dest_frame[$split[1]][0] = $inst_args[1]->value;
                $dest_frame[$split[1]][1] = $inst_args[1]->type;
            }
        }
        else{ //variable not defined
            throw new UnknownVarException;
        }
        return $dest_frame;  
    }


    /**
     * @brief function for STRLEN instruction, get integer length of string, fill variable with it
     * @param mixed $inst_args - instruction arguments
     * @param mixed $split - split instruction
     * @param array<mixed> $frame - frame
     * @return array<mixed> Return updated frame
     */
    public function StrlenFunction($inst_args, $split, $frame) : array{
        if(array_key_exists($split[1], $frame)){
            if($inst_args[1]->type === "string"){
                $frame[$split[1]][0] = strlen($inst_args[1]->value);
                $frame[$split[1]][1] = "int";
            }
            else if($inst_args[1]->type === "var"){
                $split_2 = explode("@", $inst_args[1]->value);
                if($split_2[0] === "GF" ){ //global frame variable
                    $frame = $this->StrlenSource($split, $split_2, $frame, $this->GF);
                }
                else if($split_2[0] === "TF" ){ //temporary frame variable
                    $frame = $this->StrlenSource($split, $split_2, $frame, $this->TF);
                }
                else if($split_2[0] === "LF" ){ //local frame variable
                    $last_index = count($this->FRAME_STACK) - 1;
                    $frame = $this->StrlenSource($split, $split_2, $frame, $this->FRAME_STACK[$last_index]);
                }
            }
            else{ //wrong operand, strlen operand must be string or variable
                throw new OperandValException;
            }
        }
        else{ //variable not defined
            throw new UnknownVarException;
        }
        return $frame;
    }

    /**
     * @brief function for STRLEN instruction, source variable handling
     * @param mixed $split - split instruction
     * @param mixed $split_2 - split instruction
     * @param mixed $frame - frame
     * @param array<mixed> $frame_source - source frame
     * @return array<mixed> Return updated frame
     */
    public function StrlenSource($split, $split_2, $frame, $frame_source) : array{
        if(array_key_exists($split_2[1], $frame_source)){
            if($frame_source[$split_2[1]][0] === null){ //uninitialized source variable
                throw new SemException;
            }
            if($frame_source[$split_2[1]][1] !== "string"){ //wrong type of variable
                throw new OperandTypeException;
            }
            $frame[$split[1]][0] = strlen($frame_source[$split_2[1]][0]);
            $frame[$split[1]][1] = "int";
        }
        else{ //source is not defined
            throw new UnknownVarException;
        }
        return $frame;
    }

    /**
     * @brief Method for checking if variable is defined
     * @param mixed $split - split instruction
     * @return void
     */
    public function IsVarDefined($split){
        if($split[0] === "GF"){
            if(!array_key_exists($split[1], $this->GF)){
                throw new UnknownVarException;}}
        else if($split[0] === "TF"){
            if(!$this->TF_set){
                throw new FrameAccessException;}
            if(!array_key_exists($split, $this->TF)){
                throw new UnknownVarException;}}
        else if($split[0] === "LF"){
            if(empty($this->FRAME_STACK)){
                throw new FrameAccessException;}
            $last_index = count($this->FRAME_STACK) - 1;
            if(!array_key_exists($split[1], $this->FRAME_STACK[$last_index])){
                throw new UnknownVarException;}}
    }

    /**
     * @brief function for JUMPIFEQ instruction, compare two variables
     * @param mixed $inst_args - instruction arguments
     * @param mixed $split_1 - split instruction
     * @param mixed $split_2 - split instruction
     * @param int $act_inst_num - actual instruction number
     * @param mixed $frame_1 - frame
     * @param mixed $frame_2 - frame
     * @param bool $equal - true if JUMPIFEQ, false if JUMPIFNEQ
     * @return int Return new instruction pointer
     */
    public function JumpIfEqVarCompar($inst_args, $split_1, $split_2, $act_inst_num, $frame_1, $frame_2, $equal) : int{
        if($frame_1[$split_1[1]][1] == $frame_2[$split_2[1]][1]){ //types are equal
            if($equal){
                if($frame_1[$split_1[1]][0] == $frame_2[$split_2[1]][0]){ //values are equal
                    if(array_key_exists($inst_args[0]->value, $this->LABEL_STACK)){ //label exists
                        $act_inst_num = $this->LABEL_STACK[$inst_args[0]->value];
                    }
                    else{ //label not on LABEL_STACK
                        throw new SemException;
                    }}}
            else{
                if($frame_1[$split_1[1]][0] != $frame_2[$split_2[1]][0]){ //values are equal
                    if(array_key_exists($inst_args[0]->value, $this->LABEL_STACK)){ //label exists
                        $act_inst_num = $this->LABEL_STACK[$inst_args[0]->value];
                    }
                    else{ //label not on LABEL_STACK
                        throw new SemException;
                    }}}}
        else{ //different types
            throw new OperandTypeException;
        }
        return $act_inst_num;
    }

    /**
     * @brief function for JUMPIFEQ and JUMPIFNEQ instructions, compare variable with constant
     * @param mixed $inst_args - instruction arguments
     * @param mixed $split_1 - split instruction
     * @param int $act_inst_num - actual instruction number
     * @param mixed $frame - frame
     * @param bool $equal - true if JUMPIFEQ, false if JUMPIFNEQ
     * @return int Return new instruction pointer
     */
    public function JumpIfEqVarSymb($inst_args, $split_1, $act_inst_num, $frame, $equal) : int{
        if(array_key_exists($split_1[1], $frame)){
            if($frame[$split_1[1]][1] === $inst_args[2]->type){ //types are equal
                if($equal){ //JUMPIFEQ
                    if($frame[$split_1[1]][0] == $inst_args[2]->value){ //values are equal
                        
                        if(array_key_exists($inst_args[0]->value, $this->LABEL_STACK)){ //label exists
                            $act_inst_num = $this->LABEL_STACK[$inst_args[0]->value];
                        }
                        else{ //label not on LABEL_STACK
                            throw new SemException;
                        }}}
                else{ //JUMPIFNEQ
                    if($frame[$split_1[1]][0] != $inst_args[2]->value){ //values are not equal
                        if(array_key_exists($inst_args[0]->value, $this->LABEL_STACK)){ //label exists
                            $act_inst_num = $this->LABEL_STACK[$inst_args[0]->value];
                        }
                        else{ //label not on LABEL_STACK
                            throw new SemException;
                        }}}}
            else{ //different types
                throw new OperandTypeException;
            }}
        else{ //variable not defined
            throw new UnknownVarException;
        }
        return $act_inst_num;
    }

    /**
     * @brief function for JUMPIFEQ instruction, compare constant with variable
     * @param mixed $inst_args - instruction arguments
     * @param mixed $split_2 - split instruction
     * @param int $act_inst_num - actual instruction number
     * @param mixed $frame - frame
     * @param bool $equal - true if JUMPIFEQ, false if JUMPIFNEQ
     * @return int Return new instruction pointer
     */
    public function JumpIfEqSymbVar($inst_args, $split_2, $act_inst_num, $frame, $equal) : int{
        if(array_key_exists($split_2[1], $frame)){
            if($frame[$split_2[1]][1] == $inst_args[1]->type){ //types are equal
                if($equal){
                    if($frame[$split_2[1]][0] == $inst_args[1]->value){ //values are equal
                        if(array_key_exists($inst_args[0]->value, $this->LABEL_STACK)){ //label exists
                            $act_inst_num = $this->LABEL_STACK[$inst_args[0]->value];
                        }
                        else{ //label not on LABEL_STACK
                            throw new SemException;
                        }}}
                else{
                    if($frame[$split_2[1]][0] != $inst_args[1]->value){ //values are equal
                        if(array_key_exists($inst_args[0]->value, $this->LABEL_STACK)){ //label exists
                            $act_inst_num = $this->LABEL_STACK[$inst_args[0]->value];
                        }
                        else{ //label not on LABEL_STACK
                            throw new SemException;
                        }}}}
            else{ //different types
                throw new OperandTypeException;
            }}
        else{ //variable not defined
            throw new UnknownVarException;
        }
        return $act_inst_num;
    }

    /**
     * @brief function for PUSHS instruction, push variable to stack
     * @param mixed $split - split instruction
     * @param mixed $frame - frame
     * @param array<mixed> $stack - stack
     * @return array<mixed> Return updated stack
     */
    public function PushsVar($split, $frame, $stack) : array{
        if($frame[$split[1]][0] == "bool"){
            if($frame[$split[1]][1] == "true"){
                $stack[] = array(true, "bool");
            }
            else{
                $stack[] = array(false, "bool");
            }
        }
        else{
            $stack[] = array($frame[$split[1]][0], $frame[$split[1]][1]);
        }
        return $stack;
    }

    /**
     * @brief function NOT for variable
     * @param mixed $split_dest - split instruction
     * @param mixed $split - split instruction
     * @param mixed $frame - frame
     * @return array<mixed> Return updated frame
     */
    public function NotFuncForVar($split_dest, $split, $frame) : array{
        if($frame[$split[1]][1] !== "bool"){
            throw new OperandTypeException;
        }
        if($frame[$split[1]][0] === true){
            $frame[$split_dest[1]][0] = false;
        }
        else{
            $frame[$split_dest[1]][0] = true;
        }
        $frame[$split_dest[1]][1] = "bool";
        return $frame;
    }

}//end of class Interpreter

?>