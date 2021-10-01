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

	/**
	 * @param JobQueueGroup $jobQueueGroup
	 */
	public function __construct( JobQueueGroup $jobQueueGroup ) {
		$this->jobQueueGroup = $jobQueueGroup;
	}

	/**
	 * @param string[] $dbNames
	 * @param LinkTarget $linkTarget
	 */
	public function invalidate( array $dbNames, LinkTarget $linkTarget ) {
		$this->jobQueueGroup->lazyPush(
			new LocalJobSubmitJob( Title::newFromLinkTarget( $linkTarget ), [ 'dbNames' => $dbNames ] )
		);
	}

}
