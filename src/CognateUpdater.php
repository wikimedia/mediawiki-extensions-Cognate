<?php

namespace Cognate;

use Database;
use DatabaseUpdater;
use Maintenance;
use MWException;
use RuntimeException;

/**
 * This class is needed simply to override the static newForDB method and gain access to the
 * protected extensionUpdates member.
 *
 * DatabaseUpdater::__construct populates extensionUpdates using the LoadExtensionSchemaUpdates hook
 * This newForDB then empties the list of extensionUpdates.
 * This allows CognateUpdater::newForDB to be called without any pre loaded extensionUpdates.
 * This in turn allows certain extension updates to be loaded and run ONLY.
 */
class CognateUpdater extends DatabaseUpdater {

	/**
	 * @param Database $db
	 * @param bool $shared
	 * @param Maintenance $maintenance
	 *
	 * @throws MWException
	 * @return DatabaseUpdater
	 */
	public static function newForDB( Database $db, $shared = false, $maintenance = null ) {
		$updater = parent::newForDB(
			$db,
			$shared,
			$maintenance
		);
		$updater->extensionUpdates = [];
		return $updater;
	}

	protected function getCoreUpdateList() {
		// not used but is abstract and must be implemented
	}

	public function doUpdates( $what = [ 'extensions' ] ) {
		if ( $what !== [ 'extensions' ] ) {
			throw new RuntimeException(
				'Only extension updates should be run using this DatabaseUpdater'
			);
		}
		parent::doUpdates( $what );
	}

}
