<?php
/**
 * IPP - PHP Project Student
 * @author Halva Jindřich (xhalva05)
 * @file LabelMap.php
 * @brief Method will go through whole program anf fill the label map
 * @date 2024-04-12
 */

namespace IPP\Student;

class LabelMap{
    /** @var array<string, int> */
    public array $labels = array();

    /**
     * @brief Constructor for LabelMap
     * @param mixed $Program - XML program
     */
    public function __construct($Program){
        $this->labels = array();
        $inst_count = count($Program->instructions);
        //loop through all instructions
        for($act_inst_num = 0; $act_inst_num < $inst_count; $act_inst_num++){
            $inst = $Program->instructions[$act_inst_num];
            $inst_name = strtoupper($inst->opcode);
            //if instruction is label
            if($inst_name === "LABEL"){
                //if label is already in map
                if(array_key_exists($inst->args[0]->value, $this->labels)){
                    throw new SemException;
                }
                //add label to map
                $this->labels[$inst->args[0]->value] = $act_inst_num;
            }
        }
    }

    /**
     * @brief Method for getting label map
     * @return array<mixed, int> label map
     */
    public function GetLabelMap(){
        return $this->labels;
    }
}

