<?php
namespace n2n\reflection\attribute\mock;

use n2n\reflection\annotation\AnnoInit;
use n2n\reflection\attribute\legacy\mock\LegacyPreSuffixMock;

#[AttrA, AttrB, AttrC]
class MockClass {

	private static function _annos(AnnoInit $ai) {
		$ai->p('key', new LegacyPreSuffixMock('key[', ']'));
	}

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

	private $key;

	#[AttrA]
	public function publicMethod() {

	}

	#[AttrB]
	protected function protectedMethod() {

	}

	private function privateMethod() {

	}
}