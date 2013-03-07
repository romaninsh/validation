<?php
namespace romaninsh\validation;

/**
 *
 *
 *
 *
 *        WORK IN PROGRESS!!! 
 *
 *
 *
 *
 *
 * Implementation of hook-based validation / normalization controller.
 * Use this class to define rule-based validation for your model,
 * form, or arbitrary array.
 *
 */

class Controller_Validator extends \AbstractController {
    public $_conf;
    public $configure=null;

    public $aliases=array(
        'a-z'=>':alpha','a-z0-9'=>':alpha_num',
        '?'=>':boolean',
    );


    function init(){
        parent::init();
        // you can specify configuration thourgh add's
        // second parameter: add('..',array('configure'=>..));
        // or chain.
        if($this->configure)$this->configure($this->configure);
    }
    function configure($callback,$config){
        if($callback===true){
            $this->parse($this,$config);
        }else{
            $this->owner->addHook($callback,array($this,'parse'),array($config));
        }
        return $this;
    }

    function parseRule($rule)
    {
        

    }

    function parse($m,$config)
    {
        foreach ($config as $field => $rules) {
            if (is_string($rules)) {
                $rules=explode(',', $rules);
            }

            $v_orig=$v=$this->owner[$field];


            foreach ($rules as $rule) {
                $args=explode(':',$rule);
                $rule=array_shift($args);

                if(substr($rule,-1)=='!'){
                    $rule=substr($rule,0,-1);
                    $throw=true;
                }else $throw=false;
                $e=false;

                switch ($rule) {
                    case 'int':
                        $e='Should be integer';
                        $v=(int)$v;
                        break;
                    case 'max':
                        $e='Must be no bigger than %s';
                        if($v>$arg[0])$v=$arg[0];
                        break;
                    case 'min':
                        $e='Must be at least %s';
                        if($v<$arg[0])$v=$arg[0];
                        break;
                    case 'between':
                        $e='Must be between %s and %s';
                        if($v<$arg[0])$v=$arg[0];
                        if($v>$arg[1])$v=$arg[1];
                        break;
                    case 'email':
                        // TODO: clean up email
                        break;
                    default:

                }
                if($v!==$v_orig && $throw){
                    throw $this->exception($e?:'Validaiton error','ValidityCheck')
                        ->setField($field)
                        ;
                }
            }

            $v_orig=$v=$this->owner[$field];
        }
    }
}
