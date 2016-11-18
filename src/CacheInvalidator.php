<?php

namespace Cognate;

use JobQueueGroup;
use Title;

/**
 * @license GNU GPL v2+
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
	 * @param string $languageCode
	 * @param Title $title
	 */
	public function invalidate( $languageCode, Title $title ) {
		$this->jobQueueGroup->push(
			new LocalJobSubmitJob( $title, [ 'languageCode' => $languageCode ] )
		);
	}
}
