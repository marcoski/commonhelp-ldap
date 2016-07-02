<?php

namespace Commonhelp\Ldap\Utils;

use Commonhelp\Ldap\Exception\LdapException;

class Dn implements \ArrayAccess{
	
	protected $dn;
	
	public static function factory($dn){
		if(is_array($dn)){
			return static::fromArray($dn);
		}else if(is_string($dn)){
			return static::fromString($dn);
		}
		
		throw new LdapException('Invalid argument for $dn');
	}
	
	public static function fromString($dn){
		$dn = trim($dn);
		if(empty($dn)){
			$dnArray = array();
		}else{
			$dnArray = static::explodeDn((string) $dn);
		}
		
		return new static($dnArray);
	}
	
	public static function fromArray(array $dn){
		return new static($dn);
	}
	
	public function __construct(array $dn) {
		$this->dn = $dn;
	}
	
	public function getRdn(){
		return $this->get(0, 1);
	}
	
	public function getRdnString(){
		return static::impldeDn($this->getRdn());
	}
	
	public function getParentDn($levelup = 1){
		if($levelup < 1 || $levelup >= count($this->dn)){
			throw new LdapException('Cannot reterive parent DN with given $levelup');
		}
		$newDn = array_slice($this->dn, $levelup);
		return new static($newDn);
	}
	
	public function get($index, $length=1){
		$this->assertIndex($index);
		if($length <= 0){
			$length = 1;
		}
		
		if($length === 1){
			return $this->dn[$index];
		}
		
		return array_slice($this->dn, $index, $length, false);
	}
	
	public function set($index, array $value){
		$this->assertIndex($index);
		static::assertRdn($value);
		$this->dn[$index] = $value;
		return $this;
	}
	
	public function remove($index, $length = 1){
		$this->assertIndex($index);
		if($length <= 0){
			$length = 1;
		}
		array_splice($this->dn, $index, $length, null);
		return $this;
	}
	
	public function append(array $value){
		static::assertRdn($value);
		$this->dn[] = $value;
		return $this;
	}
	
	public function prepend(array $value){
		static::assertRdn($value);
		array_unshift($this->dn, $value);
		return $this;
	}
	
	public function insert($index, array $value){
		$this->assertIndex($index);
		static::assertRdn($value);
		$first = array_slice($this->dn, 0, $index+1);
		$second = array_slice($this->dn, $index+1);
		$this->dn = array_merge($first, array($value), $second);
		return $this;
	}
	
	
	public function offsetExists($offset){
		$offset = (int) $offset;
		if($offset < 0 || $offset >= count($this->dn)){
			return false;
		}
		
		return true;
	}
	
	public function offsetGet($offset){
		return $this->get($offset, 1);
	}
	
	public function offsetSet($offset, $value){
		$this->set($offset, $value);
	}
	
	public function offsetUnset($offset){
		$this->remove($offset, 1);
	}
	
	protected function assertIndex($index){
		if(!is_int($index)){
			throw new LdapException('Parameter $index must be an integer');
		}
		if($index < 0 || $index >= count($this->dn)){
			throw new LdapException('Parameter $index out of bounds');
		}
	}
	
	protected static function assertRdn(array $value){
		if(count($value) < 1){
			throw new LdapException('RDN array is malformed: it must have at least one item');
		}
		
		foreach(array_keys($value) as $key){
			if(!is_string($key)){
				throw new LdapException('RDN array is malformed: it must use string keys');
			}
		}
	}
	
	public function toString(){
		return static::impldeDn($this->dn);
	}
	
	public function toArray(){
		return $this->dn;
	}
	
	public function __toString(){
		$this->toString();
	}
	
	public static function implodeRdn(array $part){
		static::assertRdn($part);
		$rdnParts = array();
		foreach($part as $key => $value){
			$value = static::escapeValue($value);
			$keyId = strtolower($key);
			$rdnParts[$keyId] = implode('=', array($key, $value));
		}
		ksort($rdnParts, SORT_STRING);
		
		return implode('+', $rdnParts);
	}
	
	public static function impldeDn(array $dnArray, $separator=','){
		$parts = array();
		foreach($dnArray as $p){
			$parts[] = static::implodeRdn($p);
		}
		
		return implode($separator, $parts);
	}
	
	 /**
     * Escapes a DN value according to RFC 2253
     *
     * Escapes the given VALUES according to RFC 2253 so that they can be safely used in LDAP DNs.
     * The characters ",", "+", """, "\", "<", ">", ";", "#", " = " with a special meaning in RFC 2252
     * are preceded by ba backslash. Control characters with an ASCII code < 32 are represented as \hexpair.
     * Finally all leading and trailing spaces are converted to sequences of \20.
     * @see    Net_LDAP2_Util::escape_dn_value() from Benedikt Hallinger <beni@php.net>
     * @link   http://pear.php.net/package/Net_LDAP2
     * @author Benedikt Hallinger <beni@php.net>
     *
     * @param  string|array $values An array containing the DN values that should be escaped
     * @return array The array $values, but escaped
     */
	public static function escapeValue($values = array()){
		if(!is_array($values)){
			$values = array($values);
		}
		
		foreach($values as $key => $val){
			$val = str_replace(
				array('\\', ',', '+', '"', '<', '>', ';', '#', '=',),
				array('\\\\', '\,', '\+', '\"', '\<', '\>', '\;', '\#', '\='), $val
            );
			$val = Converter::ascToHex32($val);
			if (preg_match('/^(\s*)(.+?)(\s*)$/', $val, $matches)){
				$val = $matches[2];
				for($i=0, $len = strlen($matches[1]); $i < $len; $i++){
					$val = '\20' . $val;
				}
				for($i=0, $len = strlen($matches[3]); $i < $len; $i++){
					$val = $val . '\20';
				}
			}
			if(null === $val){
				$val = '\0';
			}
			$values[$key] = $val;
		}
		
		return (count($values) == 1) ? $values[0] : $values;
	}
	
	/**
     * Undoes the conversion done by {@link escapeValue()}.
     *
     * Any escape sequence starting with a baskslash - hexpair or special character -
     * will be transformed back to the corresponding character.
     * @see    Net_LDAP2_Util::escape_dn_value() from Benedikt Hallinger <beni@php.net>
     * @link   http://pear.php.net/package/Net_LDAP2
     * @author Benedikt Hallinger <beni@php.net>
     *
     * @param  string|array $values Array of DN Values
     * @return array Same as $values, but unescaped
     */
	public static function unescapeValue($values = array()){
		if(!is_array($values)){
			$values = array($values);
		}
		
		foreach($values as $key => $val){
			$val = str_replace(
                array('\\\\', '\,', '\+', '\"', '\<', '\>', '\;', '\#', '\='),
                array('\\', ',', '+', '"', '<', '>', ';', '#', '=',), $val
            );
            $values[$key] = Converter::hex32ToAsc($val);
		}
		
		return (count($values) == 1) ? $values[0] : $values;
	}
	
	/**
     * Creates an array containing all parts of the given DN.
     *
     * Array will be of type
     * array(
     *      array("cn" => "name1", "uid" => "user"),
     *      array("cn" => "name2"),
     *      array("dc" => "example"),
     *      array("dc" => "org")
     * )
     * for a DN of cn=name1+uid=user,cn=name2,dc=example,dc=org.
     *
     * @param  string $dn
     * @param  array  $keys     An optional array to receive DN keys (e.g. CN, OU, DC, ...)
     * @param  array  $vals     An optional array to receive DN values
     * @param  string $caseFold
     * @return array
     * @throws Exception\LdapException
     */
    public static function explodeDn($dn, array &$keys = null, array &$vals = null){
        $k = array();
        $v = array();
        if (!self::checkDn($dn, $k, $v)) {
            throw new LdapException('DN is malformed');
        }
        $ret = array();
        for ($i = 0, $count = count($k); $i < $count; $i++) {
            if (is_array($k[$i]) && is_array($v[$i]) && (count($k[$i]) === count($v[$i]))) {
                $multi = array();
                for ($j = 0; $j < count($k[$i]); $j++) {
                    $key         = $k[$i][$j];
                    $val         = $v[$i][$j];
                    $multi[$key] = $val;
                }
                $ret[] = $multi;
            } elseif (is_string($k[$i]) && is_string($v[$i])) {
                $ret[] = array($k[$i] => $v[$i]);
            }
        }
        if ($keys !== null) {
            $keys = $k;
        }
        if ($vals !== null) {
            $vals = $v;
        }
        return $ret;
    }
	
	/**
     * @param  string $dn       The DN to parse
     * @param  array  $keys     An optional array to receive DN keys (e.g. CN, OU, DC, ...)
     * @param  array  $vals     An optional array to receive DN values
     * @param  string $caseFold
     * @return bool True if the DN was successfully parsed or false if the string is not a valid DN.
     */
    public static function checkDn($dn, array &$keys = null, array &$vals = null) {
        /* This is a classic state machine parser. Each iteration of the
         * loop processes one character. State 1 collects the key. When equals ( = )
         * is encountered the state changes to 2 where the value is collected
         * until a comma (,) or semicolon (;) is encountered after which we switch back
         * to state 1. If a backslash (\) is encountered, state 3 is used to collect the
         * following character without engaging the logic of other states.
         */
        $key   = null;
        $value = null;
        $slen  = strlen($dn);
        $state = 1;
        $ko    = $vo = 0;
        $multi = false;
        $ka    = array();
        $va    = array();
        for ($di = 0; $di <= $slen; $di++) {
            $ch = ($di == $slen) ? 0 : $dn[$di];
            switch ($state) {
                case 1: // collect key
                    if ($ch === '=') {
                        $key = trim(substr($dn, $ko, $di - $ko));
                        if (is_array($multi)) {
                            $keyId = strtolower($key);
                            if (in_array($keyId, $multi)) {
                                return false;
                            }
                            $ka[count($ka) - 1][] = $key;
                            $multi[]              = $keyId;
                        } else {
                            $ka[] = $key;
                        }
                        $state = 2;
                        $vo    = $di + 1;
                    } elseif ($ch === ',' || $ch === ';' || $ch === '+') {
                        return false;
                    }
                    break;
                case 2: // collect value
                    if ($ch === '\\') {
                        $state = 3;
                    } elseif ($ch === ',' || $ch === ';' || $ch === 0 || $ch === '+') {
                        $value = static::unescapeValue(trim(substr($dn, $vo, $di - $vo)));
                        if (is_array($multi)) {
                            $va[count($va) - 1][] = $value;
                        } else {
                            $va[] = $value;
                        }
                        $state = 1;
                        $ko    = $di + 1;
                        if ($ch === '+' && $multi === false) {
                            $lastKey = array_pop($ka);
                            $lastVal = array_pop($va);
                            $ka[]    = array($lastKey);
                            $va[]    = array($lastVal);
                            $multi   = array(strtolower($lastKey));
                        } elseif ($ch === ',' || $ch === ';' || $ch === 0) {
                            $multi = false;
                        }
                    } elseif ($ch === '=') {
                        return false;
                    }
                    break;
                case 3: // escaped
                    $state = 2;
                    break;
            }
        }
        if ($keys !== null) {
            $keys = $ka;
        }
        if ($vals !== null) {
            $vals = $va;
        }
        return ($state === 1 && $ko > 0);
    }
	
	public static function isChildOf($childDn, $parentDn){
		try{
			$keys = array();
			$vals = array();
			if($childDn instanceof Dn){
				$cdn = $childDn->toArray();
			}else{
				$cdn = static::explodeDn($childDn, $keys, $vals);
			}
			if($parentDn instanceof Dn){
				$pdn = $parentDn->toArray();
			}else{
				$pdn = static::explodeDn($parentDn, $keys, $vals);
			}
		} catch (\RuntimeException $ex) {
			return false;
		}
		
		$startIndex = count($cdn) - count($pdn);
		if($startIndex < 0){
			return false;
		}
		
		for($i=0, $count = count($pdn); $i < $count; $i++){
			if($cdn[$i + $startIndex] != $pdn[$i]){
				return false;
			}
		}
		
		return true;
	}
	
	
}

