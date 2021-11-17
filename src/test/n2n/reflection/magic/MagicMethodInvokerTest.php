<?php
namespace n2n\reflection\magic;

use PHPUnit\Framework\TestCase;
use n2n\reflection\magic\mock\MethodsObj;

class MagicMethodInvokerTest extends TestCase {
	
	function testStringToIntTest() {
		
		
		$class = new \ReflectionClass(MethodsObj::class);
		
		$invoker = new MagicMethodInvoker();
		$invoker->setParamValue('intParam', 'holeradio');
		
		$this->expectException(\TypeError::class);
		$invoker->invoke(new MethodsObj(), $class->getMethod('intMethod'));
		
	}
}
