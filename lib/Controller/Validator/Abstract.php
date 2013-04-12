<?php // vim:ts=4:sw=4:et:fdm=marker
namespace romaninsh\validation;

class Controller_Validator_Abstract extends \AbstractController {

    public $rules=array();

    public $default_exception='Exception_ValidityCheck';

    public $alias=array();  // legacy=>new

    public $source=null;

    public $active_field; // The field being processed

    // TODO: Multibyte stuff: refactor to a better place??
    public $encoding='UTF-8';
    public $is_mb = false; // Is the PHP5 multibyte lib available?


    function init()
    {
        parent::init();
        $that=$this;

        if(function_exists('mb_get_info')){

            $this->is_mb = true;
        }

        $this->source=$this->owner; // must support set/get interface

        if ($this->source instanceof \Model) {
            $this->source->addMethod('is', function($m) use ($that){
                $args=func_get_args();
                array_shift($args);

                call_user_func_array(array($that,'is'),$args);
                $that->on('beforeSave',null,true);
                return $m;
            });
        }
    }

    //  Rule initialization and normalization methods

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

        $p = "/(?:[^\|\']|\'((?<=\\\\)\'|[^\'])*\')*/x";
        preg_match_all($p, $rules, $matches);

        $chain = array();
        $n = 1;

        foreach($matches[0] as $rule)
        {
            if( ! empty($rule))
            {
                // Trim whitespace and quotes from ends of rule

                $rule = trim($rule, " '");

                if($n == 1)
                {
                    $field = $rule;
                }
                else
                {
                    $chain[] = $rule;
                }

                $n ++;
            }
        }

        return array($field, $chain);
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

    //

    //  Supplimentary configuration methods
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

    //

    //  Internal Methods to be used by rule_*


    //

    //  Methods which are essential when applying rules
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
     */
    function get_active_field()
    {
        return $this->active_field;
    }

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
        $is_required = false;

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

            if($is_required || $this->acc !== '')
            {
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
        }
        $this->acc=$acc;
        $this->current_ruleset=$crs;
    }

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

    //

    /**
     * Will process individual rule
     */
    function rule_fail()
    {
        return $this->fail('Is incorrect');
    }

    ////////////////////////
    // MB STRING UTILITIES
    // TODO: refactor to a more sensible place?
    // TODO Any case for an app config setting
    // for encoding, perhaps with a default of UTF-8?
    ////////////////////////

    function mb_str_len($a)
    {
         return ($this->is_mb) ? mb_strlen($a, $this->encoding) : strlen($a);
    }

    function mb_str_to_lower($a)
    {
        return ($this->is_mb) ? mb_strtolower($a, $this->encoding) : strtolower($a);
    }

    function mb_str_to_upper($a)
    {
        return ($this->is_mb) ? mb_strtoupper($a, $this->encoding) : strtoupper($a);
    }

    function mb_str_to_upper_words($a)
    {
        if ($this->is_mb)
        {
            return mb_convert_case($value, MB_CASE_TITLE, $this->encoding);
        }

        return ucwords(strtolower($value));

    }

    function mb_truncate($a, $len, $append = '...')
    {
        if ($this->is_mb)
        {
            return mb_substr($value, 0, $len, $this->encoding) . $append;
        }

        substr($value, 0, $limit).$end;
    }
}
