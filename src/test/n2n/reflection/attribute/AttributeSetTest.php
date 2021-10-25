<?php
namespace n2n\reflection\attribute;

use n2n\reflection\attribute\mock\AttrA;
use n2n\reflection\attribute\mock\AttrB;
use n2n\reflection\attribute\mock\MockClass;
use PHPUnit\Framework\TestCase;

class AttributeSetTest extends TestCase {
	private $mockClass;

	protected function setUp(): void {
		$this->mockClass = new \ReflectionClass(MockClass::class);
	}

	public function testReadClassAttributes() {
		$attributeSet = new AttributeSet($this->mockClass);

		$classAttributes = $attributeSet->getClassAttributes();

		foreach($classAttributes as $classAttribute) {
			$this->assertInstanceOf(ClassAttribute::class, $classAttribute);
			$this->assertInstanceOf(\ReflectionAttribute::class, $classAttribute->getAttribute());
			$this->assertIsNumeric($classAttribute->getLine());
			$this->assertIsString($classAttribute->getFile());
		}
	}

    public function testReadClassAttribute() {
        $attributeSet = new AttributeSet($this->mockClass);
        $classAttribute = $attributeSet->getClassAttribute(AttrB::class);

        $this->assertInstanceOf(ClassAttribute::class, $classAttribute);
        $this->assertInstanceOf(\ReflectionAttribute::class, $classAttribute->getAttribute());
        $this->assertIsNumeric($classAttribute->getLine());
        $this->assertIsString($classAttribute->getFile());
    }

	public function testReadPropertyAttributes() {
		$attributeSet = new AttributeSet($this->mockClass);

		$propertyAttributes = $attributeSet->getPropertyAttributes();

		foreach($propertyAttributes as $propertyAttribute) {
			$this->assertInstanceOf(PropertyAttribute::class, $propertyAttribute);
			$this->assertInstanceOf(\ReflectionAttribute::class, $propertyAttribute->getAttribute());
			$this->assertIsNumeric($propertyAttribute->getLine());
			$this->assertIsString($propertyAttribute->getFile());
		}

		$this->assertNotNull($attributeSet->getPropertyAttribute('publicProperty', AttrA::class));
		$this->assertNotNull($attributeSet->getPropertyAttribute('protectedProperty', AttrB::class));
		$this->assertNull($attributeSet->getPropertyAttribute('privateProperty', AttrA::class));

		$this->assertTrue($attributeSet->hasPropertyAttribute('publicProperty', AttrA::class));
		$this->assertTrue($attributeSet->hasPropertyAttribute('protectedProperty', AttrB::class));
		$this->assertFalse($attributeSet->hasPropertyAttribute('privateProperty', AttrB::class));
	}

	public function testReadMethodAttributes() {
		$attributeSet = new AttributeSet($this->mockClass);

		$methodAttributes = $attributeSet->getMethodAttributes();

		foreach($methodAttributes as $methodAttribute) {
			$this->assertInstanceOf(MethodAttribute::class, $methodAttribute);
			$this->assertInstanceOf(\ReflectionAttribute::class, $methodAttribute->getAttribute());
			$this->assertIsNumeric($methodAttribute->getLine());
			$this->assertIsString($methodAttribute->getFile());
		}

		$this->assertNotNull($attributeSet->getMethodAttribute('publicMethod', AttrA::class));
		$this->assertNotNull($attributeSet->getMethodAttribute('protectedMethod', AttrB::class));
		$this->assertNull($attributeSet->getMethodAttribute('privateMethod', AttrA::class));

		$this->assertTrue($attributeSet->hasMethodAttribute('publicMethod', AttrA::class));
		$this->assertTrue($attributeSet->hasMethodAttribute('protectedMethod', AttrB::class));
		$this->assertFalse($attributeSet->hasMethodAttribute('privateMethod', AttrB::class));
	}

}