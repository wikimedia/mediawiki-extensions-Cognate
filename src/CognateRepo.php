<?php

namespace Cognate;

use MediaWiki\Linker\LinkTarget;
use Psr\Log\LoggerInterface;
use Title;
use TitleFormatter;
use Wikimedia\Rdbms\DBReadOnlyError;

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

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	public function __construct(
		CognateStore $store,
		CacheInvalidator $cacheInvalidator,
		TitleFormatter $titleFormatter,
		LoggerInterface $logger
	) {
		$this->store = $store;
		$this->cacheInvalidator = $cacheInvalidator;
		$this->titleFormatter = $titleFormatter;
		$this->logger = $logger;
	}

	/**
	 * @param string $dbName The dbName for the site
	 * @param LinkTarget $linkTarget
	 *
	 * @return bool
	 */
	public function savePage( $dbName, LinkTarget $linkTarget ) {
		try {
			$success = $this->store->insertPage( $dbName, $linkTarget );
		} catch ( DBReadOnlyError $e ) {
			return false;
		}

		if ( $success ) {
			$this->cacheInvalidator->invalidate(
				$dbName,
				Title::newFromLinkTarget( $linkTarget )
			);
		} else {
			$dbKey = $linkTarget->getDBkey();
			$namespace = $linkTarget->getNamespace();
			$this->logger->error(
				'Probable duplicate hash for dbKey: \'' . $dbKey . '\'',
				[
					'dbName' => $dbName,
					'namespace' => $namespace,
					'dbKey' => $dbKey,
				]
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
		try {
			$success = $this->store->deletePage( $dbName, $linkTarget );
		} catch ( DBReadOnlyError $e ) {
			return false;
		}

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
