<?php

namespace Cognate;

use HTMLCacheUpdateJob;
use Job;
use Title;

/**
 * A job that runs on local wikis running HTMLCacheUpdateJob internally.
 *
 * The creation of the HTMLCacheUpdateJob makes the assumption that when this
 * job is constructed on the local wiki the Title object contained within has
 * the pageId for the local wiki and not the wiki that this job was originally
 * queued from.
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class CacheUpdateJob extends Job {

	/**
	 * @param Title $title
	 * @param array $params
	 */
	public function __construct( Title $title, array $params ) {
		parent::__construct( 'CognateCacheUpdateJob', $title, $params );
	}

	public function run() {
		$title = $this->getTitle();

		$coreJob = new HTMLCacheUpdateJob(
			$title,
			[
				'pages' => [ $title->getArticleID() => [ $title->getNamespace(), $title->getDBkey() ] ],
			]
		);

		return $coreJob->run();
	}

}
