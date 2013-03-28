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


    function rule_int($a)
    {

        if(!filter_var($a, FILTER_VALIDATE_INT)){
            return $this->fail('must be integer');
        }
        return (int)$a;
    }


    function rule_to_alpha($a)
    {
        $a=preg_replace('/[^a-z]/', '', $a);
    }
    function rule_alpha($a)
    {
        if(!preg_match('/^([a-z])+$/i', $a)){
            return $this->fail('must contain only latin characters');
        }
    }


    function rule_trim($a)
    {
        $a=trim($a);
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
        if($a!=$this->get($b))return $this->fail('must be equal to {{arg1}}');
    }

    function rule_ne($a){
        $b=$this->pullRule();
        if($a==$b)return $this->fail('must not be equal to "{{arg1}}"');
    }

    function rule_nef($a){
        $b=$this->pullRule();
        if($a==$this->get($b))return $this->fail('must not be same as {{arg1}}');
    }

    /**
     * Inclusive range check
     */
    function rule_between($a){
        $min=$this->pullRule();
        $max=$this->pullRule();
        if($a<$min || $a>$max)return $this->fail('must between {{arg1}} and {{arg2}}');
        return $a;
    }




}
