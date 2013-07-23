<?php
/**
 * Verbal Expressions v0.1 (https://github.com/jehna/VerbalExpressions) ported in PHP
 * @author Mihai Ionut Vilcu (ionutvmi@gmail.com)
 * 22.July.2013
 */


// // some tests

// $regex = new VerEx;

// $regex 	->startOfLine()
// 		->then( "http" )
// 		->maybe( "s" )
// 		->then( "://" )
// 		->maybe( "www." )
// 		->anythingBut( " " )
// 		->endOfLine();


// if($regex->test("http://github.com"))
// 	echo "valid url";
// else
// 	echo "invalid url";

// if (preg_match($regex, 'http://github.com')) {
// 	echo 'valid url';
// } else {
// 	echo 'invalud url';
// }

// echo "<pre>". $regex->getRegex() ."</pre>";


// echo $regex ->clean(array("_modifiers"=> "m","_replaceLimit"=>4))
// 			->find(' ')
// 			->replace("This is a small test http://somesite.com and some more text.", "-");


class VerEx {

	private $_prefixes = "";
	private $_source = "";
	private $_suffixes = "";
	private $_modifiers = "m"; // default to global multiline matching
	private $_replaceLimit = 1; // the limit of preg_replace when g modifier is not set


	/**
	 * Shorthand for preg_replace()
	 * @param  string $_source the string that will be affected(subject)
	 * @param  string $value  the replacement
	 */
	public function replace($_source, $value) {
		
		if(!($value instanceof Closure)){		
			// php doesn't have g modifier so we remove it if it's there and we remove limit param
			if(strpos($this->_modifiers, 'g') !== false){
				$this->_modifiers = str_replace('g', '', $this->_modifiers);
				return preg_replace($this->getRegex(), $value, $_source);
			}		
			return preg_replace($this->getRegex(), $value, $_source, $this->_replaceLimit);
		}else{
			// php doesn't have g modifier so we remove it if it's there and we remove limit param
			if(strpos($this->_modifiers, 'g') !== false){
				$this->_modifiers = str_replace('g', '', $this->_modifiers);
				return preg_replace_callback($this->getRegex(), function($matches) use ($value) {
					return call_user_func_array($value, $matches);
				}, $_source);
			}
			return preg_replace_callback($this->getRegex(), function($matches) use ($value) {
                return call_user_func_array($value, $matches);
			}, $_source, $this->_replaceLimit);
		}
	}
	/**
	 * Shorthand for preg_grep()
	 * @param  string $_source the array that will be affected(subject)
	 */
	public function grep( $_source ){
		// php doesn't have g modifier so we remove it if it's there and we remove limit param
		if(strpos($this->_modifiers, 'g') !== false){
			$this->_modifiers = str_replace('g', '', $this->_modifiers);
		}	
		return preg_grep($this->getRegex(), $_source);	
	}
	/**
	 * Shorthand for preg_split()
	 * @param  string $_source the string that will be affected(subject)
	 */
	public function split( $_source ){
		// php doesn't have g modifier so we remove it if it's there and we remove limit param
		if(strpos($this->_modifiers, 'g') !== false){
			$this->_modifiers = str_replace('g', '', $this->_modifiers);
			return preg_split($this->getRegex(), $_source);	
		}		
		return preg_split($this->getRegex(), $_source, $this->_replaceLimit);	
	}	

	/**
	 * Sanitation public function for adding anything safely to the expression
	 * @param  string $value the to be added
	 * @return string        escaped value
	 */
	protected function sanitize( $value ) {
		if(!$value) 
			return $value;
		return preg_quote($value, "/");
	}
	/**
	 * Add stuff to the expression 
	 * @param string $value the stuff to be added
	 */
	public function add( $value ) {
		$this->_source .= $value;
		return $this;
	}
	/**
	 * Mark the expression to start at the beginning of the line.
	 * @param  boolean $enable Enables or disables the line starting. Default value: true
	 */
	public function startOfLine( $enable = true ) {
		$this->_prefixes = $enable ? "^" : "";
		return $this;
	}
	/**
	 * Mark the expression to end at the last character of the line.
	 * @param  boolean $enable Enables or disables the line ending. Default value: true
	 */
	public function endOfLine( $enable = true ) {
		$this->_suffixes = $enable ? "$" : "";
		return $this;
	}
	/**
	 * Add a string to the expression
	 * @param  string $value The string to be looked for
	 */
	public function then( $value ) {
		$this->add("(".$this->sanitize($value).")");
		return $this;
	}

	/**
	 * alias for then()
	 * @param  string $value The string to be looked for
	 */
	public function find( $value ) {
		return $this->then($value);
	}
	/**
	 *  Add a string to the expression that might appear once (or not).
	 * @param  string $value The string to be looked for
	 */
	public function maybe( $value ) {
		$this->add("(".$this->sanitize($value).")?");
		return $this;
	}
	/**
	 * Accept any string 
	 */
	public function anything() {
		$this->add("(.*)");
		return $this;
	}
	/**
	 * Anything but this chars
	 * @param  string $value The unaccepted chars
	 */
	public function anythingBut( $value ) {
		$this->add("([^". $this->sanitize($value) ."]*)");
		return $this;
	}	
	/**
	 * Match line break
	 */
	public function lineBreak() {
		$this->add("(\\n|(\\r\\n))");
		return $this;
	}
	/**
	 * Shorthand for lineBreak
	 */
	public function br() {
		return $this->lineBreak();
	}
	/**
	 * Match tabs.
	 */
	public function tab() {
		$this->add("\\t");
		return $this;
	}
	/**
	 * Match any alfanumeric
	 */
	public function word() {
		$this->add("\\w+");
		return $this;
	}
	/**
	 * Any of the listed chars
	 * @param  string $value The chars looked for
	 */
	public function anyOf( $value ) {
		$this->add("["+ value +"]");
		return $this;
	}
	/**
	 * Shorthand for anyOf

	 * @param  string $value The chars looked for
	 */
	public function any( $value ) {
		return $this->anyOf($value);
	}
	/**
	 * Adds a range to our expresion ex: range(a,z) => a-z, range(a,z,0,9) => a-z0-9
	 */
	public function range() {

		$arg_num = func_num_args();
		if($arg_num%2 != 0)
			throw new Exception("Number of args must be even", 1);
		$value = "[";
		$arg_list = func_get_args();
		for($i = 0; $i < $arg_num;)
			$value .= $this->sanitize($arg_list[$i++]) . " - " . $this->sanitize($arg_list[$i++]);
		$value .= "]";

		$this->add($value);

		return $this;
	}
	/**
	 * Adds a modifier
	 */
	public function addModifier( $modifier ) {
		if(strpos($this->_modifiers, $modifier) === false)
			$this->_modifiers .= $modifier;

		return $this;
	}
	/**
	 * Removes a modifier
	 */
	public function removeModifier( $modifier ) {

		$this->_modifiers = str_replace($modifier, '', $modifier);

		return $this;
	}
	/**
	 * Match case insensitive or sensitive based on $enable value
	 * @param  boolean $enable Enables or disables case sensitive. Default true
	 */
	public function withAnyCase( $enable = true ) {
		if($enable)
			$this->addModifier('i');
		else
			$this->removeModifier('i');

		return $this;
	}
	/**
	 * Toggles g modifier
	 * @param  boolean $enable Enables or disables g modifier. Default true
	 */
	public function stopAtFirst( $enable = true ) {
		if($enable)
			$this->addModifier('g');
		else
			$this->removeModifier('g');
		return $this;
	}
	/**
	 * Toggles m modifier
	 * @param  boolean $enable Enables or disables m modifier. Default true
	 */
	public function searchOneLine( $enable = true ) {
		if($enable)
			$this->addModifier('m');
		else
			$this->removeModifier('m');

		return $this;
	}
	/**
	 * Adds the multiple modifier at the end of your expresion
	 * @param  string $value Your expresion
	 */
	public function multiple( $value ) {
		
		$value = $this->sanitize($value);

		switch (substr($value, -1)) {
			case '+':
			case '*':
				break;
			
			default:
				$value += '+';
				break;
		}

		$this->add($value);

		return $this;
	}

	/**
	 * Wraps the current expresion in an `or` with $value
	 * @param  string $value new expression
	 */
	public function _or( $value ) {
		if(strpos($this->_prefixes, "(") === false)
			$this->_prefixes .= "(";
		if(strpos($this->_suffixes, ")") === false)
			$this->_suffixes .= ")";

		$this->add(")|(");
		if($value)
			$this->add($value);

		return $this;

	}

	/**
	 * PHP Magic method to return a string representation of the object.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->getRegex();
	}

	/**
	 * Creates the final regex.
	 * @return string The final regex
	 */
	public function getRegex() {
		return "/".$this->_prefixes . $this->_source . $this->_suffixes. "/" . $this->_modifiers;
	}

	/**
	 * tests the match of a string to the current regex
	 * @param  string $value The string to be tested
	 * @return boolean        true if it's a match
	 */
	public function test($value) {
		// php doesn't have g modifier so we remove it if it's there and call preg_match_all()
		if(strpos($this->_modifiers, 'g') !== false){
			$this->_modifiers = str_replace('g', '', $this->_modifiers);
			return preg_match_all($this->getRegex(), $value);
		}
		return preg_match($this->getRegex(), $value);
	}

	/**
	 * deletes the current regex for a fresh start
	 */
	public function clean($options = array()) {
		$options = array_merge(array("_prefixes"=> "", "_source"=>"", "_suffixes"=>"", "_modifiers"=>"gm","_replaceLimit"=>"1"), $options);
		$this->_prefixes = $options['_prefixes'];
		$this->_source = $options['_source'];
		$this->_suffixes = $options['_suffixes'];
		$this->_modifiers = $options['_modifiers']; // default to global multiline matching
		$this->_replaceLimit = $options['_replaceLimit']; // default to global multiline matching

		return $this;
	}

}


// $regex = new VerEx;

// $regex 	->startOfLine()
// 		->then( "http" )
// 		->maybe( "s" )
// 		->then( "://" )
// 		->maybe( "www." )
// 		->anythingBut( " " )
// 		->endOfLine();


// if($regex->test("http://github.com"))
// 	echo "valid url";
// else
// 	echo "invalid url";

// if (preg_match($regex, 'http://github.com')) {
// 	echo 'valid url';
// } else {
// 	echo 'invalud url';
// }

// echo "<pre>". $regex->getRegex() ."</pre>";


//echo $regex ->clean(array("_modifiers"=> "m","_replaceLimit"=>4))
//			->find(" ")
//			->replace("This is a small test http://somesite.com and some more text.", function($matches){
//				return "-";
//			});
			
//echo $regex ->clean(array("_modifiers"=> "m","_replaceLimit"=>4))
// 			->find(' ');
//echo "<pre>". print_r($regex->split("This is a small test http://somesite.com and some more text."), true) ."</pre>";

//$regex->clean()->startOfLine()
//			->then( "http" )
//			->maybe( "s" )
//			->then( "://" )
//			->maybe( "www." )
//			->anythingBut( " " )
//			->endOfLine();
			
//$grep = $regex->grep(array(	'This',
//							'is',
//							'a',
//							'small',
//							'http://somesite.com',
//							'and',
//							'some',
//							'more',
//							'text.')
//					);
//echo "<pre>". print_r($grep, true) ."</pre>";
