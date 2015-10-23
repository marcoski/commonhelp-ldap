<?php
namespace Commonhelp\Ldap;

use Commonhelp\Util\Expression\Context\FilterContext;
use Commonhelp\Ldap\Expression\FilterExpression;
use Commonhelp\Util\Expression\Boolean\OrExpression;
use Commonhelp\Util\Expression\Boolean\AndExpression;
use Commonhelp\Util\Expression\Boolean\NotExpression;
class LdapSessionTest extends \PHPUnit_Framework_TestCase{
	
	protected $options = array(
		'host' 		=> 'ponteserver.unponteper.it',
		'username' => 'cn=admin,dc=unponteper,dc=it',
		'password' => 'Missioni2010',
		'basedn' => 'dc=unponteper,dc=it'
	);
	
	protected $baseDn = 'dc=unponteper,dc=it';
	
	protected $validDn = 'cn=admin,dc=unponteper,dc=it';
	protected $invalidDn = 'cn=admin,dc=unponteper,dc';
	
	/*public function testConnection(){
		$session = new LdapSession($this->options);
		$res = $session->getResource();
		
		//print_r($res);
	}
	
	public function testValidDn(){
		Dn::factory($this->validDn);
	}*/
	
	/*public function testRead(){
		// (&(|(objectClass=sambaAccount)(objectClass=sambaSamAccount))(objectClass=posixAccount)(!(uid=*$)))
		// (&(|(A)(B))(C)(!(B))) -> (A | B) & (C) & (B) -> (A | B9) & ((C) & (!B))
		 
		$session = new LdapSession($this->options);
		$reader = $session->getReader();
		$filter = new Filter();
		$expression = new AndExpression(
			new OrExpression(
				new FilterExpression('objectClass=sambaAccount'),
				new FilterExpression('objectClass=sambaSamAccount')
			),
			new AndExpression(
				new FilterExpression('objectClass=posixAccount'),
				new NotExpression(
					new FilterExpression('uid=*$')
				)
			)
		);
		
		
		$filterStr = $filter->visit($expression);
		$rs = $reader->search($session->getBaseDn(), '(&(|(objectClass=sambaAccount)(objectClass=sambaSamAccount))(objectClass=posixAccount)(!(uid=*$)))');
		
	}*/
	
	public function testFilter(){
		$expected = "(&(|(objectClass=inetOrgPerson)(objectClass=user))(userCertificate=*))";
		$manager = new AstFilterManager();
		
		$manager->filter($manager['userCertificate']->eq('*')->otherwise($manager['objectClass']->eq('inetOrgPerson'))->also($manager['objectClass']->eq('user')));
		$this->assertEquals('(&(|( userCertificate  =  * )( objectClass  =  inetOrgPerson ))( objectClass  =  user ))', $manager->toFilter());
	}
	
}