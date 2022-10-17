<?php

namespace n2n\reflection\attribute\mock;

#[AttrA]
class ExtractClassLineMock {
	#[AttrC, AttrB ]
	#[ AttrC , AttrA]
	private const TEST_CONST = 'test';

	#[AttrB]
	#[ AttrC () ] private $testProp = 'test';

	#[AttrA]
	public function testMethod() {

	}

	#[AttrA]
	function testMethod2() {

	}
}