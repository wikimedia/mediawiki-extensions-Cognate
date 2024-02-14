<?php

namespace Cognate;

use DatabaseUpdater;
use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\IMaintainableDatabase;

/**
 * Helper class for CognateHooks::onLoadExtensionSchemaUpdates
 */
class CognateUpdater {
	/** @var DatabaseUpdater */
	private $updater;

	/** @var IMaintainableDatabase */
	private $db;

	/**
	 * @param DatabaseUpdater $updater
	 */
	public static function update( DatabaseUpdater $updater ) {
		$cognateUpdater = new self( $updater );
		$cognateUpdater->doUpdate();
	}

	/**
	 * @param DatabaseUpdater $updater
	 */
	private function __construct( DatabaseUpdater $updater ) {
		$this->updater = $updater;

		$services = MediaWikiServices::getInstance();

		$this->db = $services->getConnectionProvider()->getPrimaryDatabase( 'virtual-cognate' );
	}

	private function doUpdate() {
		$this->addTable( 'cognate_pages', __DIR__ . '/../sql/' . $this->db->getType() . '/tables-generated.sql' );
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
