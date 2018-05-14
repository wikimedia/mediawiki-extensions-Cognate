<?php

namespace Cognate;

use InvalidArgumentException;
use RuntimeException;
use UtfNormal\Validator;

/**
 * BIG WARNING!!!!!   L(・o・)」
 * Any changes in this class that result in different hashes will require all tables to be rebuilt.
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class StringHasher {

	/**
	 * @param string $string
	 *
	 * @throws InvalidArgumentException
	 * @throws RuntimeException if not run on a 64 bit system
	 * @return int a 64 bit SIGNED decimal hash
	 */
	public function hash( $string ) {
		if ( PHP_INT_SIZE !== 8 ) {
			// 32 bit systems will result in poor hashes
			throw new RuntimeException( 'Cognate must run on a 64bit system' );
		}
		if ( !is_string( $string ) ) {
			throw new InvalidArgumentException(
				'Tried to hash a non string value.'
			);
		}

		$string = Validator::toNFC( $string );

		return $this->hex2int(
			substr(
				hash( 'sha256', $string ),
				0,
				16
			)
		);
	}

	/**
	 * Replacement for the php hexdec function.
	 * hexdec( 'FFFFFFFFFFFFFFFF' ) === hexdec( 'FFFFFFFFFFFFFFF0' )
	 * There are gaps in high values that would increase the chance of collisions.
	 * So use this replacement instead.
	 *
	 * @param string $hex16
	 *
	 * @return int
	 */
	private function hex2int( $hex16 ) {
		$hexhi = substr( $hex16, 0, 8 );
		$hexlo = substr( $hex16, 8, 8 );

		$int = hexdec( $hexlo ) | ( hexdec( $hexhi ) << 32 );
		return $int;
	}

}
