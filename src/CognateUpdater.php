<?php

namespace Cognate;

use DatabaseUpdater;
use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\IMaintainableDatabase;
use Wikimedia\Rdbms\LoadBalancer;

/**
 * Helper class for CognateHooks::onLoadExtensionSchemaUpdates
 */
class CognateUpdater {
	/** @var DatabaseUpdater */
	private $updater;

	/** @var IMaintainableDatabase */
	private $db;

	public static function update( DatabaseUpdater $updater ) {
		$cognateUpdater = new self( $updater );
		$cognateUpdater->doUpdate();
	}

	private function __construct( DatabaseUpdater $updater ) {
		global $wgCognateDb, $wgCognateCluster;

		$this->updater = $updater;

		// At install time, extension configuration is not loaded T198331
		if ( !isset( $wgCognateDb ) ) {
			$wgCognateDb = false;
		}
		if ( !isset( $wgCognateCluster ) ) {
			$wgCognateCluster = false;
		}

		if ( $wgCognateDb === false && $wgCognateCluster === false ) {
			$this->db = $updater->getDB();
		} else {
			$services = MediaWikiServices::getInstance();
			if ( $wgCognateCluster !== false ) {
				$loadBalancerFactory = $services->getDBLoadBalancerFactory();
				$loadBalancer = $loadBalancerFactory->getExternalLB( $wgCognateCluster );
			} else {
				$loadBalancer = $services->getDBLoadBalancer();
			}
			$this->db = $loadBalancer->getConnection( LoadBalancer::DB_MASTER, [], $wgCognateDb );
		}
	}

	private function doUpdate() {
		$this->addTable( 'cognate_pages', __DIR__ . '/../db/addCognatePages.sql' );
		$this->addTable( 'cognate_titles', __DIR__ . '/../db/addCognateTitles.sql' );
		$this->addTable( 'cognate_sites', __DIR__ . '/../db/addCognateSites.sql' );
	}

	/**
	 * Add a new table to the database
	 *
	 * @param string $name Name of the new table
	 * @param string $patch Path to the patch file
	 */
	private function addTable( $name, $patch ) {
		if ( $this->db->tableExists( $name, __METHOD__ ) ) {
			$this->updater->output( "...$name table already exists.\n" );
		} else {
			$this->updater->output( "Creating $name table ..." );
			$this->db->sourceFile( $patch );
			$this->updater->output( "done.\n" );
		}
	}

}
