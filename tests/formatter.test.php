<?php

class Test_Formatter extends PHPUnit_Framework_TestCase {

    function __construct()
    {
        require_once(path('bundle') . 'entity/libraries/formatter.php');
    }

    public function test_formatters()
    {
        $this->assertEquals('foohoo boo', Formatter::to_lower('FooHoo Boo'));
        $this->assertEquals('FOOHOO BOO', Formatter::to_upper('FooHoo Boo'));
        $this->assertEquals('Foohoo boo', Formatter::to_upper_first('foohoo boo'));
        $this->assertEquals('Foohoo Boo', Formatter::to_upper_words('foohoo boo'));
        $this->assertEquals('foo', Formatter::trim('  foo '));

        $this->assertEquals('1',Formatter::truncate('1234567890', 1));
        $this->assertEquals('1234', Formatter::truncate('1234567890', 4));
    }

    public function test_filters_and_sanitisers()
    {
        $this->assertEquals('1234', Formatter::to_digits('&*x 1234 +#cN'));
        $this->assertEquals('1 1234 1', Formatter::to_digits_and_single_spaces('&*x1  1234 1+#cN'));
        $this->assertEquals('1123.5', Formatter::to_numeric('&*x1  123.x 5+#cN'));
        $this->assertEquals('x112341cN', Formatter::to_alphanumeric('&*x1  1234 1+#cN'));
        $this->assertEquals('abcABC', Formatter::to_alpha('a 123!"£bcABC!'));
        $this->assertEquals('1234 567 890', Formatter::to_phone('(1234 )  567-890 '));
        $this->assertEquals('1994-11-05T08:15:30-05:00', Formatter::to_iso_date('199|£$%ab~+!|4-11-05T08:15:30-05:00'));
        $this->assertEquals('12/12/12', Formatter::to_short_date('x12/12/\$*#12!'));
        $this->assertEquals('foo_hoo-Boo', Formatter::to_system_id('foo_hoo|!"£$%^&*()"|-Boo'));
        $this->assertEquals('/dir/This_is-MyFile.php', Formatter::to_unix_file_path('/dir/This|#=<>?$&|_is-MyFile.php'));
        $this->assertEquals('|###thisisatest!!!|', Formatter::strip_space('  |# ##thi   sisatest!!!|  '));
        $this->assertEquals(' |# ##thi s is a test!| ', Formatter::strip_extra_space('  |# ##thi     s is a test!|  '));
        $this->assertEquals('John De-Groot 14th.', Formatter::strip_nasties('John De|*^<>?!"()|\/[]|-Groot 14th.'));
        $this->assertEquals('xxxxxxxxxxxxxxx', Formatter::strip_markup_tags('<markup>xxxx</markup>xxxx<more markup>xxxxxxx'));
    }

	public function test_names()
	{
        $tests = array(
            'smith' => 'Smith',
            'sMith' => 'Smith',
            'SMITH' => 'Smith',
            'mr     extra   space' => 'Mr Extra Space',
            'p smith' => 'P Smith',
            'mr p smith' => 'Mr P Smith',
            'mr. p. smith' => 'Mr. P. Smith',
            'john smith' => 'John Smith',
            'mcdonald' => 'McDonald',
            'joy macdonald' => 'Joy Macdonald',
            'robert MacDonald' => 'Robert MacDonald',
            'Macon' => 'Macon',
            'mcdoNald-SMYTHE' => 'McDonald-Smythe',
            'smythe-Mcdonald' => 'Smythe-McDonald',
            'von MacDonald iii' => 'von MacDonald III',
            "o'grady" => "O'Grady",
            "van o'grady" => "van O'Grady",
            "van o'grady jr." => "van O'Grady Jr.",
            'de la rue' => 'de la Rue',
            'de la rue 1st.' => 'de la Rue 1st.',
            'ap DAVIs' => 'ap Davis',
            'john ap davis' => 'John ap Davis',
            'smythington-twistle' => 'Smythington-Twistle',
            'smythington-twistle-de-groot' => 'Smythington-Twistle-De-Groot',
            'mary-jo von syMington-mcDonald-twistle 3RD' => 'Mary-Jo von Symington-McDonald-Twistle 3rd',
            'st john' => 'St John',
            'st. john iV' => 'St. John IV',
        );

        foreach($tests as $input => $expected)
        {
            $this->assertEquals($expected, Formatter::name($input));
        }
	}
}
