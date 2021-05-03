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
 * The script will add an entry in the cognate_pages table for every page that
 * exists in the mediawiki page table as well as adding the needed entries to
 * the cognate_titles table.
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class PopulateCognatePages extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Populate the Cognate page and title tables' );
		$this->addOption( 'start', 'The page ID to start from.', false, true );
		$this->addOption(
			'clear-first',
			'Clear entries in the table for the current wiki before population starts.'
		);
		$this->setBatchSize( 100 );
		$this->requireExtension( 'Cognate' );
	}

	public function execute() {
		$services = MediaWikiServices::getInstance();
		$dbName = $services->getMainConfig()->get( 'DBname' );
		$namespaces = $services->getMainConfig()->get( 'CognateNamespaces' );
		$namespaces = array_filter(
			array_map( 'intval', $namespaces ),
			static function ( $namespace ) {
				return $namespace >= NS_MAIN && $namespace <= NS_CATEGORY_TALK;
			}
		);

		/** @var CognateStore $store */
		$store = $services->getService( 'CognateStore' );

		if ( $this->hasOption( 'clear-first' ) ) {
			$this->output( "Clearing cognate_pages table of entries for $dbName.\n" );
			$store->deletePagesForSite( $dbName );
		}

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
		$blockStart = (int)$start;
		$blockEnd = $blockStart + $this->mBatchSize - 1;
		$loadBalancerFactory = $services->getDBLoadBalancerFactory();

		while ( $blockStart <= $end ) {
			$rows = $dbr->select(
				'page',
				[ 'page_namespace', 'page_title' ],
				[
					"page_id BETWEEN $blockStart AND $blockEnd",
					'page_namespace' => $namespaces,
					'page_is_redirect = 0'
				],
				__METHOD__
			);

			$titleDetails = [];
			foreach ( $rows as $row ) {
				$titleDetails[] = [
					'site' => $dbName,
					'namespace' => (int)$row->page_namespace,
					'title' => $row->page_title,
				];
			}

			$numberOfRows = count( $titleDetails );
			$store->insertPages( $titleDetails );

			$this->output( "$numberOfRows rows processed.\n" );

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
