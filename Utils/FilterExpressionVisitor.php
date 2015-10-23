<?php

namespace Commonhelp\Ldap\Utils;

use Commonhelp\Util\Expression\Expression;
use Commonhelp\Util\Expression\InOrderVisitor;
use Commonhelp\Ldap\Filters\FilterExpression;


class FilterExpressionVisitor extends InOrderVisitor{
	
	const EQUAL = 0;
	const APPROX = 1;
	const LESSTHANEQUAL = 2;
	const GREATERTHANEQUAL = 3;
	
	protected $symbolMap = array('=', '~=', '<=', '>=');
	
	
	public function visit(Expression $e){
		parent::visit($e);
		
		return $this->toString();
	}
	
	public function process(Expression $e){
		if($e instanceof FilterExpression){
			if(!in_array($e->getValue(), $this->symbolMap)){
				throw new LdapException("No match symbol for ldap filtering");
			}
			
			return $this->symbolMap[$e->getValue()];	
		}else{
			return $e->getValue();
		}
	}
	
}