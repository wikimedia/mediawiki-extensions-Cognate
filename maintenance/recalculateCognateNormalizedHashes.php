<?php

namespace Cognate;

use Maintenance;
use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\ConnectionManager;
use Wikimedia\Rdbms\Database;
use Wikimedia\Rdbms\DBUnexpectedError;

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
		$services = MediaWikiServices::getInstance();
		/** @var ConnectionManager $connectionManager */
		$connectionManager = $services->getService( 'CognateConnectionManager' );
		$this->dbr = $connectionManager->getReadConnection();
		$this->dbw = $connectionManager->getWriteConnection();
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

		$services = MediaWikiServices::getInstance();
		$loadBalancerFactory = $services->getDBLoadBalancerFactory();
		$totalUpdates = 0;
		$batchStart = (int)$start;

		while ( $batchStart ) {
			$this->output( "Getting batch starting from $batchStart\n" );
			$rows = $this->dbr->select(
				CognateStore::TITLES_TABLE_NAME,
				[ 'cgti_raw', 'cgti_raw_key', 'cgti_normalized_key' ],
				[ 'cgti_raw_key > ' . $batchStart ],
				__METHOD__,
				[
					'LIMIT' => $this->mBatchSize,
					'ORDER BY' => 'cgti_raw_key ASC',
				]
			);

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

			if ( !$this->hasOption( 'dry-run' ) ) {
				$this->output( "Performing $numberOfUpdates updates\n" );
				$this->dbw->upsert(
					CognateStore::TITLES_TABLE_NAME,
					$rowsToUpdate,
					[ 'cgti_raw_key' ],
					[
						'cgti_normalized_key=VALUES(cgti_normalized_key)',
					],
					__METHOD__
				);
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
		return $this->dbr->selectField(
			CognateStore::TITLES_TABLE_NAME,
			'MIN(cgti_raw_key)-1',
			[],
			__METHOD__
		);
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
