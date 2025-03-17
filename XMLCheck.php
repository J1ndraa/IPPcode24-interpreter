<?php
/**
 * IPP - PHP Project Student
 * @author Halva Jindřich (xhalva05)
 * @file XMLCheck.php
 * @brief Class for checking structure of XML source code
 * @date 2024-04-09
 * 
 * @note Class instance id`s starts with uppercase letter
 */

namespace IPP\Student;

use DOMDocument;
use IPP\Core\Exception\XMLException;

/**
 * @brief Class for checking structure of XML
 */
class XMLCheck{

    public function __construct(){
    }

    /**
     * @brief Method for checking structure of XML source code
     * @param DOMDocument $dom - DOMDocument object with XML source code
     * @return Program - object with instructions
     */
    public function checkXML($dom){
        $instructions = array();
        //header in XML
        $root = $dom->getRootNode();

        //check if root`s first child is program
        $program = $root->firstChild;
        if($program->nodeName !== "program"){
            throw new XMLStructureException;
        }
        if(($program->attributes->getNamedItem("language")) === null){
            throw new XMLStructureException;
        }
        if($program->attributes->getNamedItem("language")->nodeValue != "IPPcode24"){                    
            throw new XMLStructureException;
        }
        
        //program object construct
        $Program_tmp = new Program($program->attributes->getNamedItem("language")->nodeValue);

        //program has no child
        if($program->hasChildNodes() === false){
            throw new XMLException;
        }

        //first instruction in XML
        $instruction = $program->childNodes->item(1);
        //how many children to jump
        $inst_jump = 1;

        //looping through all instructions in XML
        do{
            $arguments = array();
            if($instruction->nodeName != "instruction"){
                throw new XMLStructureException;
            }
            if(($instruction->attributes->getNamedItem("order") === null) || ($instruction->attributes->getNamedItem("opcode") === null)){
                throw new XMLStructureException;
            }
            $order = $instruction->attributes->getNamedItem("order")->nodeValue;
            $opcode = $instruction->attributes->getNamedItem("opcode")->nodeValue;

            //convert $order to integer value
            $order_int = intval($order);
            
            //each instruction has special name
            $Inst[$order] = new Instruction($opcode, $order_int);
            $instructions[] = $Inst[$order];

            //check arguments
            $arg1 = null;
            $arg2 = null;
            $arg3 = null;
            if($instruction->childNodes->count() === 1){ //instruction has no arguments
                $inst_jump += 2;
                $instruction = $program->childNodes->item($inst_jump);
                continue;
            }
            else if($instruction->childNodes->count() === 3){ //one argument
                $arg1 = $instruction->childNodes->item(1);
            }
            else if($instruction->childNodes->count() === 5){ //two arguments
                $arg1 = $instruction->childNodes->item(1);
                $arg2 = $instruction->childNodes->item(3);
            }
            else if($instruction->childNodes->count() === 7){ //three arguments
                $arg1 = $instruction->childNodes->item(1);
                $arg2 = $instruction->childNodes->item(3);
                $arg3 = $instruction->childNodes->item(5);
            }
            else{
                throw new XMLException;
            }
            //check if arguments are in correct format and add them to arguments array
            if($arg1 !== null){
                $this->checkArg($arg1,"arg1");
                $Arg1 = new Arg(trim($arg1->attributes->getNamedItem("type")->nodeValue), trim($arg1->nodeValue));
                $arguments[0] = $Arg1;
            }
            if($arg2 !== null){
                $this->checkArg($arg2,"arg2");
                $Arg2 = new Arg(trim($arg2->attributes->getNamedItem("type")->nodeValue), trim($arg2->nodeValue));
                $arguments[1] = $Arg2;
            }
            if($arg3 !== null){
                $this->checkArg($arg3,"arg3");
                $Arg3 = new Arg(trim($arg3->attributes->getNamedItem("type")->nodeValue), trim($arg3->nodeValue));
                $arguments[2] = $Arg3;
            }

            //fill the instruction object with arguments
            $Inst[$order]->addArg($arguments);
            $inst_jump += 2;
            $instruction = $program->childNodes->item($inst_jump);
        } while($instruction !== null);

        //fill the program object with instructions
        $Program_tmp->addInstruction($instructions);
        
        //sort the instructions by order
        usort($Program_tmp->instructions, array($this, "compare"));

        //search for duplicates orders
        if(count($Program_tmp->instructions) !== count(array_unique(array_column($Program_tmp->instructions, 'order')))){
            throw new XMLStructureException;
        }
        
        return $Program_tmp;
    }

    /**
     * @brief Method for checking arguments in XML
     * @param mixed $arg - argument to check
     * @param string $arg_name - name of argument
     * @return void
     */
    protected function checkArg($arg, $arg_name){
        if($arg->nodeName !== "$arg_name"){
            throw new XMLStructureException;
        }
        if($arg->attributes->getNamedItem("type") === ""){
            throw new XMLStructureException;
        }
    }

    /**
     * @brief Method for comparing two instructions by order
     * @param Instruction $a - first instruction
     * @param Instruction $b - second instruction
     * @return int
     */
    protected function compare($a, $b){
        return $a->order - $b->order;
    }
}
//end of XMLCheck.php
?>