<?php

namespace Cognate;

use Job;
use JobQueueGroup;
use MediaWiki\MediaWikiServices;
use Title;

/**
 * @license GNU GPL v2+
 * @author Addshore
 */
class LocalJobSubmitJob extends Job {

	/**
	 * @var string
	 */
	private $dbName;

	public function __construct( Title $title, array $params = [] ) {
		parent::__construct( 'CognateLocalJobSubmitJob', $title, $params );
		$this->dbName = $params['dbName'];
	}

	public function run() {
		$job = new CacheUpdateJob( $this->getTitle() );

		/** @var CognateRepo $repo */
		$repo = MediaWikiServices::getInstance()->getService( 'CognateRepo' );
		$sites = $repo->selectSitesForPage( $this->getTitle() );
		// In the case of a delete causing cache invalidations we need to add the local site back to
		// the list as it has already been removed from the database.
		$sites[] = $this->dbName;

		foreach ( array_unique( $sites ) as $dbName ) {
			JobQueueGroup::singleton( $dbName )->push( $job );
		}

		return true;
	}

}
