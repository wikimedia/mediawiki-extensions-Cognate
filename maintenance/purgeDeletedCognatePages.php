<?php

namespace Cognate;

use Maintenance;
use MediaWiki\MediaWikiServices;
use RuntimeException;
use Wikimedia\Rdbms\ConnectionManager;
use Wikimedia\Rdbms\IDatabase;

if ( getenv( 'MW_INSTALL_PATH' ) !== false ) {
	require_once getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php';
} else {
	require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

/**
 * Maintenance script for removing entries from the cognate_pages table that do not currently exist
 * on the wiki. For example, due to pages being deleted while Cognate has been in read-only mode.
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class PurgeDeletedCognatePages extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Purge deleted pages from the cognate_pages table' );
		$this->addOption( 'dry-run', 'Do not perform writes' );
		$this->setBatchSize( 100 );
		$this->requireExtension( 'Cognate' );
	}

	public function execute() {
		if ( $this->mBatchSize <= 1 ) {
			throw new RuntimeException( 'batch-size must be set to a value of 2 or more.' );
		}

		$services = MediaWikiServices::getInstance();

		/** @var ConnectionManager $connectionManager */
		$connectionManager = $services->getService( 'CognateConnectionManager' );
		$dbrCognate = $connectionManager->getReadConnectionRef();

		$dbName = $services->getMainConfig()->get( 'DBname' );
		$stringHasher = new StringHasher();
		$siteKey = $stringHasher->hash( $dbName );

		$this->output( "Started processing.\n" );
		if ( $this->hasOption( 'dry-run' ) ) {
			$this->output( "In DRY RUN mode.\n" );
		}

		$start = $dbrCognate->selectField(
			'cognate_pages',
			'MIN(cgpa_title)',
			[
				'cgpa_site' => $siteKey,
			],
			__METHOD__
		);
		if ( !$start ) {
			$this->output( "Nothing to do.\n" );
			return true;
		}

		$loadBalancerFactory = $services->getDBLoadBalancerFactory();
		$dbwCognate = $connectionManager->getWriteConnectionRef();
		$dbr = $this->getDB( DB_REPLICA );

		while ( $start ) {
			$start = $this->executeMainLoop( $dbr, $dbrCognate, $dbwCognate, $siteKey, $start );
			$loadBalancerFactory->waitForReplication();
		}

		$this->output( "Done.\n" );
		return true;
	}

	/**
	 * @param IDatabase $dbr
	 * @param IDatabase $dbrCognate
	 * @param IDatabase $dbwCognate
	 * @param int $siteKey
	 * @param string $start
	 *
	 * @return bool|string cgpa_title to continue from or false if no more rows to process
	 */
	private function executeMainLoop(
		IDatabase $dbr,
		IDatabase $dbrCognate,
		IDatabase $dbwCognate,
		$siteKey,
		$start
	) {
		// Select a batch of pages that are in the cognate page table
		$cognateRows = $dbrCognate->select(
			[
				CognateStore::TITLES_TABLE_NAME,
				CognateStore::PAGES_TABLE_NAME,
			],
			[ 'cgpa_namespace', 'cgpa_title', 'cgti_raw' ],
			[
				'cgpa_site' => $siteKey,
				'cgpa_title >= ' . $start,
				'cgpa_title = cgti_raw_key',
			],
			__METHOD__,
			[
				'LIMIT' => $this->mBatchSize,
				'ORDER BY' => 'cgpa_title ASC'
			]
		);

		// Get an array to select with
		$cognateData = [];
		foreach ( $cognateRows as $row ) {
			$namespaceId = $row->cgpa_namespace;
			$rawTitleText = $row->cgti_raw;
			$rawTitleKey = $row->cgpa_title;
			if ( !array_key_exists( $namespaceId, $cognateData ) ) {
				$cognateData[$namespaceId] = [];
			}
			$cognateData[$namespaceId][$rawTitleText] = $rawTitleKey;
			$start = $rawTitleKey;
		}

		// Get an array to delete with
		$cognateDeletionData = [];
		$rowsDeleting = 0;
		foreach ( $cognateData as $namespaceId => $titles ) {
			if ( !array_key_exists( $namespaceId, $cognateDeletionData ) ) {
				$cognateDeletionData[$namespaceId] = [];
			}
			foreach ( $titles as $rawTitleKey ) {
				$cognateDeletionData[$namespaceId][$rawTitleKey] = null;
				$rowsDeleting++;
			}
		}

		// Delete any remaining titles from the cognate pages table
		if ( !$this->hasOption( 'dry-run' ) && $rowsDeleting > 0 ) {
			$dbwCognate->delete(
				CognateStore::PAGES_TABLE_NAME,
				$dbrCognate->makeList(
					[
						'cgpa_site' => $siteKey,
						$dbrCognate->makeWhereFrom2d(
							$cognateDeletionData,
							'cgpa_namespace',
							'cgpa_title'
						)
					],
					IDatabase::LIST_AND
				),
				__METHOD__
			);
		}

		$this->output(
			$cognateRows->numRows() . " rows processed, " .
			$rowsDeleting . " rows deleted\n"
		);

		if ( $cognateRows->numRows() <= 1 ) {
			return false;
		}
		return $start;
	}

}

$maintClass = PurgeDeletedCognatePages::class;
require_once RUN_MAINTENANCE_IF_MAIN;
