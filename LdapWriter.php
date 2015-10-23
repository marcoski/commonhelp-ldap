<?php
namespace Commonhelp\Ldap;

use Commonhelp\Resource\SubSystem;

class LdapWriter extends SubSystem{
	
	protected function createResource(){
		$this->resource = $this->getSessionResource();
	}
	
	public function add($dn, $data){
		return @ldap_add($this->resource, $dn, $data);
	}
	
	public function update($dn, $data){
		return @ldap_modify($this->resource, $dn, $data);
	}
	
	public function delete($dn){
		return @ldap_delete($this->resource, $dn);
	}
	
}