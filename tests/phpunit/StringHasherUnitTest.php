<?php

namespace Cognate\Tests;

use Cognate\StringHasher;
use Normalizer;
use UtfNormal\Validator;

/**
 * @covers Cognate\StringHasher
 *
 * @license GNU GPL v2+
 * @author Addshore
 */
class StringHasherTest extends \MediaWikiTestCase {

	public function provideHashes() {
		return [
			[ '', -3162216497309240828 ],
			[ ' ', 8220739050724508201 ],
			[ '1234567890', -1727145863275539665 ],
			[ 'Foo', 1393519376951501709 ],
			[ 'ABCdefGHIjklMNOpqrSTUvwxYZ', -3278377699739471302 ],
			[ 'A', 9206873250291191720 ],
			[ 'One More Title 9999999.......', -4134646102014662051 ],
			[ '✱ ✲ ✳ ✴ ✵ ✶ ✷ ✸', -2506224040903719630 ],
			[ 'â± â² â³ â´ âµ â¶ â· â¸', 220534062196378025 ],
			[ '删除纪录/档案馆/2004年3月', -4813991744441806855 ],
			[ Validator::toNFC( 'á' ), 3942642585148547196 ],
			[ Validator::toNFD( 'á' ), 3942642585148547196 ],
			[ Validator::toNFKC( 'á' ), 3942642585148547196 ],
			[ Validator::toNFKD( 'á' ), 3942642585148547196 ],
		];
	}

	/**
	 * @dataProvider provideHashes
	 */
	public function testGoodHashes( $input, $expectedHash ) {
		$normalizer = new StringHasher();

		$output = $normalizer->hash( $input );

		$this->assertType( 'int', $output );
		$this->assertEquals( $expectedHash, $output );
	}

}