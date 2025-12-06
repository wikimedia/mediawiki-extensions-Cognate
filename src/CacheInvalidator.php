<?php

namespace Cognate;

use MediaWiki\JobQueue\JobQueueGroup;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Title\Title;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class CacheInvalidator {

	public function __construct( private readonly JobQueueGroup $jobQueueGroup ) {
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
