<?php
namespace Commonhelp\Ldap\Utils\Expression;

use Commonhelp\Util\Expression\BTreeExpression;
use Commonhelp\Util\Expression\Visitor;

class ValueExpression extends BTreeExpression{
	public function __construct($litteral){
		$this->value = $litteral;
		$this->left = null;
		$this->right = null;
	
	}
	
	public function accept(Visitor $visitor){
		return $visitor->visit($this);
	}
}