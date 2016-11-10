<?php

namespace Cognate;

use MediaWiki\Linker\LinkTarget;
use Title;
use TitleFormatter;

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

	/**
	 * @var TitleFormatter
	 */
	private $titleFormatter;

	public function __construct(
		CognateStore $store,
		CacheInvalidator $cacheInvalidator,
		TitleFormatter $titleFormatter
	) {
		$this->store = $store;
		$this->cacheInvalidator = $cacheInvalidator;
		$this->titleFormatter = $titleFormatter;
	}

	/**
	 * @param string $dbName The dbName for the site
	 * @param LinkTarget $linkTarget
	 *
	 * @return bool
	 */
	public function savePage( $dbName, LinkTarget $linkTarget ) {
		$success = $this->store->insertPage( $dbName, $linkTarget );
		if ( $success ) {
			$this->cacheInvalidator->invalidate(
				$dbName,
				Title::newFromLinkTarget( $linkTarget )
			);
		}

		return $success;
	}

	/**
	 * @param string $dbName The dbName for the site
	 * @param LinkTarget $linkTarget
	 *
	 * @return bool
	 */
	public function deletePage( $dbName, LinkTarget $linkTarget ) {
		$success = $this->store->deletePage( $dbName, $linkTarget );
		if ( $success ) {
			$this->cacheInvalidator->invalidate(
				$dbName,
				Title::newFromLinkTarget( $linkTarget )
			);
		}

		return $success;
	}

	/**
	 * @param string $dbName The dbName of the site being linked from
	 * @param LinkTarget $linkTarget of the page the links should be retrieved for
	 *
	 * @return string[] interwiki links
	 */
	public function getLinksForPage( $dbName, LinkTarget $linkTarget ) {
		$linkDetails = $this->store->selectLinkDetailsForPage( $dbName, $linkTarget );
		$links = [];
		foreach ( $linkDetails as $data ) {
			$links[] = $this->titleFormatter->formatTitle(
				$data['namespaceID'],
				$data['title'],
				'',
				$data['interwiki']
			);
		}
		return $links;
	}

}
