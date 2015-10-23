<?php
namespace Commonhelp\Ldap\Filters;

use Commonhelp\Util\Expression\BTreeExpression;
use Commonhelp\Util\Expression\Visitor;
use Commonhelp\Ldap\Filter;
use Commonhelp\Ldap\FilterExpressionVisitor;
class AttributeExpression extends BTreeExpression{
	public function __construct($litteral){
		$this->value = $litteral;
		$this->left = null;
		$this->right = null;
	
	}
	
	public function eq($other){
		if(!($other instanceof ValueExpression)){
			$other = new ValueExpression($other);
		}
		return new FilterExpression($this, $other, FilterExpressionVisitor::EQUAL);
	}
	
	public function lteq($other){
		if(!($other instanceof ValueExpression)){
			$other = new ValueExpression($other);
		}
		
		return new FilterExpression($this, $other, FilterExpressionVisitor::LESSTHANEQUAL);
	}
	
	public function gteq($other){
		if(!($other instanceof ValueExpression)){
			$other = new ValueExpression($other);
		}
		
		return new FilterExpression($this, $other, FilterExpressionVisitor::GREATERTHANEQUAL);
	}
	
	public function approx(){
		if(!($other instanceof ValueExpression)){
			$other = new ValueExpression($other);
		}
		
		return new FilterExpression($this, $other, FilterExpressionVisitor::APPROX);
	}
	
	public function accept(Visitor $visitor){
		return $visitor->visit($this);
	}
}