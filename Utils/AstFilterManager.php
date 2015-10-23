<?php
namespace Commonhelp\Ldap;

use ArrayAccess;
use Commonhelp\Ldap\Filters\AttributeExpression;
use Commonhelp\Util\Expression\AstManager;

class AstFilterManager implements AstManager, ArrayAccess{
	
	protected $ast;
	protected $visitor;
	
	protected $attributes;
	
	public function __construct(){
		$this->visitor = new Filter();
		$this->ast = null;
		$this->attributes = null;
	}
	
	public function filter($expression){
		$this->ast=$expression;
	}
	
	public function attributes(){
		$numAttrs = func_num_args();
		if($numAttrs > 0){
			$attrs = func_get_args();
			foreach($attrs as $attr){
				$this->attributes[] = $attr;
			}
			
			return $this;
		}
		
		return $this->attributes;
	}
	
	public function toFilter(){
		if(null === $this->ast){
			return "";
		}
		
		return $this->ast->accept($this->visitor);
	}
	
	
	public function getAst(){
		return $this->ast;
	}
	
	public function getVisitor(){
		return $this->visitor;
	}
	
	public function __toString(){
		return $this->toFilter();
	}
	
	public function offsetGet($offset) {
		return new AttributeExpression($offset);
	}
	
	public function offsetUnset($offset) {
	}
	
	public function offsetExists($offset) {
	}
	
	public function offsetSet($offset, $value) {
	}
	
}