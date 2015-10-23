<?php

namespace Commonhelp\Ldap;

use Commonhelp\Resource\AbstractResource;
use Commonhelp\Resource\Session;
use Commonhelp\Ldap\Exception\LdapException;

class LdapSession extends AbstractResource implements Session{
	
	protected $useSsl;
	protected $useStartTls;
	protected $useUri;
	protected $port;
	protected $networkTimeout;
	protected $connectString;
	protected $host;
	
	protected $baseDn;
	
	
	public function __construct($options = array()){
		if(!extension_loaded('ldap')){
			throw new LdapException('LDAP extension not loaded');
		}
		$this->setOptions($options);
		$username = isset($options['username']) ? $options['username'] : null;
		$password = isset($options['password']) ? $options['password'] : null;
		$this->auth = new Bind($username, $password);
	}
	
	protected function createResource(){
		$resource = ($this->useUri) ? $this->connect(array($this->connectString)) : $this->connect(array($this->host, $this->port));
		if (!is_resource($resource)) {
			throw new LdapException('Unable to connect on: '.$this->connectString);
		}
		$this->resource = $resource;
		if (ldap_set_option($resource, LDAP_OPT_PROTOCOL_VERSION, 3)){
			if ($this->networkTimeout) {
				ldap_set_option($resource, LDAP_OPT_NETWORK_TIMEOUT, $this->networkTimeout);
			}
		}
		if (null !== $this->auth) {
			$this->auth();
		}
	}
	
	public function connect(array $args){
		return call_user_func_array('ldap_connect', $args);
	}
	
	public function __destruct(){
		$this->disconnect();
	}
	
	public function disconnect(){
		if(is_resource($this->resource)){
			ldap_unbind($this->resource);
		}
		
		$this->resource = null;
		
		return $this;
	}
	
	public function getReader(array $attributes=array(), $attrsOnly=0, $limit=0){
		return new LdapReader($this, $attributes, $attrsOnly, $limit);
	}
	
	public function getWriter(){
		return new LdapWriter($this);
	}
	
	public function getBaseDn(){
		return $this->baseDn;
	}
	
	public function getLastErrorCode(){
		$ret = @ldap_get_option($this->resource, LDAP_OPT_ERROR_NUMBER, $err);
		if ($ret === true) {
			if ($err <= -1 && $err >= -17) {
				$err = LdapException::LDAP_SERVER_DOWN + (-$err - 1);
			}
			return $err;
		}
		return 0;
	}
	
	public function getLastError(&$errorCode = null, array &$errorMessages = null){
		$errorCode     = $this->getLastErrorCode();
		$errorMessages = array();
		/* The various error retrieval functions can return
		 * different things so we just try to collect what we
		 * can and eliminate dupes.
		*/
		$estr1 = @ldap_error($this->resource);
		if ($errorCode !== 0 && $estr1 === 'Success') {
			$estr1 = @ldap_err2str($errorCode);
		}
		if (!empty($estr1)) {
			$errorMessages[] = $estr1;
		}
		@ldap_get_option($this->resource, LDAP_OPT_ERROR_STRING, $estr2);
		if (!empty($estr2) && !in_array($estr2, $errorMessages)) {
			$errorMessages[] = $estr2;
		}
		$message = '';
		if ($errorCode > 0) {
			$message = '0x' . dechex($errorCode) . ' ';
		}
		if (count($errorMessages) > 0) {
			$message .= '(' . implode('; ', $errorMessages) . ')';
		} else {
			$message .= '(no error message from LDAP)';
		}
		return $message;
	}
	
	protected function auth(){
		if(false === parent::auth()){
			throw new LdapException(ldap_error($this->resource));
		}
	}
	
	protected function setOptions(array $options){
		$this->host = isset($options['host']) ? $options['host'] : false;
		$this->useSsl = isset($options['usessl']) ? $options['usessl'] : false;
		$this->useStartTls = isset($options['usestarttls']) ? $options['usestarttls'] : false;
		$this->port = isset($options['port']) ? $options['port'] : '389';
		$this->networkTimeout = isset($options['networktimeout']) ? $options['networktimeout'] : null;
		$this->connectString = $this->setHost($this->host);
		$this->baseDn = isset($options['basedn']) ? $options['basedn'] : null;
	}
	
	protected function setHost($host){
		$this->useUri = false;
		$h = '';
		if (preg_match_all('~ldap(?:i|s)?://~', $host, $hosts, PREG_SET_ORDER) > 0) {
			$h = $host;
			$this->useUri = true;
			$this->useSsl = true;
		}else{
			if($this->useSsl){
				$h = 'ldaps://' . $host;
				$this->useUri = true;
			}else{
				$h = 'ldap://' . $host;
			}
			if($this->port){
				$h .= ':' . $this->port;
			}
		}
		
		return $h;
	}
	
	
	
}

?>