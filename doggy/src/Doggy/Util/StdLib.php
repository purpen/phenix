<?php
/**
 * Doggy general library functions
 *
 */
abstract class Doggy_Util_StdLib{

    /**
	 * Checks if a value is an error
	 *
	 * @param	mixed	Value to check
	 */
	static public function isError($error) {
		return ($error instanceof Exception);
	}

	/**
	 * Loads an extension
	 * @see		dl()
	 * @param	string	Name of extension excluding fileending {.so, .dll}
	 * @return	bool	Return true on success, false on failure
	 */
	static public function dl($extension) {
		$file = (PHP_SHLIB_SUFFIX=='dll'?'php_':'').$extension.'.'.PHP_SHLIB_SUFFIX;
		return file_exists(ini_get('extension_dir').'/'.$file) && @dl($file);
	}

	/**
	 * Generic hash function
	 * @param	mixed, ...
	 * @return	string
	 */
	static public function hash() {
		return md5(serialize(func_get_args()));
	}
	/**
	 * Recursive trim()
	 * @see		trim()
	 * @param	mixed	Value to trim
	 * @param	string	Characters to trim
	 * @return	mixed
	 */
	static public function trim($str, $chars = " \r\n\t") {
		if (is_array($str)) {
			foreach (array_keys($str) as $key)
				$str[$key] = self::trim($str[$key], $chars);
		}
		else if (is_string($str))
			return trim($str, $chars);
		return $str;
	}
    /**
     * sprintf support i18n
     *
     * @todo
     * @param mixed $string ...
     * @return string
     */
    static function text($string) {
		if (func_num_args() < 2)
			return self::_t($string);
		$args = func_get_args();
		$result = self::_t($result);
		$args[0] = $result;
		return call_user_func_array('sprintf', $args);
	}
	/**
	 * return i18n translated message
	 *
	 * @param string $string
	 * @param string $domain
	 * @return string
	 * @todo
	 */
	static function _t($string,$domain=null){
	    return $string;
	}

	/**
	 * Executes an external program and return the output
	 *
	 * @param	string	Command to execute using proc_open()
	 * @param	string	Buffer to write on STDIN
	 * @param	bool	Trigger failure on binary output
	 * @param	integer	Max output to read (null for unlimited)
	 * @return	string
	 */
	static function execute($cmd, $input = null, $skipBinary = false, $maxLength = null) {
		$spec = array(
			0 => array('pipe','r'),
			1 => array('pipe','w'),
			2 => array('pipe','w'));

		$pd = proc_open($cmd, $spec, $pipes);
		if (!is_resource($pd)) {
			trigger_error("Could not open process '$cmd'", E_USER_WARNING);
			return null;
		}

		fclose($pipes[2]);
		$stdout = array($pipes[1]);
		$output = null;

		if (null != $input)
			$stdin = array($pipes[0]);
		else {
			fclose($pipes[0]);
			$stdin = null;
		}

		$length = strlen($input);
		$oob = null;

		for ($i=0, $timeout = time()+10; !feof($pipes[1]);) {
			$read = $stdout;
			$write = $stdin;

			stream_select($read, $write, $oob, 1);

			// Write a chunk of data
			if (!empty($write) && $i < $length) {
				if (false === ($i += fwrite($pipes[0], substr($input, $i, 8192)))) {
					trigger_error('Could not write to STDIN.', E_USER_WARNING);
					$output = null;
					break;
				}
				else if ($i >= $length) {
					fclose($pipes[0]);
					$stdin = null;
				}
			}

			// Read any output
			if (!empty($read)) {
				while (!feof($pipes[1]) && null != ($output .= fread($pipes[1], 8192))) {
					if ($skipBinary && false !== strpos($output, "\0")) {
						trigger_error('Binary file detected, skipping.', E_USER_WARNING);
						@fclose($pipes[0]);
						$output = null;
						break 2;
					}
					if (null !== $maxLength && strlen($output) >= $maxLength) {
						@fclose($pipes[0]);
						break 2;
					}
				}
			}

			// Break after 10 seconds without IO
			if (!empty($read) || !empty($write))
				$timeout = time()+10;
			else if ($timeout < time()) {
				@fclose($pipes[0]);
				break;
			}
		}

		fclose($pipes[1]);
		proc_close($pd);

		return $output;
	}

	/**
	 * Invokes a method on each object in a list.
	 *
	 * Returns a list with the return values of each call indexed
	 * with the same key as its node. Does not make references!
	 *
	 * @see		MethodIterator
	 * @param	array	List of objects
	 * @param	string	Method to invoke
	 * @param	mixed	$a,...	Arguments to the member-function
	 * @return	array
	 */
	static function invoke($objects, $method) {
		$args = array_slice(func_get_args(), 2);
		$results = array();
		foreach ($objects as $key => $object)
			$results[$key] = call_user_func_array(array($object, $method), $args);
		return $results;
	}
	/**
	 * Filters a list of objects on the result of a method call.
	 *
	 * Applies the function to each of the nodes with the supplied
	 * arguments and returns a list with those that returned true.
	 *
	 * Example:
	 *  $filteredList = SyndLib::filter($list, 'isPermitted', 'read');
	 *
	 * @param	array	List of objects
	 * @param	string	Method to invoke
	 * @param	mixed	$a,...	Arguments to the member-function
	 * @return	array
	 */
	static function filter($objects, $method) {
		$result = array();
		$args = array_slice(func_get_args(), 2);

		foreach ($objects as $key => $object) {
			if (call_user_func_array(array($object, $method), $args))
				$result[$key] = $object;
		}

		return $result;
	}
	/**
	 * Collects a datamember from each object in a list
	 *
	 * Returns a list with the member indexed with the same key as
	 * its node. Does not make references!
	 *
	 * @param	array	List of objects
	 * @param	string	Member to collect
	 * @return	array
	 */
	static function collect($objects, $member) {
		$result = array();
		foreach ($objects as $key => $object)
			$result[$key] = $object->$member;
		return $result;
	}
	/**
	 * Returns the key of the minimum value
	 * @see		MethodObjectIterator
	 * @param	array	Values or iterator to traverse
	 * @return	mixed
	 */
	static function min(Iterator $values) {
		$key = null;
		$opt = null;

		for ($values->rewind(); $values->valid(); $values->next()) {
			if ($values->current() < $opt || null === $key) {
				$key = $values->key();
				$opt = $values->current();
			}
		}

		return $key;
	}

	/**
	 * Returns the key of the maximum value
	 * @see		MethodObjectIterator
	 * @param	array	Values or iterator to traverse
	 * @return	mixed
	 */
	static function max(Iterator $values) {
		$key = null;
		$opt = null;

		for ($values->rewind(); $values->valid(); $values->next()) {
			if ($values->current() > $opt || null === $key) {
				$key = $values->key();
				$opt = $values->current();
			}
		}

		return $key;
	}

	/**
	 * Sums a number of values
	 * @see		MethodIterator
	 * @param	array	Values or iterator to traverse
	 * @return	mixed
	 */
	static function sum($values) {
		$sum = null;
		foreach ($values as $value)
			$sum += $value;
		return $sum;
	}
	/**
	 * Alternative array_merge_recursive that overwrites existing keys instead of
	 * creating a subarray and appending an additional value.
	 * @param	array	$a,...	Arrays to merge
	 * @return	array
	 */
	static function array_merge_recursive() {
		$args = func_get_args();
		$soft = is_bool(end($args)) ? array_pop($args) : true;
		$result = array_shift($args);

		while (null != ($arg = array_shift($args))) {
			foreach (array_keys($arg) as $key) {
				if (isset($result[$key]) && isset($arg[$key]) && is_array($result[$key]) && is_array($arg[$key]))
					$result[$key] = self::array_merge_recursive($result[$key], $arg[$key], $soft);
				else if (is_int($key) && isset($result[$key]) && $soft)
					$result[] = $arg[$key];
				else
					$result[$key] = $arg[$key];
			}
		}

		return $result;
	}
	/**
	 * Merges the specified arrays by reference
	 * @param	array	$a,...	Arrays to merge
	 * @return	array
	 */
	static function array_merge_assoc() {
		$args = func_get_args();
		$result = array();
		for ($i=0; $i<count($args); $i++) {
			if (is_array($args[$i])) {
				foreach (array_keys($args[$i]) as $key) {
					if (is_numeric($key))
						$result[] = $args[$i][$key];
					else
						$result[$key] = $args[$i][$key];
				}
			}
		}
		return $result;
	}
	/**
	 * Collects a value from a list of subarrays
	 * @param	array	Array of arrays
	 * @param	mixed	Index of value to collect
	 * @return	array
	 */
	static function array_collect($list, $index) {
		$result = array();
		foreach (array_keys($list) as $key)
			$result[$key] = $list[$key][$index];
		return $result;
	}
}
?>