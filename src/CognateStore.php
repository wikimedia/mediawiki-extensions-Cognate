<?php

namespace Cognate;

use MediaWiki\Linker\LinkTarget;
use TitleValue;
use Wikimedia\Rdbms\ConnectionManager;

/**
 * @license GNU GPL v2+
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

	const PAGES_TABLE_NAME = 'cognate_pages';
	const SITES_TABLE_NAME = 'cognate_sites';
	const TITLES_TABLE_NAME = 'cognate_titles';

	/**
	 * @param ConnectionManager $connectionManager
	 * @param StringNormalizer $stringNormalizer
	 * @param StringHasher $stringHasher
	 */
	public function __construct(
		ConnectionManager $connectionManager,
		StringNormalizer $stringNormalizer,
		StringHasher $stringHasher
	) {
		$this->connectionManager = $connectionManager;
		$this->stringNormalizer = $stringNormalizer;
		$this->stringHasher = $stringHasher;
	}

	/**
	 * Adds a page to the database. As well as adding the data to the pages table this also
	 * includes adding the data to the titles table where needed.
	 *
	 * @param string $dbName The dbName for the site
	 * @param LinkTarget $linkTarget
	 *
	 * @return bool true on success, false if there was a key conflict
	 */
	public function insertPage( $dbName, LinkTarget $linkTarget ) {
		$dbw = $this->connectionManager->getWriteConnectionRef();
		$dbr = $this->connectionManager->getReadConnectionRef();

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

		if ( $row && $row->cgti_raw !== $linkTarget->getDBkey() ) {
			return false;
		}

		if ( !$row ) {
			$dbw->insert(
				self::TITLES_TABLE_NAME,
				$titlesToInsert,
				__METHOD__,
				[ 'IGNORE' ]
			);
		}

		$dbw->insert(
			self::PAGES_TABLE_NAME,
			$pagesToInsert,
			__METHOD__,
			[ 'IGNORE' ]
		);

		return true;
	}

	/**
	 * Note: this method will not remove any relevant entries from the titles table
	 *
	 * @param string $dbName The dbName for the site
	 * @param LinkTarget $linkTarget
	 *
	 * @return bool
	 */
	public function deletePage( $dbName, LinkTarget $linkTarget ) {
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
		$dbr = $this->connectionManager->getReadConnectionRef();
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

		$linkDetails = [];
		while ( $row = $result->fetchRow() ) {
			$linkDetails[] = [
				'interwiki' => $row['cgsi_interwiki'],
				'namespaceID' => intval( $row['cgpa_namespace'] ),
				'title' => $row['cgti_raw'],
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
		$dbr = $this->connectionManager->getWriteConnectionRef();
		$result = $dbr->select(
			[
				self::TITLES_TABLE_NAME,
				self::PAGES_TABLE_NAME,
				self::SITES_TABLE_NAME,
			],
			[ 'cgsi_dbname' ],
			[
				'cgti_normalized_key' => $this->getNormalizedStringHash( $linkTarget->getDBkey() ),
				'cgpa_namespace' => $linkTarget->getNamespace(),
				'cgti_raw_key = cgpa_title',
				'cgpa_site = cgsi_key',
			],
			__METHOD__
		);

		$sites = [];
		while ( $row = $result->fetchRow() ) {
			$sites[] = $row[ 'cgsi_dbname' ];
		}

		return $sites;
	}

	/**
	 * Adds pages to the database. As well as adding the data to the pages table this also
	 * includes adding the data to the titles table where needed.
	 *
	 * @note Errors during insertion are totally ignored by this method. If there were duplicate
	 * keys in the DB then you will not find out about them here.
	 *
	 * @param array $pageDetailsArray where each element contains the keys 'site', 'namespace', 'title'
	 *        e.g. [ [ 'site' => 'enwiktionary', 'namespace' => 0, 'title' => 'Berlin' ] ]
	 */
	public function insertPages( array $pageDetailsArray ) {
		$dbw = $this->connectionManager->getWriteConnectionRef();

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
	 * @param array[] $pagesToInsert
	 * @param array[] $titlesToInsert
	 *
	 * @return array 0 => $pagesToInsert, 1 => $titleToInsert
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
	 */
	public function insertSites( array $sites ) {
		$dbw = $this->connectionManager->getWriteConnectionRef();

		$toInsert = [];
		foreach ( $sites as $dbname => $interwikiPrefix ) {
			$toInsert[] = [
				'cgsi_key' => $this->getStringHash( $dbname ),
				'cgsi_dbname' => $dbname,
				'cgsi_interwiki' => $interwikiPrefix,
			];
		}

		$dbw->insert(
			'cognate_sites',
			$toInsert,
			__METHOD__,
			[ 'IGNORE' ]
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

}
