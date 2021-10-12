<?php

namespace Cognate\Tests;

use Cognate\StringNormalizer;

/**
 * @covers \Cognate\StringNormalizer
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class StringNormalizerTest extends \MediaWikiIntegrationTestCase {

	public function provideNormalizations() {
		return [
			[ 'JustAString', 'JustAString' ],
			[ 'Foo bar', 'Foo_bar' ],
			[ 'Apostrophe’', 'Apostrophe\'' ],
			[ 'ellipsis…', 'ellipsis...' ],
			[ 'cʼh', 'c\'h' ],
		];
	}

	/**
	 * @dataProvider provideNormalizations
	 */
	public function testGoodNormalizations( $inputOne, $inputTwo ) {
		$normalizer = new StringNormalizer();

		$one = $normalizer->normalize( $inputOne );

		$this->assertSame( $one, $inputTwo );
	}

}
