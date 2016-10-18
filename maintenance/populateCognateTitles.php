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
 * Maintenance script for populating the cognate titles table.
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class PopulateCognateTitles extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Populate the cognate titles table' );
		$this->addOption( 'start', "The page ID to start from.", false, true );
		$this->setBatchSize( 100 );
	}

	public function execute() {
		$services = MediaWikiServices::getInstance();
		$languageCode = $services->getMainConfig()->get( 'LanguageCode' );
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

		while ( $blockStart <= $end ) {

			$rows = $dbr->select(
				'page',
				[ 'page_namespace', 'page_title' ],
				[
					"page_id BETWEEN $blockStart AND $blockEnd",
					'page_namespace IN (' . $dbr->makeList( $namespaces ) . ')',
				],
				__METHOD__
			);

			$titleDetails = [];
			foreach ( $rows as $key => $row ) {
				$titleDetails[] = [
					'site' => $languageCode,
					'namespace' => $row->page_namespace,
					'title' => $row->page_title,
				];
			}

			$numberOfRows = count( $titleDetails );
			$success = $store->addTitles( $titleDetails );

			if ( $success ) {
				$this->output( "Inserted $numberOfRows rows.\n" );
			} else {
				$this->output( "Failed to insert $numberOfRows rows.\n" );
			}

			$blockStart += $this->mBatchSize;
			$blockEnd += $this->mBatchSize;
			$this->output( "Pass finished.\n" );
		}

		$this->output( "Done.\n" );
		return true;
	}

}

$maintClass = PopulateCognateTitles::class;
require_once RUN_MAINTENANCE_IF_MAIN;
