<?php
namespace n2n\reflection\attribute\mock;

use n2n\reflection\annotation\AnnoInit;
use n2n\reflection\attribute\legacy\mock\LegacyPreSuffixMock;

#[AttrA]
class MockSubClass extends MockClass {

	private static function _annos(AnnoInit $ai) {
		$ai->p('subKey', new LegacyPreSuffixMock('sub[', ']'));
	}

	#[AttrC]
	const SUB_CONST = 'sub';

	#[AttrC]
	public $subProperty;

	private $subKey;

	#[AttrC]
	public function subMethod() {
	}

	#[AttrC]
	protected function protectedMethod() {
	}
}
