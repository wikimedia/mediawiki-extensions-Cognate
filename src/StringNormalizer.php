<?php

namespace Cognate;

/**
 * BIG WARNING!!!!!   L(・o・)」
 * Any changes in this class that result in different normalizations will require the
 * cognate_titles table to be rebuilt.
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class StringNormalizer {

	/**
	 * @var string[]
	 */
	private $replacements = [
		// U+02BC and U+2019 normalized to U+0027
		'ʼ' => '\'',
		'’' => '\'',
		// U+2026 normalized to U+002EU+002EU+002E
		'…' => '...',
		// U+0020 normalized to U+005F
		' ' => '_',
	];

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	public function normalize( $string ) {
		return str_replace(
			array_keys( $this->replacements ),
			array_values( $this->replacements ),
			$string
		);
	}

}
