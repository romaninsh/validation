Agile Validation Library
=====

This is a validation library for your Aglie Toolkit applications and more.

Goals of Aglie Validation
----
The reasons why I have created this library instead of re-using 3rd
party is that there are very few validation rule-based libraries with a
simple and consise syntax, which can be intuitively understood. 

When creating this library, I also persuaded additional goals:

### 1. Minimalistic syntax
Developers avoid validation, becasue it requires them to write a lot
of code. Validation should be easy to define and easy to update. Current
libraries require the use of complex structures and require a lot of
writing. With my library you can be clever about defining the rules:

```
->validate([
    'name,surname|mandatory|len|5..100',
    'postcode|to_alpha|to_trim|uk_zip?Not a UK postcode',
    'postcode2|as|postcode'
   ]);
```

The above code will make sure that name and surname are specified and that
the length of name and surname is bitween 5 and 100 characters. Should
any rule not match, the error will be returned with appropriate syntax.

Second line demonstrate normalization. Without equation sign (=) alpha
and trim would produce errors but with equation they work as
normalization methods. They will filter input and remove unnecessary
spaces. Any command can be postfixed with ? and a custom error message.

Final line shows how one rule can be inherited.

### 2. Flexibility
The validation is performed by validation controller, which initially
initializes a basic set of rules. There are several classes which extend
the base validation by adding additional rules to it. Some controllers
may imply additional restrictions on what or when is being validated. 

At the same time, each operator which you can use in a validator, is
actually a method. If any section uses more than default set of characters,
then additional checks are performed.

    validate('user_email|email');

will execute `$v->v_email('john@doe.com', 'email', &$array)`

by defining additional methods, new rules can emerge. You can also use
controllers to register additional methods (registerMethod) inside
validator.

### 3. Integration
Validator can work on it's own but is neatly integrated with the rest of
Agile Toolkit. You still have controll over when the action is
performed. By default validation occurs before saving model, but this
can be changed.

### 4. Exceptions
Agile Toolkit already supports custom validation and can perfectly
understand excetpions. Validator simply helps you provide additional rules.

Syntax
----

### Most basic use
By calling validate() method you can specify 3 types of arguments:

```
->validate('field|rule|rule|rule');
->validate('field','rule','rule','rule');
```

Both calls produce identical results. You can also use single-argument format by specifying array of rules:

```
->validate(array(
   'field|rule|rule','field|rule|rule'
  )) 
or
->validate(array(
    array('field','rule','rule'),
    array('filed','rule','rule')
  ));
```

Calling ->validate() will only record the rules inside controllers and
will not yet do anything. There are 2 ways how to actually start
performing the validation:

```
  ->now(); // will perform validation right away

  ->on('hookName'); // will use owner's hook to validate.
``` 
  
Validation
rules inside models use beforeSave by default. Calling this multiple
time will replace previous value.


### Rule processing
As you saw rules can be defined by separating with pipe character or
array elements. Method validate() will actually take any number of arguments,
so you dont need to worry about syntax. It will process rules
one-by-one. Some rules may consume additional argument, for example:

    ->validate('name','in',$values);

Using "as" and "asif" will take all rules from another field and will
apply them to a current field.

### Field definition
First argument always defines field. The validators method expandField
is responsible for converting the notation into list of fields.

Examples:

 - single field: "email"
 - multiple fields: "email,name,surname"
 - all fields: "*"
 - excluding fields: "*,-name,-surname"

You may also specify list of fields with array (except when you are
using pipe character). Some validator extensions may define additional
ways to define fields, for example when defining model's rules, you can
also use field groups:

```
$model->addField('address')->group('addr');
$model->addField('zip')->group('addr');
$model->validate('[addr],!');
```

### Exclamination mark

If field ends with exclamination sign, then 'mandatory' rule is invoked
right after. Exclamination mark may also appear after rules, in which
the 'mandatory' will be insterted after that particular Rules

    ->validate('name|trim!')

### Aliases
Rules can contain only lower_case characters and underscores because
they are actually methods of a validator class, but some of those can be
shortened using aliases. Here are some examples:

 - a-z -> alpha
 - a-z0-9 -> alpha_num
 - 0-9a-z -> alpha_num
 - ! -> mandatory

### Convertors
Some rules will change the value of the field which will then be parsed
to next rules. 

 - len - replace value of field by it's length
 - trim - replace value of field by trimming it

### Filters
Some rules can also act as a filters. Most of the rules are defined in
such a way that they can also filter a usable part of the string for you.
To use rule as a filter, it must be prepented with equation sign.

 - Calling "to_a-z" rule on "Hello World" will return "elloorld".
 - Calling "to_int" rule on "25.2" will produce 25.

### Error messages
Each rule have an appropriate error message defined. For example rule
">20" produces message "%s must me more than %n". By default %s will
contain caption or name of the field. If you have used some convertors
they may also alter that name and use "length of %s" there with the
resulting message:

    "length of Name must be more than 20"

You can specify a custom error message if you append it through question
mark to a rule:

    >20?Must be over 20

### Custom Filters
Sometimes you would like to specify a custom rule yourself. In this case
you can pass a callable to a validator. 

```
->validate('birthdate',function($v){
  $d=new DateTime($v);
  return $d->diff(new DateTime($v))->format('%y')
 },'>=18?Must be at least 18 years old');
```

### Special formats
If after applying aliases rule still contains non-alpha characters, then
few more things are checked:

 - /xx/ (starts with slash) changed to => `'regexp','/xx/'`
 - a-c (containing dash) changed to => `'regexp','^[a-c]*$'`
 - 3..9 (containing ..) changed into `'between',3,9`
 - `>5, <5, >=5, <=5, =5, !=5` are changed into `gt, lt, ge, le, eq, ne`

### And and Or
If you specify multiple rules for a same field, they are joined using
"and" logic. In other words - they must all match. You can also use "or"
logic:

```
->validate(':or', rules1, rules2, rules3)
->validate(':and', rules1, rules2, rules3)
```

Example:

```
->validate(
  ':or?Must be male over 10y or female over 12y',
  array(':and','gender|=M','age>10'),
  array(':and','gender|=F','age>12')
 )
```

### Not
This rule will reverse the outcome of the next validation. 

    ->validate('gender|not|a-z')

Will produce error "Gender must not consist of latin characters". Error
messages will have "must be" replaced with "must not" and sometimes may
not make sense, so consider using a custom error message.

### if (array)
By default 'if' rule consumes next argument and uses it as to see if
the other field is specified. What if you would like to use a more
sophisticated check? If supports sub-rules (just lake :or / :and)

    ->validate('addr','if',array('method','=','deliver'))

You can also use a call-back:

```
->validate('addr','if?Must specify address if you deliver',
 function($addr,$addr_name,$data){
  return $data['method']=='deliver';
})
```

Rule 'if' will consume up to 3 arguments if you specify them. You can
skip argument by supplying null or just empty string. The first argument
can be a call-back or sub-rule. If second argument is not specified,
then the field will simply be mandatory. If it is specified it is then
used as a rule, which will only apply when if is true. Third argument is
"else"-rule.

```
->validate('delivery_to','if','home','[home_addr]!','[work_addr]!')
```


### Comparing fields
When you use comparison operatiors either by their alias ('=')
or by using the rule name 'eq', you specify the value:

```
->validate('gender=M')
->validate('gender','=','M')
->validate('gender','eq','M')
```

if you want to compare with other field, then can use the colomn in the
short syntax:

```
->validate('pass1=:pass2')
->validate('pass1','=:','pass2')
->validate('pass1','eqf','pass2');
```

Same rule applies to other 5 comparison operators.

### Member of array
using "in" and "!in" (or not_in) you can verify if element is inside set
of allowed values:

```
->validate('gender|in|M,F')
->validate('gender','in',array('M','F'))
```

The second format allows you to use any value inside array, they can
even contain commas or pipes.

### Database-queries
If you are willing to use some model-magic extensions, then you would need
to use "Controller_Validator_ORM": There is a separate documentation on 
it, but here I'll just give you some samples:

```
->validate('email|unique')
->validate('email','in',$dsql) // specify a sub-query
->validate('user_id','loadable','User')  // valid record of another model
```

### Field-specific validation
When you call field->validate(), the field may extend your validadator
of choice by injecting some additional rules. For instance image field
will add additional rules:

```
->validate('picture_id','larger','50x50')
->validate('picture_id','format','jpeg')
->validate('picture_id','size','<5000')
->validate('picture_id','size','kb','<5')
```


Here "larger" rule will require images to be 50x50 resolution at least.
"format" is convertor which will substitute picture into format of file,
"size", "height", "width" all are also convertors.

### Units
Use of convertors allows us to easily operate with units. For example,
"kb" and "mb" are also convertors which divide current value by
1024 or 1024*1024. This is handy when used with "size" for file size.

### Multi-field operations, copy
Sometimes you would like to perform operation between multiple fields,
such as storing length of one filed inside another or splitting a field
into two fields. This can be done by applying convertors carefully:

```
->validate('name|copy|full_name|to_regexp|/.* /')
->validate('surname|copy|full_name|to_regexp|/ .*/')
->validate('name_length|copy|name|to_len')
```

### Other Examples

```
->validate([
 'email|to_email|!', # convert to email and must not be empty
 'base_price|to_int|10..100',  # convert to int, and bust me within range
 'postcode|to_upper|to_trim|to_A-Z|postcode', # clean up postcode then validate
 'pass1|=:pass2',
 'country_code|upper|in|UK,US,DE,FR', # uppercase for comparison only
 'addr2|asif|addr1', # validate addr2 like addr1 if addr1 is present
```


### Changing hook

As I mentioned, by default validation is performed on beforeSave hook.
If used with form, the validation is performed during submit. It is possible
to change the hook for a specific rule by using @hook. This is converted
into "on" rule:

```
->validate('name|to_lower|@afterLoad')
->validate('name|to_lower|on|afterLoad')
->validate('name','to_lower','on',array($api,'post-init'))
```

This will affect only a single rule and may result in creation of
another copy of Controller_Validatior, so use ->on method of a validator
instead of using this for every single rule.
