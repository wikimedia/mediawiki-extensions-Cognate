<?php

namespace Cognate;

use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use MediaWiki\Linker\LinkTarget;
use NullStatsdDataFactory;
use Psr\Log\LoggerInterface;
use StatsdAwareInterface;
use Title;
use TitleFormatter;
use Wikimedia\Rdbms\DBReadOnlyError;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class CognateRepo implements StatsdAwareInterface {

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
	 * @var StatsdDataFactoryInterface
	 */
	private $stats;

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
		$this->stats = new NullStatsdDataFactory();
	}

	public function setStatsdDataFactory( StatsdDataFactoryInterface $statsFactory ) {
		$this->stats = $statsFactory;
	}

	/**
	 * @param string $dbName The dbName for the site
	 * @param LinkTarget $linkTarget
	 *
	 * @return bool
	 */
	public function savePage( $dbName, LinkTarget $linkTarget ) {
		$this->stats->increment( 'Cognate.Repo.savePage' );

		try {
			$start = microtime( true );
			$success = $this->store->insertPage( $dbName, $linkTarget );
			$this->stats->timing( 'Cognate.Repo.savePage.time', 1000 * ( microtime( true ) - $start ) );
			$this->stats->gauge( 'Cognate.Repo.savePage.inserts', $success );
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

		return (bool)$success;
	}

	/**
	 * @param string $dbName The dbName for the site
	 * @param LinkTarget $linkTarget
	 *
	 * @return bool
	 */
	public function deletePage( $dbName, LinkTarget $linkTarget ) {
		$this->stats->increment( 'Cognate.Repo.deletePage' );

		try {
			$start = microtime( true );
			$success = $this->store->deletePage( $dbName, $linkTarget );
			$this->stats->timing( 'Cognate.Repo.deletePage.time', 1000 * ( microtime( true ) - $start ) );
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
		$this->stats->increment( 'Cognate.Repo.getLinksForPage' );

		$start = microtime( true );
		$linkDetails = $this->store->selectLinkDetailsForPage( $dbName, $linkTarget );
		$time = 1000 * ( microtime( true ) - $start );
		$this->stats->timing( 'Cognate.Repo.getLinksForPage.time', $time );

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
	 * @param LinkTarget $linkTarget
	 *
	 * @return string[] array of dbnames
	 */
	public function selectSitesForPage( LinkTarget $linkTarget ) {
		$this->stats->increment( 'Cognate.Repo.selectSitesForPage' );

		$start = microtime( true );
		$sites = $this->store->selectSitesForPage( $linkTarget );
		$time = 1000 * ( microtime( true ) - $start );
		$this->stats->timing( 'Cognate.Repo.selectSitesForPage.time', $time );

		return $sites;
	}

}
