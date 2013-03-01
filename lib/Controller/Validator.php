<?php
namespace romaninsh\validation;

/**
 * Implementation of hook-based validation / normalization controller.
 * Use this class to define rule-based validation for your model,
 * form, or arbitrary array.
 *
 * Example 1:
 *
 * $model->add('Controller_Validator')
 *     ->on('beforeSave')
 *     ->validate('age!','5..10') // [f] is mandatory, must be larger than 5
 *     ->validate('username','a-z') // must be letters, :alpha
 *     ->validate('username','a-z0-9') // :alpha_num
 *     ->validate('username',':len','5..10') // length of [f] must be larger than
 *     ->validate('salary',':money') // must be money
 *     ->validate('size','small,medium,large') // should be small, medium or large
 *     ->validate('pass1=pass2') // [f] must match [f2]
 *     ->validate('code~','/^0/') // must match expression
 *     ->validate('name',':up') // uppercases
 *     ->validate('surname',':caps') // first caps
 *     ->validate('text,url',':as','name') // use same validation
 *     ->validate('address2',':if','address1',':as','address1')
 *     ->validate('id',':ip') // must be valid ip
 *     ->validate('email',function($v){ return $v[0]; }) 
 *     ->validate('[group]',':len','>5') // group field len over 5
 *     ->validate('email','@afterLoad',':low')
 *     ->validate('*!')  // all fields are mandotory
 *     ;
 *
 * same rules:
 *
 *     ->validate('email!',':down','a-z')
 *     ->validate('email!|:down|a-z')
 *     ->validate('email!:down:a-z')
 *     ->validate('email:mandatory:down:a-z')
 *     ;
 *
 *
 * Goals of the validator: 
 *  - make validation as simple and accessible as possible
 *  - allow flexibility on when validation occurs
 *  - mix in with normalization, so you don't forget it
 *  - automaticaly come up with reasonable error messages
 *  - provide many ways to alias and make syntax sexy
 *
 * [ - group
 * [visible], [editable] - pseudogroups
 * * - all fields
 * [a-z] - selects all fields
 * :func (may cosume more arguments)
 * a-z - character range
 * .. - numeric range
 * 
 *
 *
 * $form->add('Controller_Validator')
 *     ->validate('*')->now();
 *
 *
 * $model->addField('email')->validate('email');
 *
 *
 * $this->add('Controller_Validator')
 *      ->with($my_data)
 *      ->validate(':myrules');
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
