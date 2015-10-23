<?php
namespace Commonhelp\Ldap;

use Commonhelp\Ldap\Exception\LdapException;
class ResultSet implements \Iterator, \Countable{
	
	protected $reader;
	protected $resultId;
	
	protected $itemCount = -1;
	protected $current;
	
	public function __construct(LdapReader $reader, $resId){
		$this->reader = $reader;
		$this->resultId = $resId;
		
		$res = $reader->getResource();
		$this->itemCount = @ldap_count_entries($res, $resId);
		if(false === $this->itemCount){
			throw new LdapException('Error while counting entries');
		}
	}
	
	public function __destruct(){
		$this->close();
	}
	
	public function close(){
		$isClosed = false;
		if(is_resource($this->resultId)){
			$isClosed = @ldap_free_result($this->resultId);
			$this->resultId = null;
			$this->current = null;
		}
		
		return $isClosed;
	}
	
	public function getReader(){
		return $this->reader;
	}
	
	public function current(){
		if (!is_resource($this->current)) {
			$this->rewind();
		}
		if (!is_resource($this->current)) {
			return;
		}
		$entry = array('dn' => $this->key());
		$berIdentifier = null;
		$resource = $this->reader->getResource();
		$name = @ldap_first_attribute(
				$resource, $this->current,
				$berIdentifier
		);
		while ($name) {
			$data = @ldap_get_values_len($resource, $this->current, $name);
			if (!$data) {
				$data = array();
			}
			if (isset($data['count'])) {
				unset($data['count']);
			}
			
			$entry[$name] = $data;
			$name = ldap_next_attribute(
					$resource, $this->current,
					$berIdentifier
			);
		}
		ksort($entry, SORT_LOCALE_STRING);
		return $entry;
	}
	
	public function key(){
		if (!is_resource($this->current)) {
			$this->rewind();
		}
		if (is_resource($this->current)) {
			$resource = $this->reader->getResource();
			$currentDn = @ldap_get_dn($resource, $this->current);
			if ($currentDn === false) {
				throw new LdapException('getting dn');
			}
			return $currentDn;
		} else {
			return;
		}
	}
	
	public function next(){
		$code = 0;
		if (is_resource($this->current) && $this->itemCount > 0) {
			$resource = $this->reader->getResource();
			$this->current = @ldap_next_entry($resource, $this->current);
			if ($this->current === false) {
				//$msg = $this->reader->getLastError($code);
				if ($code === LdapException::LDAP_SIZELIMIT_EXCEEDED) {
					return;
				} elseif ($code > LdapException::LDAP_SUCCESS) {
					throw new LdapException('getting next entry');
				}
				
			}
		} else {
			$this->current = false;
		}
	}
	
	public function rewind(){
		if (is_resource($this->resultId)) {
			$resource = $this->reader->getResource();
			$this->current = @ldap_first_entry($resource, $this->resultId);
			if ($this->current === false) {
				throw new LdapException('getting first entry');
			}
		}
	}
	
	public function valid(){
		return (is_resource($this->current));
	}
	
	public function count(){
		return $this->itemCount;
	}
	
}