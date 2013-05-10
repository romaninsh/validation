<?php // vim:ts=4:sw=4:et:fdm=marker
namespace romaninsh\validation;

/**
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
/**
 * Abstract Validator implements the low-level requirements of
 * a validator integrated into Agile Toolkit. In normal conditions
 * you should use 'Validator' class which extends 'Validator_Advanced'.
 *
 * If you are writing your own validator rules to a very specific
 * objects, you can use extend either this class or Validator_Basic.
 *
 * NOTE: How to write rules:
 *  Do not attempt to put all the rules for a single field on a line.
 * Many rules will change the value of acumulator, so please add 
 * as many rules as necessary
 *
 * is(array(
 *   'name|len|gt|20',
 *   'name|!rude',
 *   'name|to_ucfirst',
 *   ));
 *
 * Finally - you can add one validator inside another to extends
 * it's rules.
 *
 * @link http://agiletoolkit.org/doc/api
 */
class Controller_Validator_Abstract extends \AbstractController {

    /**
     * Each ruleset is broken down by field and is stored in this
     * array in a normal form. You can get rules for a particular
     * field by calling $this->getRules(field);
     */
    public $rules=array();

    public $default_exception='Exception_ValidityCheck';

    /**
     * This is a static array which is expanded inside extending
     * classes. Extend this inside your validator's init method:
     *
     *   $alias['mandatory']='required';
     *   $alias['must_have']='required';
     */
    public $alias=array(); 

    /**
     * Validator can check either a model, array, form or any other
     * object as long as it supports array_access. If you are using
     * Model then you can use some additional functionality foreach
     * selecting fields.
     */
    public $source=null;

    /**
     * Name of the field which is currently beind processed
     */
    public $active_field;

    // {{{ Initialization method
    function init()
    {
        parent::init();
        $that=$this;

        if ($this->owner instanceof Controller_Validator) {
            $this->owner->addHook('extraRules',$this);
            return;  // no source, simply extend rules.
        }

        if ((
            $this->owner instanceof \Model ||
            $this->owner instanceof \Form

        ) && !$this->owner->hasMethod('is')) {

            $this->source=$this->owner; // must support set/get interface
            $this->owner->validator = $this;

            $this->source->addMethod('is', function($m) use ($that){
                $args=func_get_args();
                array_shift($args);

                call_user_func_array(array($that,'is'),$args);
                $that->on('beforeSave',null,true);
                return $m;
            });
        }
    }
    // }}}

    // {{{ Rule initialization and normalization methods
    // ^^ do not remove - that's a fold in VIM, starts section

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
        $fields=$this->expandFieldDefinition($field_definition, $rules);

        // Save rules for each field
        foreach ($fields as $field) {
            $this->rules[$field][]=$rules;
        }

        return $this;
    }

    /**
     * If you are adding this Controller inside a Model, you don't need to
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
        // If you want to use a pipe in a regex, custom message etc,
        // single-quote the string (escaping would be too confusing in regexes):
        //
        // This works with:
        //
        // 'foo?\'my piped | string\''
        // "foo?'my piped | string'"
        //
        // BIG NOTE: There is a reason why there are 2 formats. I don't
        // want developres to use ONLY the pipe format. There is always
        // multi-argument format, where argument can be anything, and
        // we don't complicate things and try to get around regexps
        //
        // is('name|required?my pipe|string')       // Bad
        // is('name','required?my pipe|string')     // Good
        // is('name','required?','my pipe|string')  // Best

        // TODO: clean up
        $rules=preg_split('/[|,:]/',$rules);
        $field=array_shift($rules);

        return array($field, $rules);
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
     * TODO: find these comments very difficult to understand!
     *
     * Call this to set a different hook when rules are going to be
     * applied. By default you have to call now()
     *
     * on() used by default for when validator is added, however if
     * you call it manually (avoiding 3rd argument) it will override
     * the default hook. This is done to avoid double-validation
     */
    public $custom_hook=false;
    function on($hook,$object=null,$default_hook=false)
    {
        if(!$object)$object=$this->owner;
        if(!$default_hook)$this->custom_hook=true;

        $this->has_hook=true;
        $that=$this;

        $object->addHook($hook,function($m) use ($default_hook,$that){
            if ($that->custom_hook && $default_hook) return;
            $that->applyRulesets();
        });
    }

    /**
     * Apply rules now.
     */
    function now(){
        return $this->applyRulesets();
    }

    // }}}

    // {{{ Methods which are essential when applying rules
    /**
     * Get list of fields which we are going to validate. In some cases
     * it makes no sense to validate fields which are not appearing individually
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
            $this->active_field = $field;
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
     * Retuns field name of rule chain
     * being processed
     *
     * Second argument to rule_ is field, there are no need for this method
     */
    /*
    function get_active_field()
    {
        return $this->active_field;
    }
     */

    /**
     * Changes the original value of the field (for normalization)
     */
    function set($field,$value)
    {
        $this->source[$field]=$value;
        return $this;
    }

    function resolveRuleAlias($rule)
    {
        if(isset($this->alias[$rule])){
            $rule=$this->alias[$rule];
        }

        // Only rule names are passed here,
        // not args, so a comma could only be
        // a custom message.

        // TODO: but what about array validation?
        // Probably a rare edge case, but we
        // should mention it in the docs??

        if(strpos($rule,'?') !== false){

            list($rule, $error)=explode('?', $rule, 2);

            // Trim off any leading quote from from
            // the error message
            $this->custom_error = preg_replace('/^\'/', '', $error);
        }

        return $rule;
    }
    // }}}

    // {{{ Methods which are called by the rules
    function fail()
    {
        $args =  func_get_args();
        $str = array_shift($args);

        // Insert any args into placeholders

        if(count($args) > 0){

            $n = 1;

            foreach($args as $arg)
            {
                $tag = sprintf('{{arg%s}}', $n);
                $str = str_replace($tag, $arg, $str);
                $n ++;
            }
        }

        throw $this->exception($this->custom_error?:$str);
    }

    function stop()
    {
        $this->bail_out=true;
    }
    // }}}

    // {{{ Main rule application loop

    // Next are system fields, do not access when in doubt.
    public $acc=null;
    public $consumed=array();
    public $current_ruleset=null;
    public $custom_error=null;
    public $bail_out=false;

    /**
     * This is the main body for rule processing.
     */
    function applyRules($field,$ruleset)
    {
        // Save previous values, just in case
        $acc=$this->acc;
        $crs=$this->current_ruleset;
        $this->bail_out=false;

        $this->acc=$this->get($field);
        $this->current_ruleset=$ruleset;

        while(!is_null($rule=$this->pullRule())){

            $this->cast=false;
            $this->custom_error=null;

            if($rule == 'required')
                $is_required = true;

            // For debugging
            $tmp=null;
            $this->consumed=array($rule);

            try{
                if( (is_object($rule) || is_array($rule)) && is_callable($rule)){

                    $tmp = $rule($this,$this->acc,$field);

                }else{
                    // For to_XX rules
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
                if($this->cast)$this->set($field, $tmp);
                if($this->bail_out) break;

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
    // }}}

    /**
     * The only rule in Validator_Abstract. Will fail.
     */
    function rule_fail()
    {
        return $this->fail('Is incorrect');
    }

}
