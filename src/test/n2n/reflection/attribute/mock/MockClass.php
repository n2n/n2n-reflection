<?php
namespace n2n\reflection\attribute\mock;

#[AttrA, AttrB, AttrC]
class MockClass {

    #[AttrA, AttrB, AttrC]
    const TEST = 'test';
    #[AttrA]
    protected const PUBLIC_CONST = 'test';
    #[AttrB]
    protected const PROTECTED_CONST = 'test';
    private const PRIVATE_CONST = 'test';

	#[AttrA]
	public $publicProperty;
	#[AttrB]
	protected $protectedProperty;
	private $privateProperty;

	#[AttrA]
	public function publicMethod() {

	}

	#[AttrB]
	protected function protectedMethod() {

	}

	private function privateMethod() {

	}
}