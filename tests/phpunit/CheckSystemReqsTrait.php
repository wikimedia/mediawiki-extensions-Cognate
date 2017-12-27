<?php

namespace Cognate\Tests;

/**
 * Trait that checks that system requirements
 */
trait CheckSystemReqsTrait {

	/**
	 * Check, if running on a 64bit system
	 */
	protected function markTestSkippedIfNo64bit() {
		if ( PHP_INT_SIZE !== 8 ) {
			// StringHasher throws RunTimeException
			$this->markTestSkipped( "Skip test, needs 64bit system" );
		}
	}

}
