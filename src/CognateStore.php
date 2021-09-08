<?php

namespace Cognate;

use MediaWiki\Linker\LinkTarget;
use RuntimeException;
use TitleValue;
use Wikimedia\Rdbms\ConnectionManager;
use Wikimedia\Rdbms\DBReadOnlyError;

/**
 * Database access for the Cognate tables.
 *
 * This class should generally not be accessed directly but instead via CognateRepo which contains
 * extra business logic such as logging, stats and cache purges.
 *
 * @license GPL-2.0-or-later
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 * @author Addshore
 */
class CognateStore {

	/**
	 * @var ConnectionManager
	 */
	private $connectionManager;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	/**
	 * @var StringHasher
	 */
	private $stringHasher;

	/**
	 * @var bool
	 */
	private $readOnly;

	public const PAGES_TABLE_NAME = 'cognate_pages';
	public const SITES_TABLE_NAME = 'cognate_sites';
	public const TITLES_TABLE_NAME = 'cognate_titles';

	/**
	 * @param ConnectionManager $connectionManager
	 * @param StringNormalizer $stringNormalizer
	 * @param StringHasher $stringHasher
	 * @param bool $readOnly Is Cognate in readonly mode?
	 */
	public function __construct(
		ConnectionManager $connectionManager,
		StringNormalizer $stringNormalizer,
		StringHasher $stringHasher,
		$readOnly
	) {
		$this->connectionManager = $connectionManager;
		$this->stringNormalizer = $stringNormalizer;
		$this->stringHasher = $stringHasher;
		$this->readOnly = $readOnly;
	}

	/**
	 * Adds a page to the database. As well as adding the data to the pages table this also
	 * includes adding the data to the titles table where needed.
	 *
	 * @param string $dbName The dbName for the site
	 * @param LinkTarget $linkTarget
	 *
	 * @return bool|int number of inserts run on success, false if there was a key conflict
	 * @throws DBReadOnlyError
	 */
	public function insertPage( $dbName, LinkTarget $linkTarget ) {
		if ( $this->readOnly ) {
			$this->throwReadOnlyException();
		}

		$dbr = $this->connectionManager->getReadConnection();

		list( $pagesToInsert, $titlesToInsert ) = $this->buildRows(
			$linkTarget,
			$dbName
		);

		$row = $dbr->selectRow(
			self::TITLES_TABLE_NAME,
			[ 'cgti_raw' ],
			[ 'cgti_raw_key' => $this->getStringHash( $linkTarget->getDBkey() ) ],
			__METHOD__
		);
		$this->connectionManager->releaseConnection( $dbr );

		if ( $row && $row->cgti_raw !== $linkTarget->getDBkey() ) {
			return false;
		}

		$insertQueryCounter = 0;

		$dbw = $this->connectionManager->getWriteConnectionRef();
		if ( !$row ) {
			$dbw->insert(
				self::TITLES_TABLE_NAME,
				$titlesToInsert,
				__METHOD__,
				[ 'IGNORE' ]
			);
			$insertQueryCounter++;
		}

		$dbw->insert(
			self::PAGES_TABLE_NAME,
			$pagesToInsert,
			__METHOD__,
			[ 'IGNORE' ]
		);
		$insertQueryCounter++;

		return $insertQueryCounter;
	}

	/**
	 * Note: this method will not remove any relevant entries from the titles table
	 *
	 * @param string $dbName The dbName for the site
	 * @param LinkTarget $linkTarget
	 *
	 * @return bool
	 * @throws DBReadOnlyError
	 */
	public function deletePage( $dbName, LinkTarget $linkTarget ) {
		if ( $this->readOnly ) {
			$this->throwReadOnlyException();
		}

		$pageData = [
			'cgpa_site' => $this->getStringHash( $dbName ),
			'cgpa_title' => $this->getStringHash( $linkTarget->getDBkey() ),
			'cgpa_namespace' => $linkTarget->getNamespace(),
		];
		$dbw = $this->connectionManager->getWriteConnectionRef();
		$result = $dbw->delete( self::PAGES_TABLE_NAME, $pageData, __METHOD__ );

		return (bool)$result;
	}

	/**
	 * @param string $dbName The dbName of the site being linked from
	 * @param LinkTarget $linkTarget of the page the links should be retrieved for
	 *
	 * @return array[] details used to create interwiki links. Each array will look like:
	 *                 [ 'interwiki' => 'en', 'namespaceID' => 0, 'title' => 'Berlin' ]
	 */
	public function selectLinkDetailsForPage( $dbName, LinkTarget $linkTarget ) {
		$dbr = $this->connectionManager->getReadConnection();
		$result = $dbr->select(
			[
				self::TITLES_TABLE_NAME,
				self::PAGES_TABLE_NAME,
				self::SITES_TABLE_NAME,
			],
			[
				'cgsi_interwiki',
				'cgpa_namespace',
				'cgti_raw',
			],
			[
				'cgsi_dbname != ' . $dbr->addQuotes( $dbName ),
				'cgti_normalized_key' => $this->getNormalizedStringHash( $linkTarget->getDBkey() ),
				'cgpa_namespace' => $linkTarget->getNamespace(),
				'cgti_raw_key = cgpa_title',
				'cgpa_site = cgsi_key',
			],
			__METHOD__
		);
		$this->connectionManager->releaseConnection( $dbr );

		$linkDetails = [];
		foreach ( $result as $row ) {
			$linkDetails[] = [
				'interwiki' => $row->cgsi_interwiki,
				'namespaceID' => intval( $row->cgpa_namespace ),
				'title' => $row->cgti_raw,
			];
		}

		return $linkDetails;
	}

	/**
	 * @param LinkTarget $linkTarget
	 *
	 * @return string[] array of dbnames
	 */
	public function selectSitesForPage( LinkTarget $linkTarget ) {
		$dbr = $this->connectionManager->getReadConnection();
		$sites = $dbr->selectFieldValues(
			[
				self::TITLES_TABLE_NAME,
				self::PAGES_TABLE_NAME,
				self::SITES_TABLE_NAME,
			],
			'cgsi_dbname',
			[
				'cgti_normalized_key' => $this->getNormalizedStringHash( $linkTarget->getDBkey() ),
				'cgpa_namespace' => $linkTarget->getNamespace(),
				'cgti_raw_key = cgpa_title',
				'cgpa_site = cgsi_key',
			],
			__METHOD__
		);
		$this->connectionManager->releaseConnection( $dbr );
		return $sites;
	}

	/**
	 * Adds pages to the database. As well as adding the data to the pages table this also
	 * includes adding the data to the titles table where needed.
	 *
	 * @note Errors during insertion are totally ignored by this method. If there were duplicate
	 * keys in the DB then you will not find out about them here.
	 *
	 * @param array[] $pageDetailsArray where each element contains the keys 'site', 'namespace',
	 * and 'title', e.g. [ [ 'site' => 'enwiktionary', 'namespace' => 0, 'title' => 'Berlin' ] ].
	 *
	 * @throws RuntimeException
	 */
	public function insertPages( array $pageDetailsArray ) {
		if ( !defined( 'RUN_MAINTENANCE_IF_MAIN' ) && !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new RuntimeException( __METHOD__ . ' can only be used for maintenance or tests.' );
		}

		$pagesToInsert = [];
		$titlesToInsert = [];
		foreach ( $pageDetailsArray as $pageDetails ) {
			$this->buildRows(
				new TitleValue( $pageDetails['namespace'], $pageDetails['title'] ),
				$pageDetails['site'],
				$pagesToInsert,
				$titlesToInsert
			);
		}

		$dbw = $this->connectionManager->getWriteConnectionRef();
		$dbw->insert(
			self::TITLES_TABLE_NAME,
			$titlesToInsert,
			__METHOD__,
			[ 'IGNORE' ]
		);

		$dbw->insert(
			self::PAGES_TABLE_NAME,
			$pagesToInsert,
			__METHOD__,
			[ 'IGNORE' ]
		);
	}

	/**
	 * @param LinkTarget $linkTarget
	 * @param string $site
	 * @param array[] &$pagesToInsert
	 * @param array[] &$titlesToInsert
	 *
	 * @return array[] 0 => $pagesToInsert, 1 => $titleToInsert
	 */
	private function buildRows(
		LinkTarget $linkTarget,
		$site,
		array &$pagesToInsert = [],
		array &$titlesToInsert = []
	) {
		$pagesToInsert[] = [
			'cgpa_site' => $this->getStringHash( $site ),
			'cgpa_namespace' => $linkTarget->getNamespace(),
			'cgpa_title' => $this->getStringHash( $linkTarget->getDBkey() ),
		];
		$titlesToInsert[] = [
			'cgti_raw' => $linkTarget->getDBkey(),
			'cgti_raw_key' => $this->getStringHash( $linkTarget->getDBkey() ),
			'cgti_normalized_key' => $this->getNormalizedStringHash( $linkTarget->getDBkey() ),
		];

		return [ $pagesToInsert, $titlesToInsert ];
	}

	/**
	 * @param string[] $sites keys of site dbname => values of interwiki prefix
	 *        e.g. 'enwiktionary' => 'en'
	 *
	 * @throws RuntimeException
	 */
	public function insertSites( array $sites ) {
		if ( !defined( 'RUN_MAINTENANCE_IF_MAIN' ) && !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new RuntimeException( __METHOD__ . ' can only be used for maintenance or tests.' );
		}

		$toInsert = [];
		foreach ( $sites as $dbname => $interwikiPrefix ) {
			$toInsert[] = [
				'cgsi_key' => $this->getStringHash( $dbname ),
				'cgsi_dbname' => $dbname,
				'cgsi_interwiki' => $interwikiPrefix,
			];
		}

		$dbw = $this->connectionManager->getWriteConnectionRef();
		$dbw->insert(
			'cognate_sites',
			$toInsert,
			__METHOD__,
			[ 'IGNORE' ]
		);
	}

	/**
	 * Delete all entries from the cognate_pages table for the given site.
	 *
	 * @param string $dbName The dbname of the site to delete pages for.
	 *
	 * @throws RuntimeException if not run in a maintenance or test scope
	 */
	public function deletePagesForSite( $dbName ) {
		if ( !defined( 'RUN_MAINTENANCE_IF_MAIN' ) && !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new RuntimeException( __METHOD__ . ' can only be used for maintenance or tests.' );
		}

		$dbw = $this->connectionManager->getWriteConnectionRef();
		$dbw->delete(
			'cognate_pages',
			[
				'cgpa_site' => $this->getStringHash( $dbName ),
			],
			__METHOD__
		);
	}

	/**
	 * @param string $string
	 *
	 * @return int
	 */
	private function getStringHash( $string ) {
		return $this->stringHasher->hash( $string );
	}

	/**
	 * @param string $string
	 *
	 * @return int
	 */
	private function getNormalizedStringHash( $string ) {
		return $this->stringHasher->hash(
			$this->stringNormalizer->normalize( $string )
		);
	}

	/**
	 * @throws DBReadOnlyError
	 * @return never
	 */
	private function throwReadOnlyException() {
		throw new DBReadOnlyError( null, 'Cognate is in Read Only mode' );
	}

}
