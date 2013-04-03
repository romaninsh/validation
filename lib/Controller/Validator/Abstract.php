<?php // vim:ts=4:sw=4:et:fdm=marker
namespace romaninsh\validation;

class Controller_Validator_Abstract extends \AbstractController {
    public $rules=array();

    public $default_exception='Exception_ValidityCheck';

    public $alias=array();  // legacy=>new

    public $source=null;

    function init()
    {
        parent::init();
        $that=$this;

        $this->source=$this->owner; // must support set/get interface
        if ($this->source instanceof \Model) {

            $this->source->addMethod('is',function($m) use ($that){
                $args=func_get_args();
                array_shift($args);

                call_user_func_array(array($that,'is'),$args);
                $that->on('beforeSave');
                return $m;
            });
        }
    }


    // {{{ Rule initialization and normalization methods
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
            list($field_definition,$rules) = $this->normalizeRules($args[0]);
        } else {
            $rules=$args;
            $field_definition=array_shift($rules);
        }

        // Convert field defintion into list of fields
        $fields=$this->expandFieldDefinition($field_definition,$rules);

        // Save rules for each field
        foreach ($fields as $field) {
            $this->rules[$field][]=$rules;
        }
        return $this;
    }

    /**
     * If you are adding this controller inside a model, you don't need to
     * set source. If you want controller to work with an array or some other
     * object, use setSource()
     */
    function setSource($source) {
        $this->source=$source;
    }

    /**
     * Provided with string containing rules, this will convert it into
     * normal (array) form
     *
     * In: "int|required|alphanum|save"  (Basic)
     * In: "int!|a-z|"                   (Advanced)
     * Out: array('int','required','alphanum','save')
     */
    function normalizeRules($rules)
    {
        list($field,$rules)=explode('|',$rules,2);
        $rules=preg_split('/[|,:]/',$rules);
        return array($field,$rules);
    }

    /**
     * Provided with a multiple field definition, this will convert
     * them into an array.
     *
     * In: "name,surname,foo"        (Basic)
     * In: "%boolean,-@address"      (Advanced)
     * Out: array('name','surname','foo')
     */
    function expandFieldDefinition($field_definition,&$normalized_rules)
    {
        return explode(',',$field_definition);
    }

    // }}}

    // {{{ Supplimentary configuration methods
    /**
     * Call this to get list of parsed rules for specified field.
     */
    function getRules($field){
        return $this->rules[$field];
    }

    /**
     * Call this to set a different hook when rules are going to be
     * applied. By default you have to call now()
     */
    public $custom_hook=false;
    function on($hook,$object=null,$default_hook=false)
    {
        if(!$object)$object=$this->owner;

        $this->has_hook=true;

        $object->addHook($hook,array($this,'applyRulesets'));
    }

    /**
     * Apply rules now.
     */
    function now(){
        return $this->applyRulesets();
    }

    // }}}

    // {{{ Internal Methods to be used by rule_*


    // }}}

    // {{{ Methods which are essential when applying rules
    /**
     * Get list of fields which we are going to validate. In some cases
     * it makes no sense to validate fields which are not appearing individual
     * the form, therefore this method will look carefully at what you are
     * validating 
     */
    function getActualFields(){
        return array_keys($this->rules);
    }

    /** 
     * Go through the list of defined rules and call the corresponding
     * filters and convertors.
     */
    function applyRulesets(){
        // List of fields which actually need validation at this time.
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
     * Pulls next rule out of the rule stack (current_ruleset)
     */
    function pullRule(){
        return $this->consumed[]=array_shift($this->current_ruleset);
    }
    /**
     * Adds new rule into a rule-set, which will be executed next.
     * You can specify single or multiple rules, this method accepts
     * variable arguments.
     *
     * Rules must be normalized.
     */
    function pushRule(){
        $args=func_get_args();

        // TODO: this can probably be done by args+current_ruleset
        foreach (array_reverse($args) as $arg) {
            array_unshift($this->current_ruleset, $arg);
        }
    }

    /**
     * Returns the original value of the field.
     */
    function get($field){
        return $this->source[$field];
    }

    /**
     * Changes the original value of the field (for normalization)
     */
    function set($field,$value){
        $this->source[$field]=$value;
        return $this;
    }

    function resolveRuleAlias($rule){
        if(isset($this->alias[$rule])){
            $rule=$this->alias[$rule];
        }

        if(strpos($rule,'?')!==false){
            list($rule,$this->custom_error)=explode('?',$rule,2);
        }

        return $rule;
    }

    public $acc=null;
    public $consumed=array();
    public $current_ruleset=null;
    public $custom_error=null;
    public $bail_out=false;

    /**
     * This is the main body for rule processing.
     */
    function applyRules($field,$ruleset){

        // Save previous values, just in case
        $acc=$this->acc;
        $crs=$this->current_ruleset;
        $this->bail_out=false;

        $this->acc=$this->get($field);
        $this->current_ruleset=$ruleset;

        while(!is_null($rule=$this->pullRule())){
            $this->cast=false;
            $this->custom_error=null;

            // For debugging
            $tmp=null;
            $this->consumed=array($rule);

            try{
                if( (is_object($rule) || is_array($rule)) && is_callable($rule)){
                    $tmp = $rule($this,$this->acc,$field);
                }else{
                    // to_XX 
                    if(substr($rule,0,3)=='to_'){
                        if(!$this->hasMethod('rule_'.$rule)) {
                            $rule=substr($rule,3);
                        }
                        $this->cast=true;
                    }
                    if($rule===''){
                        if($this->cast)$this->set($field,$this->acc);
                        continue;
                    }

                    $rule=$this->resolveRuleAlias($rule);
                    $tmp = $this->{'rule_'.$rule}($this->acc,$field);
                }

                if($this->debug){
                    echo "<font color=blue>rule_$rule({$this->acc},".
                        join(',',$this->consumed).")=$tmp</font><br/>";
                }

                if(!is_null($tmp))$this->acc=$tmp;
                if($this->cast)$this->set($field,$tmp);
                if($this->bail_out)break;
            } catch (\Exception_ValidityCheck $e) {
                if($this->debug){
                    echo "<font color=red>rule_$rule({$this->acc},".
                        join(',',$this->consumed).") failed</font><br/>";
                }
                $this->acc=$acc;
                $this->current_ruleset=$crs;
                throw $e
                    ->setField($field)
                    ->addMoreInfo('val',$this->acc)
                    ->addMoreInfo('rule',$rule);
            }
        }
        $this->acc=$acc;
        $this->current_ruleset=$crs;
    }

    function fail($str)
    {
        throw $this->exception($this->custom_error?:$str);
    }
    function stop()
    {
        $this->bail_out=true;
    }

    // }}}

    /**
     * Will process individual rule 
     */
    function rule_fail(){
        return $this->fail('is incorrect');
    }


    // TO BE MOVED to validator
    //
    function rule_required($a)
    {
        if ($a==='' || $a===false) {
            return $this->fail('mandatory');
        }
        return $a;
    }


}
