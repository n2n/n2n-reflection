<?php

namespace n2n\reflection\attribute\mock;

#[AttrA]
class ExtractClassLineMock {
	#[AttrB]
	private const TEST_CONST = 'test';

	#[AttrC]
	private $testProp = 'test';

	#[AttrA]
	public function testMethod() {

	}
}