<?php

namespace Cognate;

use DatabaseUpdater;
use Maintenance;
use MWException;
use Wikimedia\Rdbms\Database;

/**
 * @author Addshore
 *
 * This class is needed simply to override the static newForDB method and gain access to the
 * protected extensionUpdates member.
 *
 * This code is only used while running cores update.php maintenance script via the
 * LoadExtensionSchemaUpdates hook.
 *
 * DatabaseUpdater::__construct populates extensionUpdates using the LoadExtensionSchemaUpdates hook
 * This newForCognateDB then empties the list of extensionUpdates and alters the Updater database
 * instance to be the Cognate database.
 * This allows CognateUpdater::newForCognateDB to be called and return an Updater without any pre
 * loaded extensionUpdates which also points to the correct database.

 * The Cognate database instance can not be passed directly into DatabaseUpdater::newForDB as some
 * hooks that are fired may call methods on the updater which make db calls expecting tables to
 * exist that do not.
 * For example Flow calls the updateRowExists method which expects the updatelog table.
 * This in turn allows only Cognate extension updates to be loaded and run using the DB / Updater.
 */
class CognateUpdater extends DatabaseUpdater {

	/**
	 * @param Database $mainDb
	 * @param Database $cognateDb
	 * @param bool $shared
	 * @param Maintenance $maintenance
	 *
	 * @throws MWException
	 * @return DatabaseUpdater
	 */
	public static function newForCognateDB(
		Database $mainDb,
		Database $cognateDb,
		$shared = false,
		$maintenance = null
	) {
		$updater = parent::newForDB(
			$mainDb,
			$shared,
			$maintenance
		);

		$updater->extensionUpdates = [];

		// Copied from DatabaseUpdater::__construct to alter the db after construction.
		$updater->db = $cognateDb;
		$updater->db->setFlag( DBO_DDLMODE );
		$updater->maintenance->setDB( $updater->db );

		return $updater;
	}

	protected function getCoreUpdateList() {
		// not used but is abstract and must be implemented
	}

}