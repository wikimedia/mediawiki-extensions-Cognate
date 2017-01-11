<?php

namespace Cognate;

use HTMLFileCache;
use Job;
use Title;

/**
 * A job that runs on local wikis to purge CDN and possibly
 * queue local HTMLCacheUpdate jobs
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class CacheUpdateJob extends Job {

	/**
	 * @param Title $title
	 */
	public function __construct( Title $title ) {
		parent::__construct( 'CognateCacheUpdateJob', $title );
	}

	public function run() {
		$title = $this->getTitle();
		$title->purgeSquid();
		HTMLFileCache::clearFileCache( $title );
	}

}
