<?php
namespace romaninsh\validation;

class Controller_Validator_Basic extends Controller_Validator_Abstract {

    function init(){
        parent::init();

        $this->alias=array_merge($this->alias,
            array(
                'same'=>'eq',
                'different'=>'ne',
            )
        );
    }


    function rule_int($acc)
    {

        if(!filter_var($acc, FILTER_VALIDATE_INT)){
            return $this->fail('must be integer');
        }
        return (int)$acc;
    }







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
        $b=$this->pullRule();
        if($a!=$this->get($b))return $this->fail('must be equal to {{arg1}}',$a);
    }

    function rule_ne($a){
        $b=$this->pullRule();
        if($a==$this->get($b))return $this->fail('must not be {{arg1}}',$a);
    }



}
