<?php
namespace n2n\reflection\property;

use PHPUnit\Framework\TestCase;
use n2n\util\type\custom\Undefined;
use n2n\util\type\TypeConstraints;

class ReflectionAccessProxyTest extends TestCase {
    private object $testClass;

    protected function setUp(): void {
        $this->testClass = $this->createTestClass();
    }

    private function createTestClass(): object {
        return new class() {
            public int $publicInt = 0;

            public ?string $publicNullableString = null;

            public string $publicUninitializedString;

            public readonly int $publicReadonlyInt;

            public \n2n\util\type\custom\Undefined|string|null $publicUndefinedableString;

            private int $privateInt = 0;

            private ?string $privateNullableString = null;

            private string $privateString = 'val';

            public function __construct() {
                $this->publicReadonlyInt = 1;
            }

            public function getPrivateString(): string {
                return $this->privateString;
            }

            public function setPrivateString(string $value): void {
                $this->privateString = $value;
            }
        };
    }

	/**
	 * @throws PropertyValueTypeMismatchException
	 * @throws \ReflectionException
	 * @throws PropertyAccessException
	 */
	public function testGetAndSetPublicProperty(): void {
        $class = new \ReflectionClass($this->testClass);
        $prop = $class->getProperty('publicInt');

        $ap = new ReflectionAccessProxy('publicInt', $prop, null, null, UninitializedBehaviour::RETURN_NULL);
        $this->assertTrue($ap->isReadable());
        $this->assertTrue($ap->isWritable());

        $ap->setValue($this->testClass, 123);
        $this->assertSame(123, $ap->getValue($this->testClass));
    }

	/**
	 * @throws \ReflectionException
	 * @throws PropertyAccessException
	 */
	public function testSetWithTypeMismatchThrows(): void {
        $class = new \ReflectionClass($this->testClass);
        $prop = $class->getProperty('publicInt');

        $ap = new ReflectionAccessProxy('publicInt', $prop, null, null, UninitializedBehaviour::RETURN_NULL);

        $this->expectException(PropertyValueTypeMismatchException::class);
        $ap->setValue($this->testClass, 'not-an-int');
    }

	/**
	 * @throws PropertyValueTypeMismatchException
	 * @throws \ReflectionException
	 * @throws PropertyAccessException
	 */
	public function testGetWithGetterMethodConstraintValidation(): void {
        $class = new \ReflectionClass($this->testClass);
        $prop = $class->getProperty('privateString');
        $getter = $class->getMethod('getPrivateString');
        $setter = $class->getMethod('setPrivateString');

        // Use methods for access, property is private
        $ap = new ReflectionAccessProxy('privateString', $prop, $getter, $setter, UninitializedBehaviour::RETURN_NULL);
        $this->assertTrue($ap->isReadable());
        $this->assertTrue($ap->isWritable());

        $ap->setValue($this->testClass, 'hello');
        $this->assertSame('hello', $ap->getValue($this->testClass));
    }

	/**
	 * @throws \ReflectionException
	 * @throws PropertyValueTypeMismatchException
	 * @throws PropertyAccessException
	 */
	public function testUninitializedBehaviourReturnNull(): void {
        $class = new \ReflectionClass($this->testClass);
        $prop = $class->getProperty('publicUninitializedString');

        $ap = new ReflectionAccessProxy('publicUninitializedString', $prop, null, null, UninitializedBehaviour::RETURN_NULL);
        $this->assertNull($ap->getValue($this->testClass));
    }

	/**
	 * @throws \ReflectionException
	 * @throws PropertyAccessException
	 * @throws PropertyValueTypeMismatchException
	 */
	public function testUninitializedBehaviourReturnUndefined(): void {
        $class = new \ReflectionClass($this->testClass);
        $prop = $class->getProperty('publicUninitializedString');

        $ap = new ReflectionAccessProxy('publicUninitializedString', $prop, null, null, UninitializedBehaviour::RETURN_UNDEFINED);
        $value = $ap->getValue($this->testClass);
        $this->assertInstanceOf(Undefined::class, $value);
        $this->assertSame(Undefined::val(), $value);
    }

	/**
	 * @throws \ReflectionException
	 * @throws PropertyValueTypeMismatchException
	 */
	public function testUninitializedBehaviourThrowException(): void {
        $class = new \ReflectionClass($this->testClass);
        $prop = $class->getProperty('publicUninitializedString');

        $ap = new ReflectionAccessProxy('publicUninitializedString', $prop,
				null, null, UninitializedBehaviour::THROW_EXCEPTION);
        $this->expectException(PropertyAccessException::class);
        $ap->getValue($this->testClass);
    }

	/**
	 * @throws \ReflectionException
	 * @throws PropertyAccessException
	 * @throws PropertyValueTypeMismatchException
	 */
	public function testUninitializedBehaviourReturnUndefinedIfUndefinable(): void {
        $class = new \ReflectionClass($this->testClass);
        // Property explicitly allows Undefined in union type
        $prop = $class->getProperty('publicUndefinedableString');

        $ap = new ReflectionAccessProxy('publicUndefinedableString', $prop, null, null,
				UninitializedBehaviour::RETURN_UNDEFINED_IF_UNDEFINABLE);
        $value = $ap->getValue($this->testClass);
        $this->assertInstanceOf(Undefined::class, $value);
    }

	/**
	 * @throws \ReflectionException
	 * @throws PropertyAccessException
	 */
	public function testForcePropertyAccessForPrivateProperty(): void {
        $class = new \ReflectionClass($this->testClass);
        $prop = $class->getProperty('privateInt');

        $ap = new ReflectionAccessProxy('privateInt', $prop);
        $this->assertFalse($ap->isReadable());
        $this->assertFalse($ap->isWritable());

        $ap->setForcePropertyAccess(true);
        $this->assertTrue($ap->isReadable());
        $this->assertTrue($ap->isWritable());

        $ap->setValue($this->testClass, 77);
        $this->assertSame(77, $ap->getValue($this->testClass));
    }

	/**
	 * @throws \ReflectionException
	 */
	public function testSetConstraintConflictThrows(): void {
        $class = new \ReflectionClass($this->testClass);
        $prop = $class->getProperty('publicInt');
        $ap = new ReflectionAccessProxy('publicInt', $prop);

        $this->expectException(ConstraintsConflictException::class);
        $ap->setConstraint(TypeConstraints::type('string'));
    }


	/**
	 * @throws \ReflectionException
	 * @throws PropertyAccessException
	 * @throws ConstraintsConflictException
	 */
	public function testReturnedValueValidationFails(): void {
        $class = new \ReflectionClass($this->testClass);
        $prop = $class->getProperty('publicNullableString');
        $ap = new ReflectionAccessProxy('publicNullableString', $prop);
        $ap->setConstraint(TypeConstraints::type('string'));

        $this->expectException(PropertyValueTypeMismatchException::class);
        $ap->getValue($this->testClass);
    }

	/**
	 * @throws PropertyValueTypeMismatchException
	 * @throws ConstraintsConflictException
	 * @throws \ReflectionException
	 * @throws PropertyAccessException
	 */
	public function testNullReturnAllowedSkipsValidation(): void {
        $class = new \ReflectionClass($this->testClass);
        $prop = $class->getProperty('publicNullableString');
        $ap = new ReflectionAccessProxy('publicNullableString', $prop);
        $ap->setConstraint(TypeConstraints::type('string'));
        $ap->setNullReturnAllowed(true);

        $this->assertNull($ap->getValue($this->testClass));
    }
}


