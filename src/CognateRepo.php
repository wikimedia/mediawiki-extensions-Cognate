<?php

namespace Cognate;

use MediaWiki\Linker\LinkTarget;
use MediaWiki\Title\TitleFormatter;
use Psr\Log\LoggerInterface;
use Wikimedia\Rdbms\DBReadOnlyError;
use Wikimedia\Stats\StatsFactory;

/**
 * @license GPL-2.0-or-later
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

	/**
	 * @var StatsFactory
	 */
	private $statsFactory;

	/**
	 * @param CognateStore $store
	 * @param CacheInvalidator $cacheInvalidator
	 * @param TitleFormatter $titleFormatter
	 * @param StatsFactory $statsFactory
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		CognateStore $store,
		CacheInvalidator $cacheInvalidator,
		TitleFormatter $titleFormatter,
		StatsFactory $statsFactory,
		LoggerInterface $logger
	) {
		$this->store = $store;
		$this->cacheInvalidator = $cacheInvalidator;
		$this->titleFormatter = $titleFormatter;
		$this->statsFactory = $statsFactory->withComponent( 'Cognate' );
		$this->logger = $logger;
	}

	/**
	 * @param string $dbName The dbName for the site
	 * @param LinkTarget $linkTarget
	 *
	 * @return bool
	 */
	public function savePage( $dbName, LinkTarget $linkTarget ) {
		$start = microtime( true );
		try {
			$success = $this->store->insertPage( $dbName, $linkTarget );
		} catch ( DBReadOnlyError $e ) {
			return false;
		} finally {
			$this->statsFactory->getTiming( 'repo_writes_seconds' )
				->setLabel( 'action', 'savePage' )
				->copyToStatsdAt( 'Cognate.Repo.savePage.time' )
				->observe( 1000 * ( microtime( true ) - $start ) );
		}

		if ( $success ) {
			$this->invalidateAllSitesForPage( $dbName, $linkTarget );
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

		return (bool)$success;
	}

	/**
	 * @param string $dbName The dbName for the site
	 * @param LinkTarget $linkTarget
	 *
	 * @return bool
	 */
	public function deletePage( $dbName, LinkTarget $linkTarget ) {
		$start = microtime( true );
		try {
			$success = $this->store->deletePage( $dbName, $linkTarget );
		} catch ( DBReadOnlyError $e ) {
			return false;
		} finally {
			$this->statsFactory->getTiming( 'repo_writes_seconds' )
				->setLabel( 'action', 'deletePage' )
				->copyToStatsdAt( 'Cognate.Repo.deletePage.time' )
				->observe( 1000 * ( microtime( true ) - $start ) );
		}

		if ( $success ) {
			$this->invalidateAllSitesForPage( $dbName, $linkTarget );
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
		$start = microtime( true );
		$linkDetails = $this->store->selectLinkDetailsForPage( $dbName, $linkTarget );
		$this->statsFactory->getTiming( 'repo_reads_seconds' )
			->setLabel( 'action', 'getLinksForPage' )
			->copyToStatsdAt( 'Cognate.Repo.getLinksForPage.time' )
			->observe( 1000 * ( microtime( true ) - $start ) );

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

	/**
	 * @param string $dbName
	 * @param LinkTarget $linkTarget
	 */
	private function invalidateAllSitesForPage( $dbName, LinkTarget $linkTarget ) {
		$start = microtime( true );
		$sites = $this->store->selectSitesForPage( $linkTarget );
		$this->statsFactory->getTiming( 'repo_reads_seconds' )
			->setLabel( 'action', 'selectSitesForPage' )
			->copyToStatsdAt( 'Cognate.Repo.selectSitesForPage.time' )
			->observe( 1000 * ( microtime( true ) - $start ) );

		// In the case of a delete causing cache invalidations we need to add the local site
		// back to the list as it has already been removed from the database.
		$sites[] = $dbName;
		$sites = array_values( array_unique( $sites ) );

		$this->cacheInvalidator->invalidate( $sites, $linkTarget );
	}

}
