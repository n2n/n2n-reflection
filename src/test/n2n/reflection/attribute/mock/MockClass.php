<?php
namespace n2n\reflection\attribute\mock;

#[AttrA, AttrB, AttrC]
class MockClass {

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