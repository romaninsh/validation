<?php

class Test_Validator extends PHPUnit_Framework_TestCase {

    protected $validator;

    function __construct()
    {
        require_once( path('common') . 'libraries/validator.php');
        require_once( path('bundle') . 'intl/models/translation.php');
        Config::set('application.language', 'en_gb');
    }

    public function test_in_house_validators()
    {
        // boolean

        $validation = Validator::make(array('test' => true), array('test' => 'boolean'));
        $this->assertTrue($validation->passes());

        $validation = Validator::make(array('test' => false), array('test' => 'boolean'));
        $this->assertTrue($validation->passes());

        $validation = Validator::make(array('test' => 0), array('test' => 'boolean'));
        $this->assertTrue($validation->passes());

        $validation = Validator::make(array('test' => 1), array('test' => 'boolean'));
        $this->assertTrue($validation->passes());

        $validation = Validator::make(array('test' => '1'), array('test' => 'boolean'));
        $this->assertFalse($validation->passes());

        $validation = Validator::make(array('test' => 'x'), array('test' => 'boolean'));
        $this->assertFalse($validation->passes());

        // iso_date

        $validation = Validator::make(array('test' => '2000-01-01'), array('test' => 'iso_date'));
        $this->assertTrue($validation->passes());

        $validation = Validator::make(array('test' => '2000-12-31'), array('test' => 'iso_date'));
        $this->assertTrue($validation->passes());

        $validation = Validator::make(array('test' => '200-01-01'), array('test' => 'iso_date'));
        $this->assertFalse($validation->passes());

        $validation = Validator::make(array('test' => '2000-13-01'), array('test' => 'iso_date'));
        $this->assertFalse($validation->passes());

        $validation = Validator::make(array('test' => '2000-12-32'), array('test' => 'iso_date'));
        $this->assertFalse($validation->passes());

        // iso_time

        $validation = Validator::make(array('test' => '01:01:01'), array('test' => 'iso_time'));
        $this->assertTrue($validation->passes());

        $validation = Validator::make(array('test' => '24:01:01'), array('test' => 'iso_time'));
        $this->assertTrue($validation->passes());

        $validation = Validator::make(array('test' => '01:59:01'), array('test' => 'iso_time'));
        $this->assertTrue($validation->passes());

        $validation = Validator::make(array('test' => '01:01:59'), array('test' => 'iso_time'));
        $this->assertTrue($validation->passes());

        $validation = Validator::make(array('test' => '01:01:59.12345'), array('test' => 'iso_time'));
        $this->assertTrue($validation->passes());

        $validation = Validator::make(array('test' => '25:01:01'), array('test' => 'iso_time'));
        $this->assertFalse($validation->passes());

        $validation = Validator::make(array('test' => '01:60:01'), array('test' => 'iso_time'));
        $this->assertFalse($validation->passes());

        $validation = Validator::make(array('test' => '01:01:60'), array('test' => 'iso_time'));
        $this->assertFalse($validation->passes());

        // iso_datetime

        $validation = Validator::make(array('test' => '2000-01-01 01:01:01'), array('test' => 'iso_datetime'));
        $this->assertTrue($validation->passes());

        $validation = Validator::make(array('test' => '100-01-01 01:01:01'), array('test' => 'iso_datetime'));
        $this->assertFalse($validation->passes());

        // character_set

        $validation = Validator::make(array('test' => '123'), array('test' => 'character_set:123'));
        $this->assertTrue($validation->passes());

        $validation = Validator::make(array('test' => '123'), array('test' => 'character_set:0-9'));
        $this->assertTrue($validation->passes());

        $validation = Validator::make(array('test' => '123'), array('test' => 'character_set:23'));
        $this->assertFalse($validation->passes());

        $validation = Validator::make(array('test' => '123'), array('test' => 'character_set:2-9'));
        $this->assertFalse($validation->passes());

        // site_visit_id

        $validation = Validator::make(array('test' => 'AA00'), array('test' => 'site_visit_id'));
        $this->assertTrue($validation->passes());

        $validation = Validator::make(array('test' => 'AAA00'), array('test' => 'site_visit_id'));
        $this->assertFalse($validation->passes());

        // ad_response_code

        $validation = Validator::make(array('test' => 'AA00'), array('test' => 'ad_response_code'));
        $this->assertTrue($validation->passes());

        $validation = Validator::make(array('test' => 'AAA00'), array('test' => 'ad_response_code'));
        $this->assertFalse($validation->passes());

        // autoship_id

        $validation = Validator::make(array('test' => 'AAA000'), array('test' => 'autoship_id'));
        $this->assertTrue($validation->passes());

        $validation = Validator::make(array('test' => 'AAA00'), array('test' => 'autoship_id'));
        $this->assertFalse($validation->passes());

        // tag

        $validation = Validator::make(array('test' => 'foo, foo_far'), array('test' => 'taglist'));
        $this->assertTrue($validation->passes());

        $validation = Validator::make(array('test' => 'foo| foo_far'), array('test' => 'taglist'));
        $this->assertFalse($validation->passes());

        // credit_card (we use dummy test numbers from http://bradconte.com/cc_generator.html)

        $good_cards = array(


        '4943014034259500',
        '4843065387981729',
        '4119148966967',
        '4037296037813',
        '5104077633496247',
        '5232353603130222',
        );

        foreach($good_cards as $card)
        {
            $validation = Validator::make(array('test' => $card), array('test' => 'credit_card'));
            $this->assertTrue($validation->passes());
        }
    }
}
