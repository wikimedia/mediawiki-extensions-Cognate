<?php

namespace Cognate;

use Job;
use JobQueueGroup;
use Title;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class LocalJobSubmitJob extends Job {

	public function __construct( Title $title, array $params ) {
		parent::__construct( 'CognateLocalJobSubmitJob', $title, $params );
	}

	public function run() {
		$job = new CacheUpdateJob( $this->getTitle(), [] );

		foreach ( $this->params['dbNames'] as $dbName ) {
			JobQueueGroup::singleton( $dbName )->push( $job );
		}

		return true;
	}

}
