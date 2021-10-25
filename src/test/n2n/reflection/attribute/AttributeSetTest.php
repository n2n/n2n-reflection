<?php
namespace n2n\reflection\attribute;

use n2n\reflection\attribute\mock\AttrA;
use n2n\reflection\attribute\mock\AttrB;
use n2n\reflection\attribute\mock\MockClass;
use PHPUnit\Framework\TestCase;

class AttributeSetTest extends TestCase {
    private $attributeSet;

	protected function setUp(): void {
		$this->attributeSet = new AttributeSet(new \ReflectionClass(MockClass::class));
	}

	public function testReadClassAttributes() {
		$classAttributes = $this->attributeSet->getClassAttributes();

		foreach($classAttributes as $classAttribute) {
			$this->assertInstanceOf(ClassAttribute::class, $classAttribute);
			$this->assertInstanceOf(\ReflectionAttribute::class, $classAttribute->getAttribute());
			$this->assertIsNumeric($classAttribute->getLine());
			$this->assertIsString($classAttribute->getFile());
		}
	}

    public function testReadClassAttribute() {
        $classAttribute = $this->attributeSet->getClassAttribute(AttrB::class);

        $this->assertInstanceOf(ClassAttribute::class, $classAttribute);
        $this->assertInstanceOf(\ReflectionAttribute::class, $classAttribute->getAttribute());
        $this->assertEquals(AttrB::class, $classAttribute->getAttribute()->getName());
        $this->assertIsString($classAttribute->getFile());
        $this->assertEquals(4, $classAttribute->getLine());
    }

	public function testReadPropertyAttributes() {
		$propertyAttributes = $this->attributeSet->getPropertyAttributes();

		foreach($propertyAttributes as $propertyAttribute) {
			$this->assertInstanceOf(PropertyAttribute::class, $propertyAttribute);
			$this->assertInstanceOf(\ReflectionAttribute::class, $propertyAttribute->getAttribute());
			$this->assertIsNumeric($propertyAttribute->getLine());
			$this->assertIsString($propertyAttribute->getFile());
		}

		$this->assertNotNull($this->attributeSet->getPropertyAttribute('publicProperty', AttrA::class));
		$this->assertNotNull($this->attributeSet->getPropertyAttribute('protectedProperty', AttrB::class));
		$this->assertNull($this->attributeSet->getPropertyAttribute('privateProperty', AttrA::class));

		$this->assertTrue($this->attributeSet->hasPropertyAttribute('publicProperty', AttrA::class));
		$this->assertTrue($this->attributeSet->hasPropertyAttribute('protectedProperty', AttrB::class));
		$this->assertFalse($this->attributeSet->hasPropertyAttribute('privateProperty', AttrB::class));
	}

    public function testReadPropertyAttribute() {
        $attribute = $this->attributeSet->getPropertyAttribute('publicProperty', AttrA::class);

        $this->assertInstanceOf(PropertyAttribute::class, $attribute);
        $this->assertInstanceOf(\ReflectionAttribute::class, $attribute->getAttribute());
        $this->assertEquals(AttrA::class, $attribute->getAttribute()->getName());
        $this->assertIsString($attribute->getFile());

        $this->assertEquals(7, $attribute->getLine());
        $protectedProperty = $this->attributeSet->getPropertyAttribute('protectedProperty', AttrB::class);
        $this->assertEquals(9, $protectedProperty->getLine());

        $privateProperty = $this->attributeSet->getPropertyAttribute('privateProperty', AttrA::class);
        $this->assertNull($privateProperty);
    }

	public function testReadMethodAttributes() {
		$methodAttributes = $this->attributeSet->getMethodAttributes();
		foreach($methodAttributes as $methodAttribute) {
			$this->assertInstanceOf(MethodAttribute::class, $methodAttribute);
			$this->assertInstanceOf(\ReflectionAttribute::class, $methodAttribute->getAttribute());
			$this->assertIsNumeric($methodAttribute->getLine());
			$this->assertIsString($methodAttribute->getFile());
		}

		$this->assertNotNull($this->attributeSet->getMethodAttribute('publicMethod', AttrA::class));
		$this->assertNotNull($this->attributeSet->getMethodAttribute('protectedMethod', AttrB::class));
		$this->assertNull($this->attributeSet->getMethodAttribute('privateMethod', AttrA::class));

		$this->assertTrue($this->attributeSet->hasMethodAttribute('publicMethod', AttrA::class));
		$this->assertTrue($this->attributeSet->hasMethodAttribute('protectedMethod', AttrB::class));
		$this->assertFalse($this->attributeSet->hasMethodAttribute('privateMethod', AttrB::class));
	}

    public function testReadMethodAttribute() {
        $attribute = $this->attributeSet->getMethodAttribute('publicMethod', AttrA::class);
        $this->assertInstanceOf(MethodAttribute::class, $attribute);
        $this->assertInstanceOf(\ReflectionAttribute::class, $attribute->getAttribute());
        $this->assertEquals(AttrA::class, $attribute->getAttribute()->getName());
        $this->assertIsString($attribute->getFile());

        $this->assertEquals(13, $attribute->getLine());
        $protectedMethod = $this->attributeSet->getMethodAttribute('protectedMethod', AttrB::class);
        $this->assertEquals(18, $protectedMethod->getLine());

        $privateMethod = $this->attributeSet->getMethodAttribute('privateMethod', AttrA::class);
        $this->assertNull($privateMethod);
    }

}