<?php

namespace Cognate\Tests;

use Cognate\StringHasher;
use UtfNormal\Validator;

/**
 * @covers \Cognate\StringHasher
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class StringHasherTest extends \MediaWikiIntegrationTestCase {

	use CheckSystemReqsTrait;

	public function provideHashes() {
		return [
			[ '', -2039914840885289964 ],
			[ ' ', 3938934374763561727 ],
			[ '1234567890', -4074095513246505424 ],
			[ 'Foo', 2071311921841431698 ],
			[ 'ABCdefGHIjklMNOpqrSTUvwxYZ', 4014358822633962249 ],
			[ 'A', 6168500820899059065 ],
			[ 'One More Title 9999999.......', -7201962078110811948 ],
			[ '✱ ✲ ✳ ✴ ✵ ✶ ✷ ✸', -7435652355441782233 ],
			[ 'â± â² â³ â´ âµ â¶ â· â¸', -1051066159924273193 ],
			[ '删除纪录/档案馆/2004年3月', 395730596998145766 ],
			[ Validator::toNFC( 'á' ), -317652819336014565 ],
			[ Validator::toNFD( 'á' ), -317652819336014565 ],
			[ Validator::toNFKC( 'á' ), -317652819336014565 ],
			[ Validator::toNFKD( 'á' ), -317652819336014565 ],
		];
	}

	/**
	 * @dataProvider provideHashes
	 */
	public function testGoodHashes( $input, $expectedHash ) {
		$this->markTestSkippedIfNo64bit();

		$normalizer = new StringHasher();

		$output = $normalizer->hash( $input );

		$this->assertIsInt( $output );
		$this->assertSame( $expectedHash, $output );
	}

}
