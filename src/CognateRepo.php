<?php

namespace Cognate;

use MediaWiki\Linker\LinkTarget;
use Title;

/**
 * @license GNU GPL v2+
 * @author Addshore
 */
class CognateRepo {

	/**
	 * @var CognateStore
	 */
	private $store;

	/**
	 * @var CacheInvalidator
	 */
	private $cacheInvalidator;

	public function __construct(
		CognateStore $store,
		CacheInvalidator $cacheInvalidator
	) {
		$this->store = $store;
		$this->cacheInvalidator = $cacheInvalidator;
	}

	/**
	 * @param string $languageCode The language code of the site
	 * @param LinkTarget $linkTarget
	 *
	 * @return bool
	 */
	public function savePage( $languageCode, LinkTarget $linkTarget ) {
		$success = $this->store->savePage( $languageCode, $linkTarget );
		if ( $success ) {
			$this->cacheInvalidator->invalidate(
				$languageCode,
				Title::newFromLinkTarget( $linkTarget )
			);
		}

		return $success;
	}

	/**
	 * @param string $languageCode The language code of the site
	 * @param LinkTarget $linkTarget
	 *
	 * @return bool
	 */
	public function deletePage( $languageCode, LinkTarget $linkTarget ) {
		$success = $this->store->deletePage( $languageCode, $linkTarget );
		if ( $success ) {
			$this->cacheInvalidator->invalidate(
				$languageCode,
				Title::newFromLinkTarget( $linkTarget )
			);
		}

		return $success;
	}

	/**
	 * @param string $languageCode The language code of the site being linked from
	 * @param LinkTarget $linkTarget
	 * @return string[] language codes, excluding the language passed into this method.
	 */
	public function getLinksForPage( $languageCode, LinkTarget $linkTarget ) {
		return $this->store->getLinksForPage( $languageCode, $linkTarget );
	}

}
