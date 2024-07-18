<?php
namespace n2n\reflection\magic;

use PHPUnit\Framework\TestCase;
use n2n\reflection\magic\mock\MethodsObj;
use n2n\reflection\ReflectionRuntimeException;

class MagicMethodInvokerTest extends TestCase {
	
	function testStringToIntTest() {
		
		
		$class = new \ReflectionClass(MethodsObj::class);
		
		$invoker = new MagicMethodInvoker();
		$invoker->setParamValue('intParam', 'holeradio');
		
		$this->expectException(\TypeError::class);
		$invoker->invoke(new MethodsObj(), $class->getMethod('intMethod'));
		
	}

	function testUnion() {



		$param = new \DateTime();
		$methodsObjMock = $this->createMock(MethodsObj::class);
		$methodsObjMock->expects($this->once())->method('union')->with($param);
		$class = new \ReflectionClass($methodsObjMock);

		$invoker = new MagicMethodInvoker();
		$invoker->setClassParamObject(\DateTime::class, $param);
		$invoker->invoke($methodsObjMock, $class->getMethod('union'));


		$param = new \ArrayObject();
		$methodsObjMock = $this->createMock(MethodsObj::class);
		$methodsObjMock->expects($this->once())->method('union')->with($param);
		$class = new \ReflectionClass($methodsObjMock);

		$invoker = new MagicMethodInvoker();
		$invoker->setClassParamObject(\ArrayObject::class, new \ArrayObject());
		$invoker->invoke($methodsObjMock, $class->getMethod('union'));

	}
}
