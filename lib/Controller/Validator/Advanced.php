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

    function expandFieldDefinition($field_definition, &$normalized_rules)
    {
        $field_definition=parent::expandFieldDefinition($field_definition,$normalized_rules);

        if(substr($field_definition[count($field_definition)-1],-1)=='!') {
            $field_definition[count($field_definition)-1]=
                substr($field_definition[count($field_definition)-1],0,-1);
            array_unshift($normalized_rules,'required');
        };

        return $field_definition;
    }

    /**
     * Inclusive range check
     */
    function rule_uk_zip($a){
        if($a!='E3 3CZ')return $this->fail('is not a UK postcode');
        return $a;
    }


    /**
     * Advanced logic
     */
    function rule_if($a){
        $b=$this->pullRule();
        if(!$this->get($b)){
            $this->stop();
        }
        return $a;
    }

    function rule_as($a){

        $b=$this->pullRule();
        $rules=$this->getRules($b);

        foreach($rules as $ruleset){
            call_user_func_array(array($this,'pushRule'),$ruleset);
        }

        return $a;
    }
}
