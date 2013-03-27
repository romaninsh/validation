<?php
namespace romaninsh\validation;

class Controller_Validator_Abstract extends \AbstractController {
    public $rules=array();

    public $default_exception='Exception_ValidityCheck';

    public $alias=array();  // legacy=>new

    function init()
    {
        parent::init();

        $this->source=$this->owner; // must support set/get interface
    }


    // {{{ Interface Methods
    /**
     * This method will go through all the rules you specify, expand
     * and normalize them and assign into array indexed by field name.
     *
     * You do not need to have your fields defined at this point, unless
     * you specify wildcards.
     *
     * This method takes various arguments as described in documentation.
     */
    function is()
    {
        $args=func_get_args();

        // If only first argument is specified, then it's array of rulesets.
        // We will call ourselves with every element.
        if (count($args)==1 && is_array($args[0])) {
            foreach ($args[0] as $ruleset) {
                // $ruleset here is either array or string with pipes
                if (!is_array($ruleset)) {
                    $ruleset=array($ruleset);
                }
                call_user_func_array(array($this,'is'), $ruleset);
            }
            return $this;
        }


        // If ruleset is specified as a string, we need to expand it
        // into an array.
        if (count($args)==1) {
            list($field_definition,$rules) = $this->expandRules($args[0]);
        } else {
            $rules=$args;
            $field_definition=array_shift($rules);
        }

        // Convert field defintion into list of fields
        $fields=$this->expandFieldDefinition($field_definition);

        // Save rules for each field
        foreach ($fields as $field) {
            $this->rules[$field][]=$rules;
        }
        return $this;
    }

    /**
     * Provided with 
     *
     * In: "int|required|alphanum|save"  (Basic)
     * In: "int!|a-z|"                   (Advanced)
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
        return $this->consumed[]=array_shift($this->current_ruleset);
    }

    function get($field){
        return $this->source->get($field);
    }

    public $acc=null;
    public $consumed=array();
    function applyRules($field,$rules){
        $this->acc=$this->get($field);
        $this->current_ruleset=$rules;

        while(!is_null($rule=$this->getRule())){
            $this->cast=false;

            // For debugging
            $tmp=null;
            $this->consumed=array($rule);

            try{
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
                    if(isset($this->alias[$rule])){
                        $rule=$this->alias[$rule];
                    }
                    $tmp = $this->{'rule_'.$rule}($this->acc,$field);
                }

                if($this->debug){
                    echo "<font color=blue>rule_$rule({$this->acc},".
                        join(',',$this->consumed).")=$tmp</font><br/>";
                }

                if(!is_null($tmp))$this->acc=$tmp;
                if($this->cast)$this->set($field,$tmp);
            } catch (\Exception_ValidityCheck $e) {
                if($this->debug){
                    echo "<font color=red>rule_$rule({$this->acc},".
                        join(',',$this->consumed).") failed</font><br/>";
                }
                throw $e
                    ->setField($field)
                    ->addMoreInfo('val',$this->acc)
                    ->addMoreInfo('rule',$rule);
            }
        }
    }

    function fail($str)
    {
        throw $this->exception($str);
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

    function rule_fail(){
        return $this->fail('is incorrect');
    }


    // TO BE MOVED to validator
    //

}
