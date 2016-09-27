<?php

/**
 * @license GNU GPL v2+
 * @author Addshore
 */
class StringNormalizerTest extends MediaWikiTestCase {

	public function provideNormalizations() {
		return [
			[ 'JustAString', 'JustAString' ],
			[ 'Foo_bar', 'Foo bar' ],
			[ 'Apostrophe’', 'Apostrophe\'' ],
			[ 'ellipsis…', 'ellipsis...' ],
		];
	}

	/**
	 * @dataProvider provideNormalizations
	 */
	public function testGoodNormalizations( $inputOne, $inputTwo ) {
		$normalizer = new StringNormalizer();

		$one = $normalizer->normalize( $inputOne );

		$this->assertEquals( $one, $inputTwo );
	}

}