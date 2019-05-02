<?php

namespace Cognate;

use JobQueueGroup;
use MediaWiki\Linker\LinkTarget;
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
	 * @param LinkTarget $linkTarget
	 */
	public function invalidate( $dbName, LinkTarget $linkTarget ) {
		$this->jobQueueGroup->push(
			new LocalJobSubmitJob( Title::newFromLinkTarget( $linkTarget ), [ 'dbName' => $dbName ] )
		);
	}

}
