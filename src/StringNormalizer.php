<?php

/**
 * @license GNU GPL v2+
 * @author Addshore
 */
class StringNormalizer {

	private $replacements = [
		'’' => '\'',
		'…' => '...',
		'_' => ' ',
	];

	/**
	 * @param string $string
	 *
	 * @return mixed
	 */
	public function normalize( $string ) {
		foreach ( $this->replacements as $find => $replacement ) {
			$string = str_replace( $find, $replacement, $string );
		}
		return $string;
	}

}
