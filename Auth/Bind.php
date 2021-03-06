<?php

namespace Commonhelp\Ldap\Auth;

use Commonhelp\Resource\Auth;
use Commonhelp\Ldap\Exception\LdapException;
use Commonhelp\Ldap\Utils\Dn;

class Bind implements Auth{
	
	private $username;
	private $password;
	
	public function __construct($username, $password){
		$userDn = Dn::factory($username);
		$this->username = $userDn->toString();
		$this->password = $password;
	}
	
	public function authenticate($session){
		if ((null === $this->username) || (null === $this->password)) {
			if ((null !== $this->username) || (null !== $this->password)) {
				throw new LdapException('For an anonymous binding, both rdn & passwords have to be null');
			} 
		}
		return @ldap_bind($session, $this->username, $this->password);
	}
	
}

