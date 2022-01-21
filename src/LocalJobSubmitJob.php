<?php

namespace Cognate;

use Job;
use MediaWiki\MediaWikiServices;
use Title;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class LocalJobSubmitJob extends Job {

	/** @inheritDoc */
	public function __construct( Title $title, array $params ) {
		parent::__construct( 'CognateLocalJobSubmitJob', $title, $params );
	}

	/** @inheritDoc */
	public function run() {
		$job = new CacheUpdateJob( $this->getTitle(), [] );

		$jobQueueGroupFactory = MediaWikiServices::getInstance()->getJobQueueGroupFactory();
		foreach ( $this->params['dbNames'] as $dbName ) {
			$jobQueueGroupFactory->makeJobQueueGroup( $dbName )->push( $job );
		}

		return true;
	}

}
