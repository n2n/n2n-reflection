<?php

namespace n2n\reflection\attribute;

use PHPUnit\Framework\TestCase;
use n2n\reflection\ReflectionContext;
use n2n\reflection\attribute\mock\ExtractClassLineMock;
use n2n\reflection\attribute\mock\AttrA;
use n2n\reflection\attribute\mock\AttrB;
use n2n\reflection\attribute\mock\AttrC;

class AttributeUtilsTest extends TestCase {
	public function testReadLine() {
		$class = new \ReflectionClass(ExtractClassLineMock::class);

		$attrLine = AttributeUtils::extractClassAttributeLine(AttrA::class, $class);
		$this->assertEquals(5, $attrLine);

		$const = $class->getReflectionConstant('TEST_CONST');
		$attrLine = AttributeUtils::extractClassConstantAttributeLine(AttrB::class, $const);
		$this->assertEquals(7, $attrLine);

		$property = $class->getProperty('testProp');
		$attrLine = AttributeUtils::extractPropertyAttributeLine(AttrC::class, $property);
		$this->assertEquals(10, $attrLine);

		$method = $class->getMethod('testMethod');
		$attrLine = AttributeUtils::extractMethodAttributeLine(AttrA::class, $method);
		$this->assertEquals(13, $attrLine);
	}
}