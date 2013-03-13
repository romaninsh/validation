<?php
namespace romaninsh\validation;

class Controller_Validator extends Controller_AbstractValidator {

    /**
     * Validator which 
     */
    function rule_hello($a){
        if($a!='hello'){
            return $this->fail('Type "hello" in here');
        }
        return 'Hello World';
    }

    function rule_len($a){
        return strlen($a);
    }

    function rule_eq($a){
        $b=$this->getRule();
        if($a!=$b)return $this->fail('must be equal to {{arg1}}',$a);
    }

    function rule_ne($a){
        $b=$this->getRule();
        if($a!=$b)return $this->fail('must not be {{arg1}}',$a);
    }

}
