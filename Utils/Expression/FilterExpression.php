<?php

namespace Commonhelp\Ldap\Expression;
use Commonhelp\Util\Expression\Visitor;
use Commonhelp\Util\Expression\BTreeExpression;


class FilterExpression extends BTreeExpression{
	public function __construct(BTreeExpression $left, BTreeExpression $right, $symbol){
		$this->value = $symbol;
		$this->left = $left;
		$this->right = $right;
		
	}
	
	public function accept(Visitor $visitor){
		return $visitor->visit($this);
	}
	
}

