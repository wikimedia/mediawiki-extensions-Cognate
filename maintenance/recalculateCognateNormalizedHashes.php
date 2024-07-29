<?php

namespace Cognate;

use Maintenance;
use Wikimedia\Rdbms\Database;
use Wikimedia\Rdbms\DBUnexpectedError;
use Wikimedia\Rdbms\SelectQueryBuilder;

if ( getenv( 'MW_INSTALL_PATH' ) !== false ) {
	require_once getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php';
} else {
	require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

/**
 * Maintenance script for recalculating the normalized Cognate hashes
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class RecalculateCognateNormalizedHashes extends Maintenance {

	/**
	 * @var Database
	 */
	private $dbr;

	/**
	 * @var Database
	 */
	private $dbw;

	/**
	 * @var StringHasher
	 */
	private $stringHasher;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Recalculate the normalized Cognate hashes' );
		$this->addOption( 'dry-run', 'Perform a dry run' );
		$this->setBatchSize( 100 );
		$this->requireExtension( 'Cognate' );
	}

	private function setupServices() {
		$services = $this->getServiceContainer();
		$connectionProvider = $services->getConnectionProvider();
		$this->dbr = $connectionProvider->getReplicaDatabase( CognateServices::VIRTUAL_DOMAIN );
		$this->dbw = $connectionProvider->getPrimaryDatabase( CognateServices::VIRTUAL_DOMAIN );
		$this->stringHasher = new StringHasher();
		$this->stringNormalizer = new StringNormalizer();
	}

	public function execute() {
		$this->output( "Started processing...\n" );
		if ( $this->hasOption( 'dry-run' ) ) {
			$this->output( "In DRY RUN mode.\n" );
		}
		$this->setupServices();
		$start = $this->getLowestRawKey();

		if ( !$start ) {
			$this->output( "Nothing to do.\n" );
			return true;
		}

		$services = $this->getServiceContainer();
		$loadBalancerFactory = $services->getDBLoadBalancerFactory();
		$totalUpdates = 0;
		$batchStart = (int)$start;

		while ( $batchStart ) {
			$this->output( "Getting batch starting from $batchStart\n" );
			$rows = $this->dbr->newSelectQueryBuilder()
				->select( [ 'cgti_raw', 'cgti_raw_key', 'cgti_normalized_key' ] )
				->from( CognateStore::TITLES_TABLE_NAME )
				->where( $this->dbr->expr( 'cgti_raw_key', '>', $batchStart ) )
				->orderBy( 'cgti_raw_key', SelectQueryBuilder::SORT_ASC )
				->limit( $this->mBatchSize )
				->caller( __METHOD__ )
				->fetchResultSet();

			$this->output( "Calculating new hashes..\n" );
			$batchStart = false;
			$rowsToUpdate = [];
			foreach ( $rows as $row ) {
				$batchStart = $row->cgti_raw_key;

				$newNormalizedHash = $this->normalizeAndHash( $row->cgti_raw );
				if ( $newNormalizedHash != $row->cgti_normalized_key ) {
					$newRow = (array)$row;
					$newRow['cgti_normalized_key'] = $newNormalizedHash;
					$rowsToUpdate[] = $newRow;
				}
			}

			$numberOfUpdates = count( $rowsToUpdate );
			$totalUpdates += $numberOfUpdates;

			if ( $numberOfUpdates > 0 && !$this->hasOption( 'dry-run' ) ) {
				$this->output( "Performing $numberOfUpdates updates\n" );
				// @phan-suppress-next-line SecurityCheck-SQLInjection
				$this->dbw->newInsertQueryBuilder()
					->insertInto( CognateStore::TITLES_TABLE_NAME )
					->rows( $rowsToUpdate )
					->onDuplicateKeyUpdate()
					->uniqueIndexFields( 'cgti_raw_key' )
					->set( [
						'cgti_normalized_key=' . $this->dbw->buildExcludedValue( 'cgti_normalized_key' ),
					] )
					->caller( __METHOD__ )
					->execute();
			}

			$this->output(
				$rows->numRows() . " rows processed, " .
				$numberOfUpdates . " rows upserted\n"
			);

			$loadBalancerFactory->waitForReplication();
		}

		$this->output( "$totalUpdates hashes recalculated\n" );
		$this->output( "Done!\n" );

		return true;
	}

	/**
	 * Select 1 less than the minimum so that > can be used in selects in this script.
	 *
	 * @return int|false
	 * @throws DBUnexpectedError
	 */
	private function getLowestRawKey() {
		return $this->dbr->newSelectQueryBuilder()
			->select( 'MIN(cgti_raw_key)-1' )
			->from( CognateStore::TITLES_TABLE_NAME )
			->caller( __METHOD__ )
			->fetchField();
	}

	/**
	 * @param string $string
	 *
	 * @return int
	 */
	private function normalizeAndHash( $string ) {
		return $this->stringHasher->hash(
			$this->stringNormalizer->normalize( $string )
		);
	}

}

$maintClass = RecalculateCognateNormalizedHashes::class;
require_once RUN_MAINTENANCE_IF_MAIN;
