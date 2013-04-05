<?php
namespace romaninsh\validation;

class Controller_Validator_Basic extends Controller_Validator_Abstract {

    function init()
    {
        parent::init();

        $this->alias=array_merge($this->alias,
            array(
                'same'=>'eq',
                'different'=>'ne',
            )
        );
    }

    //
    // SINGLE VALUE VALIDATION RULES
    //

    /**
     * Inclusive numeric range check
     */
    function rule_range($a)
    {
        $min=$this->pullRule();
        $max=$this->pullRule();
        if($a < $min || $a > $max) return $this->fail('Number must between {{arg1}} and {{arg2}}');
    }

    /**
     * Inclusive length range check
     *
     * Next 2 rules must specify the min
     *
     */
    function rule_between($a)
    {
        $min=$this->pullRule();
        $max=$this->pullRule();
        $len = strlen($a);
        if($len < $min || $len > $max) return $this->fail('Must be between {{arg1}} and {{arg2}} characters long');
    }

    function rule_length($a)
    {
        $target=$this->pullRule();
        $actual = strlen($a);
        if($target != $actual) return $this->fail('Must be {{arg1}} characters long');
    }

    function rule_int($a)
    {
        if( ! filter_var($a, FILTER_VALIDATE_INT)){
            return $this->fail('Must be an integer');
        }

        return (int)$a;
    }

    /**
     * Test for A-Za-z
     */
    function rule_alpha($a)
    {
        $msg = 'Must contain only letters';
        if(!preg_match('/^([A-Za-z])+$/', $a)) return $this->fail($msg);
    }

    /**
     * Test for unicode letter characters
     *
     * Requires PCRE compiled with  "--enable-unicode-properties".
     * Most distros these days will offer this
     */
    function rule_alpha_unicode($a)
    {
        $msg = 'Must contain only letters';
        if(!preg_match('/^([\p{L}])+$/', $a)) return $this->fail($msg);
    }

    /**
     * Test for A-Za-z0-9
     */
    function rule_alpha_num($a)
    {
        $msg = 'Must contain only digits and letters';
        if(!preg_match('/^([a-zA-Z0-9])+$/', $a)) return $this->fail($msg);
    }

    /**
     * Test for unicode letter characters and digits
     *
     * Requires PCRE compiled with  "--enable-unicode-properties".
     * Most distros these days will offer this
     */
    function rule_alpha_num_unicode($a)
    {
        $msg = 'Must contain only letters and numbers';
        if(!preg_match('/^([\p{L}0-9])+$/', $a)) return $this->fail($msg);
    }

    /**
     * Test for A-Za-z0-9_-
     */
    function rule_alpha_num_dash($a)
    {
        $msg = 'Must contain only letters, numbers and dashes';
        if(!preg_match('/^([a-zA-Z0-9_-])+$/', $a)) return $this->fail($msg);
    }

    /**
     * Test for unicode letter characters and digits
     *
     * Requires PCRE compiled with  "--enable-unicode-properties".
     * Most distros these days will offer this
     */
    function rule_alpha_num_dash_unicode($a)
    {
        $msg = 'Must contain only letters, numbers and dashes';
        if(!preg_match('/^([\p{L}0-9_-])+$/', $a)) return $this->fail($msg);
    }

	/**
	 * Validate for true|false|t|f|1|0|yes|no|y|n
     *
     * Normalizes to lower case
	 */
	function rule_bool($a)
	{
        // We don't use PHP inbuilt test - a bit restrictive

        // Changes PHP true/false to 1, 0
        $a = strtolower($a);

        $vals = array('true', 'false', 't', 'f', 1, 0, 'yes', 'no', 'y', 'n');

        if(! in_array($a, $vals)){
            return $this->fail('Must be a boolean value');
        }
    }

	/**
	 * Validate for true|t|1|yes|y
     *
     * Normalizes to lower case
	 */
	function rule_true($a)
	{
        // Changes PHP true to 1
        $a = strtolower($a);

        $vals = array('true', 't', 1, 'yes', 'y');

        if(! in_array($a, $vals)){
            return $this->fail('Must be true');
        }
    }

	/**
	 * Validate for false|f|0|no|n
     *
     * Normalizes to lower case
	 */
	function rule_false($a)
	{
        // Changes PHP false to 0
        $a = strtolower($a);

        $vals = array('false', 'f', 0, 'no', 'n');

        if(! in_array($a, $vals)){
            return $this->fail('Must be false');
        }
    }

    function rule_email($a)
    {
        if( ! filter_var($a, FILTER_VALIDATE_EMAIL)){
            return $this->fail('Must be a valid email address');
        }
    }

    /**
     * Requires a regex pattern as the
     * next rule in the chain.
     *
     * Please give your rule a custom error message to
     * expain why it has failed.
     */
    function rule_regex($a)
    {
        $opt['regexp'] = $this->pullRule();

        if( ! filter_var($a, FILTER_VALIDATE_REGEXPR, $opt)){
            return $this->fail('Not a valid value');
        }
    }

	/**
	 * Validate for ISO date in format YYYY-MM-DD
     *
     * Also checks for valid month and day values
	 */
	function validate_iso_date($a)
	{
        $date = explode('-', $a);

        $msg = 'Must be date in format: YYYY-MMM-DD';

        if(count($date) != 3) return $this->fail($msg);

        if(strlen($date[0]) !== 4 || strlen($date[1]) !== 2 || strlen($date[2]) !== 2) return $this->fail($msg);

        if(! @checkdate($date[1], $date[2], $date[0])) return $this->fail($msg);
    }

	/**
	 * Validate for ISO time
     *
     * Requires a complete hour:minute:second time with
     * the optional ':' separators.
     *
     * Checks for hh:mm[[:ss][.**..] where * = microseconds
     * Also checks for valid # of hours, mins, secs
	 */
	function rule_iso_time($a)
    {
        $pattern = "/^([0-9]{2}):([0-9]{2})(?::([0-9]{2})(?:(?:\.[0-9]{1,}))?)?$/";
        $msg = 'Must be a valid ISO time';

        if(preg_match($pattern, $a, $matches))
        {
            if($matches[1] > 24) return $this->fail($msg);

            if($matches[2] > 59) return $this->fail($msg);

            if(isset($matches[3]) && $matches[3] > 59) return $this->fail($msg);
        }
        else
        {
            return $this->fail($msg);
        }
    }

	/**
	 * Validate ISO datetime in the format:
     *
     * YYYY-MM-DD hh:mm:ss with optional microseconds
	 */
	function rule_iso_datetime($a)
    {
        $parts = explode(' ', $a);
        $msg = 'Must be a valid ISO datetime';

        if(count($parts) != 2) return $this->fail($msg);

        try {$this->rule_iso_date($parts[0]);} catch (Exception $e){return $this->fail($msg);}
        try {$this->rule_iso_time($parts[1]);} catch (Exception $e){return $this->fail($msg);}
    }

    /**
     * Checks any PHP datetime format:
     * http://www.php.net/manual/en/datetime.formats.date.php
     */
    function rule_before($a)
    {
        $time = $this->pullRule();
        if(strtotime($a) >= strtotime($time)) return $this->fail('Must be before {{arg1}}');;
    }

    /**
     * Checks any PHP datetime format:
     * http://www.php.net/manual/en/datetime.formats.date.php
     */
    function rule_after($a)
    {
        $time = $this->pullRule();
        if(strtotime($a) <= strtotime($time)) return $this->fail('Must be after {{arg1}}');;
    }

	/**
     * Validate for credit card number
     *
     * Uses the Luhn Mod 10 check
	 */
	function rule_credit_card($a)
    {
        // Card formats keep changing and there is too high a risk
        // of false negatives if we get clever. So we just check it
        // with the Luhn Mod 10 formula

        // Calculate the Luhn check number

        $msg = 'Not a valid card number';

        $sum = 0;
        $alt = false;

        for($i = strlen($a) - 1; $i >= 0; $i--){
            $n = substr($a, $i, 1);
            if($alt){
                //square n
                $n *= 2;
                if($n > 9) {
                    //calculate remainder
                    $n = ($n % 10) +1;
                }
            }
            $sum += $n;
            $alt = !$alt;
        }

        // If $sum divides exactly by 10 it's valid

        if (! ($sum % 10 == 0))
        {
            return $this->fail($msg);
        }
        else
        {
            // Luhn check seems to return true for any string of 0s

            $stripped = str_replace('0', '', $a);

            if(strlen($stripped) == 0)
            {
                return $this->fail($msg);
            }
        }
    }

	/**
     * Validate a card "expires end" date
     *
	 * @param  string  $attribute
	 * @param  mixed   $a
	 * @return bool
	 */

	function rule_card_to_date($a)
    {

        $msg = 'Not a valid date';
        if(!$this->card_date_helper($a, 'to')) return $this->fail($msg);
    }

	/**
     * Validate a card "valid from" date
     *
	 * @param  string  $attribute
	 * @param  mixed   $a
	 * @return bool
	 */
	function rule_card_from_date($a)
    {
        if(!$this->card_date_helper($a, 'from')) return $this->fail($msg);
    }

	/**
     * Helper for validating card to and from dates
     *
	 * @param  string  $a
	 * @param  mixed   $type
	 * @return bool
	 */
    function card_date_helper($a, $type)
    {
        // Strip out any slash

        $date = str_replace('/', '', $a);

        // Check that we have 4 digits

        if(! preg_match("|^[0-9]{4}$|", $date))
        {
            return false;
        }

        $month = substr($date, 0, 2);
        $year = substr($date, 2, 2);

        // Check month is logical

        if($month > 12)
        {
            return false;
        }

        $parts = array( date('Y'), date('m'), 1);
        $now_datetime = new DateTime(implode('-', $parts));

        $parts = array('20' . $year, $month, '1');
        $card_datetime = new DateTime(implode('-', $parts));

        $interval = $now_datetime->diff($card_datetime);
        $days = $interval->format('%R%a days');

        if($type == 'from')
        {
            // Check from date is older or equal to current month

            if($days <= 0 && $days > -3650)
            {
                return true;
            }
            else
            {
                return false;
            }

        }
        elseif($type == 'to')
        {
            // Check to date is newer or equal to current month

            if($days >= 0 && $days < 3650)
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
           $msg = "Bad date type '$type' in card-date validation";
           throw new Error($msg);
        }

        return $true;
    }

    //
    // VALUE COMPARISON FILTERS
    //

    function rule_eq($a){
        $b=$this->pullRule();
        if($a!=$b) return $this->fail('Must be equal to {{arg1}}');
    }

    function rule_ne($a){
        $b=$this->pullRule();
        if($a==$b) return $this->fail('Must not be equal to "{{arg1}}"');
    }

    function rule_eqf($a){
        $b=$this->pullRule();
        if($a!=$this->get($b)) return $this->fail('Must be same as {{arg1}}');
    }


    function rule_nef($a){
        $b=$this->pullRule();
        if($a==$this->get($b)) return $this->fail('Must not be same as {{arg1}}');
    }

    //
    // NORMALIZATION & SANITIZATION
    //
    // These rules change the value being
    // tested in subsequent rules.
    //

    /**
     * Changes the value being tested
     * to the length of the original value.
     *
     * Run tests on the original value
     * before calling this!
     */
    function rule_len($a)
    {
        return strlen($a);
    }

    function rule_trim($a)
    {
        return trim($a);
    }

    function rule_ltrim($a)
    {
        return ltrim($a);
    }

    function rule_rtrim($a)
    {
        return rtrim($a);
    }

    function rule_to_int($a)
    {
        return (int)$a=preg_replace('/[^0-9]/', '', $a);
    }

    /**
     * Strip to A-Za-z0-9
     */
    function rule_to_alpha($a)
    {
        return preg_replace('/[^a-zA-Z]/', '', $a);
    }

    /**
     * Test for unicode letter characters
     *
     * Requires PCRE compiled with  "--enable-unicode-properties".
     * Most distros these days will offer this
     */
    function rule_to_alpha_unicode($a)
    {
        return preg_replace('/[^\p{L}]/', '', $a);
    }

    function rule_to_alpha_num($a)
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', $a);
    }

    /**
     * Test for unicode letter characters and 0-9
     *
     * Requires PCRE compiled with  "--enable-unicode-properties".
     * Most distros these days will offer this
     */
    function rule_to_alpha_num_unicode($a)
    {
        return preg_replace('/[^\p{L}0-9]/', '', $a);
    }

    function rule_to_alpha_num_dash($a)
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $a);
    }

    /**
     * Test for unicode letter characters and 0-9, -, _
     *
     * Requires PCRE compiled with  "--enable-unicode-properties".
     * Most distros these days will offer this
     */
    function rule_to_alpha_num_dash_unicode($a)
    {
        return preg_replace('/[^\p{L}0-9_-]/', '', $a);
    }

    // TO DO: what about decimal formats for other locales?
    function rule_to_decimal($a)
    {
        return preg_replace('/[^0-9\.]/', '', $a);
    }

    function rule_strip_tags($a)
    {
        return strip_tags($a);
    }

    function rule_to_lower($a)
    {
        return strtolower($a);
    }

    function rule_to_upper($a)
    {
        return strtoupper($a);
    }

    function rule_to_upper_first($a)
    {
        return ucfirst($a);
    }

    function rule_to_upper_words($a)
    {
        return ucwords($a);
    }

    /**
     * Requires next rule to set
     * str length for truncation
     */
    function rule_truncate($a)
    {
        $len=$this->pullRule();
        return substr($a, 0, $len);
    }

    function rule_to_digits_and_single_spaces($a)
    {
        $a= preg_replace("/[^\d ]/", '',$a);
        return $this->rule_strip_extra_space($a);
    }

    function rule_to_iso_date($a)
    {
        $a = preg_replace("/[^T0-9\/\-\(\): ]/", '',$a);
        return $this->rule_iso_date($a);
    }

    /**
     * Strips all but A-Za-z0-9_-
     */
    function rule_to_system_id($a)
    {
        return preg_replace("|[^A-Za-z0-9_-]|", '' , $a);
    }

    /**
     * Strip out all white space
     */
    function rule_strip_space($a)
    {
        return preg_replace("/\s/", "",$a);
    }

    /**
     * Reduce sequential whitespaces to a single space
     */
    function rule_strip_extra_space($a)
    {
        // 1) Replace all whitespace with a space char

        $a= preg_replace("/\s/", " ",$a);

        // 2) Turn multiple space to a single space

        return preg_replace("/[ ]{2,}/", " ",$a);
    }

    /**
     * Strip out attack characters from names & addresses
     * and other strings where they have no place
     */

    function rule_strip_nasties($a)
    {
        return preg_replace("|[\*\^<?>!\"\(\)\|\\\\/\[\]\+=#%;~`]|", '' , $a);
    }

    // *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-
    // PERSONAL NAMES (European style)
    // *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-

    /**
     * Useful for cleaning up input where you don't want to present
     * an error to the user - eg a checkout where ease of use is
     * more important than accuracy.
     *
     * Some libraries don't clean up names if already in mixed case.
     * Experience shows this isn't very useful, as many users
     * will type mAry, JOSepH etc.
     *
     * Can only be a best guess - but much better than nothing:
     * has been in production for years without any negative
     * customer feedback.
     *
     * Set $is_capitalise_prefix if you want prefixes in upper:
     * Von Trapp vs von Trapp. You would do this to format
     * a last name for use in salutations:
     *
     * Dear Mr Von Trapp
     *
     * Can handle full names, 1st only, middle only, last only.
     *
     * Cleans up extra whitespace.
     *
     * @param string $name
     * $param bool $is_capitalise_prefix
     * @return string
     */

    function rule_to_name($name, $is_capitalise_prefix = false)
    {
        /*
        A name can have up to 5 components, space delimited:

        Worst case:

            salutation  |  forenames 	| prefix(es)	|	main name 	|	suffix
            Ms          |  Jo-Sue Ellen	| de la 		| 	Mer-Savarin | 	III

        Rules for forenames

        1) Capitalise 1st char and after a hyphen.

        Rules for special case prefixes: von, de etc

        1) Set capitalisation at runtime.

            There seem to be no fixed rules, but
            lower case is commonly used as part of a
            whole name:

            John von Trapp

            While it is normally capitalised as part of
            a salutation:

            Dear Mr Von Trapp

            By default we store in the lower case form.
            Set the param $is_capitalise_prefix to TRUE
            to capitalise.

        2) In default mode, St is capitalised,
            other prefixes lower cased. We retain user's
            choice of punctuation with St./St

        Rules for main name:

        1) Capitalise after a hyphen in the main name:

            Smythington-Fenwick

        2) Capitalise after Mc at start of name -
            this is pretty much a universal rule

            MCDONALD => McDonald

        3) Unless user has capitalised after the M,
            do NOT capitalise after Mac at start of name:

            - Many Scottish Mac names
                are not capitalised: Macaulay, Macdonald

            - Many non-Scottish names start with Mac:
                eg Macon - we want to avoid "MacOn";

            - The Cpan name modules and some style manuals
                force MacDonald, but this seems to
                create more problems than it solves.

            macrae => Macrae

        4) Capitalise after O'

            o'grady => O'Grady
        */

        // If name string is empty, bail out

        if(empty($name))
        {
            return '';
        }

        // Setup special case prefix lookup list.
        // These are prefixes that are not capitalised.

        // Prefixes which are capitalised such as "St"
        // can be omitted.

        // We omit prefixes that are also common names,
        // such as "Della", "Di" and "Ben"

        $prefixes = array(
            "ap" => array("upper" => "Ap", "lower" => "ap"),
            "da" => array("upper" => "Da", "lower" => "da"),
            "de" => array("upper" => "De", "lower" => "de"),
            "del" => array("upper" => "Del", "lower" => "del"),
            "der" => array("upper" => "Der", "lower" => "der"),
            "du" => array("upper" => "Du", "lower" => "du"),
            "la" => array("upper" => "La", "lower" => "la"),
            "le" => array("upper" => "Le", "lower" => "le"),
            "lo" => array("upper" => "Lo", "lower" => "lo"),
            "van" => array("upper" => "Van", "lower" => "van"),
            "von" => array("upper" => "Von", "lower" => "von")
           );

        // Set up suffix lookup list

        // We preserve user's preferred punctuation: Sr./Sr

        $suffixes = array(
                        "i" => "I",
                        "ii" => "II",
                        "iii" => "III",
                        "iv" => "IV",
                        "v" => "V",
                        "vi" => "VI",
                        "vii" => "VII",
                        "viii" => "VIII",
                        "ix" => "IX",
                        "x" => "X",
                        "jr." => "Jr.",
                        "jr" => "Jr",
                        "jnr." => "Jnr.",
                        "jnr" => "Jnr",
                        "sr." => "Sr.",
                        "sr" => "Sr",
                        "snr." => "Snr.",
                        "snr" => "Snr",
                        "1st" => "1st",
                        "2nd" => "2nd",
                        "3rd" => "3rd",
                        "4th" => "4th",
                        "5th" => "5th",
                        "6th" => "6th",
                        "7th" => "7th",
                        "8th" => "8th",
                        "9th" => "9th",
                        "10th" => "10th",
                        "1st." => "1st.",
                        "2nd." => "2nd.",
                        "3rd." => "3rd.",
                        "4th." => "4th.",
                        "5th." => "5th.",
                        "6th." => "6th.",
                        "7th." => "7th.",
                        "8th." => "8th.",
                        "9th." => "9th.",
                        "10th." => "10th.",
                       );

        // Clean out extra whitespace

        $name = $this->rule_strip_extra_space(trim ($name));

        // Try to parse into forenames, main name, suffix

        $parts = explode(" ", $name);

        if(count($parts) == 1)
        {
            // Must be the main name

            $name_main = array_pop($parts);
            $name_fname = false;
            $name_suffix = false;
        }
        else
        {
            // We have more than one part to parse

            // Is the last part a suffix?
            // We assume name can have only one suffix

            $part = array_pop($parts);
            $normalised_part = strtolower($part);

            if(array_key_exists($normalised_part, $suffixes))
            {
                // Last part is a suffix

                $name_main = array_pop($parts);
                $name_suffix = $suffixes[$normalised_part];
            }
            else
            {
                // Last part is the main name

                $name_main = $part;
                $name_suffix = FALSE;
            }
        }

        // Anything left is a salutation, initial or forname

        if(count($parts) > 0)
        {
            $name_fnames = $parts;
        }
        else
        {
            $name_fnames = FALSE;
        }

        // We build the name from first to last:

        $new_name = array();

        // Set case for the forenames

        if($name_fnames)
        {
            foreach($name_fnames as $fname)
            {
                $parts = array();
                $fname = strtolower($fname);

                // Do hypenated parts separately

                $exploded_fname = explode('-', $fname);

                foreach($exploded_fname as $part)
                {
                    // If it is one of our special case prefixes
                    // we use the appropriate value
                    // Else, we capitalise

                    if(array_key_exists($part, $prefixes))
                    {
                        if($is_capitalise_prefix !== FALSE)
                        {
                            $parts[] = $prefixes[$part]["upper"];
                        }
                        else
                        {
                            $parts[] = $prefixes[$part]["lower"];
                        }
                    }
                    else
                    {
                        // It is a normal forename, salutation or initial
                        // We capitalise it.

                        $parts[] = ucfirst($part);
                    }
                }

                $new_name[] = implode('-', $parts);
            }
        }

        // Set case for the main name

        $name_main_original = $name_main;
        $name_main = strtolower($name_main);

        // Do hypenated parts separately

        $exploded_main_original = explode('-', $name_main_original);
        $exploded_main = explode('-', $name_main);

        $parts = array();

        foreach($exploded_main as $key => $part)
        {
            $part_original = $exploded_main_original[$key];

            if(substr($part, 0, 2) == "mc")
            {
                // Do "Mc"

                // Uppercase the 3rd character

                $a= substr($part, 2);
                $parts[] = "Mc" . ucfirst($a);
            }
            elseif(substr($part, 0, 3) == "mac")
            {
                // Do "Mac"

                // Lowercase the 3rd character
                // unless user has submitted
                // a correct looking name

                if(preg_match("|^Mac[A-Z][a-z]*$|", $part_original))
                {
                    $parts[] = $part_original;
                }
                else
                {
                    $parts[] = ucfirst($part);
                }
            }
            elseif(substr($part, 0, 2) == "o'")
            {
                // Do O'
                // Uppercase the 3rd character

                $a= substr($part, 2);
                $parts[] = "O'" . ucwords($a);
            }
            else
            {
                // It is a plain-jane name

                $parts[] = ucfirst($part);
            }
        }

        $new_name[] = implode('-', $parts);

        if($name_suffix)
        {
            $new_name[] =  $name_suffix;
        }

        // Assemble the new name

        $output = implode(' ', $new_name);

        return $output;
    }

    //
    // TESTING
    //

    function rule_hello($a){
        if($a!='hello'){
            return $this->fail('Type "hello" in here');
        }
        return 'Hello World';
    }
}
