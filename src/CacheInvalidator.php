<?php

namespace Cognate;

use JobQueueGroup;
use Title;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class CacheInvalidator {

	/**
	 * @var JobQueueGroup
	 */
	private $jobQueueGroup;

	public function __construct( JobQueueGroup $jobQueueGroup ) {
		$this->jobQueueGroup = $jobQueueGroup;
	}

	/**
	 * @param string $dbName
	 * @param Title $title
	 */
	public function invalidate( $dbName, Title $title ) {
		$this->jobQueueGroup->push(
			new LocalJobSubmitJob( $title, [ 'dbName' => $dbName ] )
		);
	}

}
