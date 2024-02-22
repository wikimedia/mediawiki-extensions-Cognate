<?php

namespace Cognate;

use MediaWiki\Installer\DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

/**
 * @license GPL-2.0-or-later
 */
class CognateSchemaHooks implements LoadExtensionSchemaUpdatesHook {

	/**
	 * Run database updates
	 *
	 * @param DatabaseUpdater $updater
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$dbType = $updater->getDB()->getType();

		$updater->addExtensionUpdateOnVirtualDomain( [
			CognateServices::VIRTUAL_DOMAIN,
			'addTable',
			'cognate_pages',
			__DIR__ . '/../sql/' . $dbType . '/tables-generated.sql',
			true
		] );
	}
}
