<?php
namespace n2n\reflection\attribute\legacy\mock;

use n2n\persistence\orm\annotation\AnnoOneToMany;
use n2n\persistence\orm\annotation\AnnoTable;
use n2n\reflection\annotation\AnnoInit;
use n2n\reflection\attribute\mock\AttrA;
use n2n\reflection\attribute\mock\AttrB;
use n2n\reflection\attribute\mock\AttrC;

#[AttrB, AttrC]
class LegacyMockClass {

	private static function _annos(AnnoInit $ai) {
		$ai->c(new LegacyClassAnnoMock());
		$ai->p('publicProperty', new LegacyPropertyAnnoMock());
		$ai->p('protectedProperty', new LegacyPropertyAnnoMock());
		$ai->m('publicMethod', new LegacyMethodAnnoMock());
		$ai->m('protectedMethod', new LegacyMethodAnnoMock());
	}

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