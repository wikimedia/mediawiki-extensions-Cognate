<?php

namespace Cognate;

use Job;
use JobQueueGroup;
use MediaWiki\MediaWikiServices;
use Title;

class LocalJobSubmitJob extends Job {

	/**
	 * @var string
	 */
	private $languageCode;

	public function __construct( Title $title, array $params = [] ) {
		parent::__construct( 'CognateLocalJobSubmitJob', $title, $params );
		$this->languageCode = $params['languageCode'];
	}

	public function run() {
		$job = new CacheUpdateJob( $this->getTitle() );

		/** @var CognateRepo $repo */
		$repo = MediaWikiServices::getInstance()->getService( 'CognateRepo' );
		$group = MediaWikiServices::getInstance()->getMainConfig()->get( 'CognateGroup' );
		$languages = $repo->getLinksForPage( $this->languageCode, $this->getTitle() );
		// In the case of a delete causing cache invalidations we need to add the local site back to
		// the list as it has already been removed from the database.
		$languages[] = $this->languageCode;

		foreach ( array_unique( $languages ) as $language ) {
			$wiki = $language . $group;
			JobQueueGroup::singleton( $wiki )->push( $job );
		}
	}
}
