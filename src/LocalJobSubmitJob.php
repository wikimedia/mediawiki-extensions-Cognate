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

	/**
	 * @var string[]
	 */
	private $dbNames;

	public function __construct( Title $title, array $params = [] ) {
		parent::__construct( 'CognateLocalJobSubmitJob', $title, $params );
		$this->dbNames = $params['dbNames'];
	}

	public function run() {
		$job = new CacheUpdateJob( $this->getTitle() );

		foreach ( array_unique( $this->dbNames ) as $dbName ) {
			JobQueueGroup::singleton( $dbName )->push( $job );
		}

		return true;
	}

}
