<?php
namespace romaninsh\validation;

class Controller_AbstractValidator extends \AbstractController {
    public $rules=array();


    // {{{ Interface Methods
    function is()
    {
        $args=func_get_args();

        // If only first argument is specified, then it's array of rules.
        if(count($args)==1 && is_array($args[0])){
            $args=$args[0];
        }
        foreach($args as $rules){

            // Converts rule-set into presentable format, and clean up
            // field definition from !, = etc.
            list($field_definition,$rules) = $this->expandRules($rules);

            // Convert field defintion into list of fields
            $fields=$this->expandFieldDefinition($field_definition);

            // Save rules for each field
            foreach($fields as $field){
                $this->rules[$field][]=$rules;
            }
        }
        return $this;
    }

    /**
     * In: "int|required|alphanum|save"
     * In: "int!|a-z|"
     * Out: array('int','required','alphanum','save')
     */
    function expandRules($rules)
    {
        list($field,$rules)=explode('|',$rules,2);
        $rules=preg_split('/[|,:]/',$rules);
        return array($field,$rules);
    }

    /**
     * In: "name,surname,foo"
     * In: "%boolean,-@address" // boolean type except address group
     * Out: array('name','surname','foo')
     *
     */
    function expandFieldDefinition($field_definition)
    {
        return explode(',',$field_definition);
    }

    /**
     * Get parsed rules
     */
    function getRules($field){
        return $this->rules[$field];
    }


    function on($hook,$object=null)
    {
        if(!$object)$object=$this->owner;

        $object->addHook($hook,array($this,'applyRules'));
    }

    function now(){
        return $this->applyRulesets();
    }

    function getActualFields(){
        return array_keys($this->rules);
    }

    function applyRulesets(){
        // Get of fields which actually need validation at this time.
        $fields=$this->getActualFields();

        foreach($fields as $field){
            $rulesets = $this->getRules($field);
            foreach($rulesets as $rules){

                $this->applyRules($field,$rules);
            }
        }
        return $this;
    }

    /**
     * Gets next rule from the current ruleset
     */
    function getRule(){
        return array_unshift($this->current_ruleset);
    }

    public $acc=null;
    function applyRules($field,$rules){
        $this->acc=$this->get($field);
        $this->current_ruleset=$rules;

        while(!is_null($rule=$this->getRule())){
            $this->cast=false;

            if( (is_object($rule) || is_array($rule)) && is_callable($rule)){
                $tmp = $rule($this,$this->acc,$field);
            }else{
                // to_XX 
                if(substr($rule,0,2)=='to_'){
                    $rule=substr($rule,3);
                    $this->cast=true;
                }
                if($rule===''){
                    if($this->cast)$this->set($field,$this->acc);
                    continue;
                }
                $tmp = $this->{'rule_'.$rule}($this->acc,$field);
            }

            if($this->debug()){
                echo "<font color=blue>rule_$rule({$this->acc},$field)=$tmp</font><br/>";
            }

            if(!is_null($tmp))$this->acc=$tmp;
            if($this->cast)$this->set($field,$tmp);
        }
    }

    // }}}

    /**
     * Will process individual rule 
     */
    function processRuleset()
    {
        //foreach(
    }

    function singleRule()
    {
    }

}
