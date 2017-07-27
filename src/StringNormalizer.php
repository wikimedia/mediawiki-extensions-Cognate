<?php

namespace Cognate;

/**
 * BIG WARNING!!!!!   L(・o・)」
 * Any changes in this class that result in different normalizations will require the
 * cognate_titles table to be rebuilt.
 *
 * @license GNU GPL v2+
 * @author Addshore
 */
class StringNormalizer {

	/**
	 * @var string[]
	 */
	private $replacements = [
		// Normalized to U+0027
		'’' => '\'', // U+2019
		// Normalized to U+002EU+002EU+002E
		'…' => '...', // U+2026
		// Normalized to U+005F
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
