<?php

namespace Cognate;

use Maintenance;
use MediaWiki\MediaWikiServices;

if ( getenv( 'MW_INSTALL_PATH' ) !== false ) {
	require_once getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php';
} else {
	require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

/**
 * Maintenance script for populating the cognate page and title tables.
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class PopulateCognatePages extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Populate the Cognate page and title tables' );
		$this->addOption( 'start', 'The page ID to start from.', false, true );
		$this->setBatchSize( 100 );
		$this->requireExtension( 'Cognate' );
	}

	public function execute() {
		$services = MediaWikiServices::getInstance();
		$dbName = $services->getMainConfig()->get( 'DBname' );
		$namespaces = $services->getMainConfig()->get( 'CognateNamespaces' );
		$namespaces = array_filter(
			array_map( 'intval', $namespaces ),
			function( $namespace ) {
				return $namespace >= NS_MAIN && $namespace <= NS_CATEGORY_TALK;
			}
		);

		/** @var CognateStore $store */
		$store = $services->getService( 'CognateStore' );
		$this->output( "Started processing.\n" );
		$dbr = $this->getDB( DB_REPLICA );

		$start = $this->getOption( 'start' );
		if ( $start === null ) {
			$start = $dbr->selectField( 'page', 'MIN(page_id)', '', __METHOD__ );
		}
		if ( !$start ) {
			$this->output( "Nothing to do.\n" );
			return true;
		}

		$end = $dbr->selectField( 'page', 'MAX(page_id)', '', __METHOD__ );
		$blockStart = $start;
		$blockEnd = $blockStart + $this->mBatchSize - 1;
		$loadBalancerFactory = $services->getDBLoadBalancerFactory();

		while ( $blockStart <= $end ) {

			$rows = $dbr->select(
				'page',
				[ 'page_namespace', 'page_title' ],
				[
					"page_id BETWEEN $blockStart AND $blockEnd",
					'page_namespace IN (' . $dbr->makeList( $namespaces ) . ')',
					'page_is_redirect = 0'
				],
				__METHOD__
			);

			$titleDetails = [];
			foreach ( $rows as $key => $row ) {
				$titleDetails[] = [
					'site' => $dbName,
					'namespace' => $row->page_namespace,
					'title' => $row->page_title,
				];
			}

			$numberOfRows = count( $titleDetails );
			$success = $store->insertPages( $titleDetails );

			if ( $success ) {
				$this->output( "Inserted $numberOfRows rows.\n" );
			} else {
				$this->output( "Failed to insert $numberOfRows rows.\n" );
			}

			$blockStart += $this->mBatchSize;
			$blockEnd += $this->mBatchSize;
			$this->output( "Pass finished.\n" );

			$loadBalancerFactory->waitForReplication();
		}

		$this->output( "Done.\n" );
		return true;
	}

}

$maintClass = PopulateCognatePages::class;
require_once RUN_MAINTENANCE_IF_MAIN;
