<?php

namespace Cognate;

use Maintenance;
use RuntimeException;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IReadableDatabase;
use Wikimedia\Rdbms\SelectQueryBuilder;

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

		$services = $this->getServiceContainer();

		$connectionProvider = $services->getConnectionProvider();
		$dbrCognate = $connectionProvider->getReplicaDatabase( CognateServices::VIRTUAL_DOMAIN );

		$dbName = $services->getMainConfig()->get( 'DBname' );
		$stringHasher = new StringHasher();
		$siteKey = $stringHasher->hash( $dbName );

		$this->output( "Started processing.\n" );
		if ( $this->hasOption( 'dry-run' ) ) {
			$this->output( "In DRY RUN mode.\n" );
		}

		$start = $dbrCognate->newSelectQueryBuilder()
			->select( 'MIN(cgpa_title)' )
			->from( 'cognate_pages' )
			->where( [
				'cgpa_site' => $siteKey,
			] )
			->caller( __METHOD__ )
			->fetchField();
		if ( !$start ) {
			$this->output( "Nothing to do.\n" );
			return true;
		}

		$dbwCognate = $connectionProvider->getPrimaryDatabase( CognateServices::VIRTUAL_DOMAIN );
		$dbr = $this->getDB( DB_REPLICA );

		while ( $start ) {
			$start = $this->executeMainLoop( $dbr, $dbrCognate, $dbwCognate, $siteKey, $start );
			$this->waitForReplication();
		}

		$this->output( "Done.\n" );
		return true;
	}

	/**
	 * @param IReadableDatabase $dbr
	 * @param IReadableDatabase $dbrCognate
	 * @param IDatabase $dbwCognate
	 * @param int $siteKey
	 * @param string $start
	 *
	 * @return bool|string cgpa_title to continue from or false if no more rows to process
	 */
	private function executeMainLoop(
		IReadableDatabase $dbr,
		IReadableDatabase $dbrCognate,
		IDatabase $dbwCognate,
		$siteKey,
		$start
	) {
		// Select a batch of pages that are in the cognate page table
		$cognateRows = $dbrCognate->newSelectQueryBuilder()
			->select( [ 'cgpa_namespace', 'cgpa_title', 'cgti_raw' ] )
			->from( CognateStore::TITLES_TABLE_NAME )
			->join( CognateStore::PAGES_TABLE_NAME, null, 'cgpa_title = cgti_raw_key' )
			->where( [
				'cgpa_site' => $siteKey,
				$dbrCognate->expr( 'cgpa_title', '>=', $start ),
			] )
			->orderBy( 'cgpa_title', SelectQueryBuilder::SORT_ASC )
			->limit( $this->mBatchSize )
			->caller( __METHOD__ )
			->fetchResultSet();

		if ( !$cognateRows->numRows() ) {
			return false;
		}

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

		// Select pages that exist in mediawiki with the given titles
		$pageRows = $dbr->newSelectQueryBuilder()
			->select( [ 'page_namespace', 'page_title' ] )
			->from( 'page' )
			->where( $dbr->makeWhereFrom2d( $cognateData, 'page_namespace', 'page_title' ) )
			->caller( __METHOD__ )
			->fetchResultSet();
		// Remove pages that do exist on wiki from the cognate data
		foreach ( $pageRows as $row ) {
			unset( $cognateData[$row->page_namespace][$row->page_title] );
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
			$dbwCognate->newDeleteQueryBuilder()
				->deleteFrom( CognateStore::PAGES_TABLE_NAME )
				->where( [
					'cgpa_site' => $siteKey,
					$dbrCognate->makeWhereFrom2d(
						$cognateDeletionData,
						'cgpa_namespace',
						'cgpa_title'
					)
				] )
				->caller( __METHOD__ )
				->execute();
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
