<?php
namespace n2n\reflection\attribute\legacy;

use n2n\reflection\annotation\AnnotationSetFactory;
use n2n\reflection\attribute\AttributeSet;
use n2n\reflection\attribute\ClassAttribute;
use n2n\reflection\attribute\legacy\mock\LegacyMockClass;
use n2n\reflection\attribute\MethodAttribute;
use n2n\reflection\attribute\mock\AttrA;
use n2n\reflection\attribute\mock\AttrB;
use n2n\reflection\attribute\mock\AttrC;
use n2n\reflection\attribute\PropertyAttribute;
use PHPUnit\Framework\TestCase;

class LegacyConverterTest extends TestCase {
	private $mockClass;

	protected function setUp(): void {
		$this->mockClass = new \ReflectionClass(LegacyMockClass::class);
	}

	public function testReadClassAttributes() {
		$attributeSet = new LegacyConverter(AnnotationSetFactory::create($this->mockClass));

		$classAttributes = $attributeSet->getClassAttributes();

		foreach($classAttributes as $classAttribute) {
			$this->assertInstanceOf(ClassAttribute::class, $classAttribute);
			$this->assertInstanceOf(\ReflectionAttribute::class, $classAttribute->getAttribute());
			$this->assertIsNumeric($classAttribute->getLine());
			$this->assertIsString($classAttribute->getFile());
		}
	}

	public function testReadPropertyAttributes() {
		$legacyConverter = new LegacyConverter(AnnotationSetFactory::create($this->mockClass));

		$propertyAttributes = $legacyConverter->getPropertyAttributes();

		foreach($propertyAttributes as $propertyAttributeArr) {
			foreach ($propertyAttributeArr as $propertyAttribute) {
				$this->assertInstanceOf(PropertyAttribute::class, $propertyAttribute);
				$this->assertInstanceOf(\ReflectionAttribute::class, $propertyAttribute->getAttribute());
				$this->assertIsNumeric($propertyAttribute->getLine());
				$this->assertIsString($propertyAttribute->getFile());
			}
		}

//		$this->assertNotNull($legacyConverter->getPropertyAttribute('publicProperty', AttrB::class));
//		$this->assertNotNull($legacyConverter->getPropertyAttribute('protectedProperty', AttrB::class));
//		$this->assertNull($legacyConverter->getPropertyAttribute('privateProperty', AttrB::class));
//
//		$this->assertTrue($legacyConverter->hasPropertyAttribute('publicProperty', AttrA::class));
//		$this->assertTrue($legacyConverter->hasPropertyAttribute('protectedProperty', AttrB::class));
//		$this->assertFalse($legacyConverter->hasPropertyAttribute('privateProperty', AttrB::class));
	}

	public function testReadMethodAttributes() {
		$legacyConverter = new LegacyConverter(AnnotationSetFactory::create($this->mockClass), $this->mockClass);

		$methodAttributes = $legacyConverter->getMethodAttributes();

		foreach($methodAttributes as $methodAttributeArr) {
			foreach ($methodAttributeArr as $methodAttribute) {
				$this->assertInstanceOf(MethodAttribute::class, $methodAttribute);
				$this->assertInstanceOf(\ReflectionAttribute::class, $methodAttribute->getAttribute());
				$this->assertIsNumeric($methodAttribute->getLine());
				$this->assertIsString($methodAttribute->getFile());
			}
		}

//		$this->assertNotNull($attributeSet->getMethodAttribute('publicMethod', AttrA::class));
//		$this->assertNotNull($attributeSet->getMethodAttribute('protectedMethod', AttrB::class));
//		$this->assertNull($attributeSet->getMethodAttribute('privateMethod', AttrA::class));
//
//		$this->assertTrue($attributeSet->hasMethodAttribute('publicMethod', AttrA::class));
//		$this->assertTrue($attributeSet->hasMethodAttribute('protectedMethod', AttrB::class));
//		$this->assertFalse($attributeSet->hasMethodAttribute('privateMethod', AttrB::class));
	}

	public function testAttributeSetLegacyIntegration() {
		$attributeSet = new AttributeSet($this->mockClass);

		$this->assertCount(3, $attributeSet->getClassAttributes());
		$this->assertTrue($attributeSet->hasClassAttribute(AttrA::class));
		$this->assertTrue($attributeSet->hasClassAttribute(AttrA::class));
		$this->assertTrue($attributeSet->hasPropertyAttribute('publicProperty', AttrB::class));
		$this->assertTrue($attributeSet->hasMethodAttribute('protectedMethod', AttrC::class));
		$this->assertFalse($attributeSet->hasMethodAttribute('privateMethod', AttrC::class));
	}
}