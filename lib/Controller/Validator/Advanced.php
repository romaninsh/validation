<?php
namespace romaninsh\validation;

class Controller_Validator_Advanced extends Controller_Validator_Basic {

    function init(){
        parent::init();

        $this->alias=array_merge($this->alias,
            array(
                'foo'=>'bar',
            )
        );
    }

    function resolveRuleAlias($rule){

        //  4..20
        if(strpos($rule,'..')!==false){
            list($min,$max)=explode('..',$rule);
            $this->pushRule($min,$max);
            return 'between';
        }

        return parent::resolveRuleAlias($rule);
    }

    /**
     * Inclusive range check
     */
    function rule_between($a){
        $min=$this->pullRule();
        $max=$this->pullRule();
        if($a<$min || $a>$max)return $this->fail('must between {{arg1}} and {{arg2}}',$a);
        return $a;
    }
}
