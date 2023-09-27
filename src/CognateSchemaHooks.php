<?php

namespace Cognate;

use DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

/**
 * @license GPL-2.0-or-later
 */
class CognateSchemaHooks implements LoadExtensionSchemaUpdatesHook {

	/**
	 * Run database updates
	 *
	 * @see CognateUpdater regarding the complexities of this hook
	 *
	 * @param DatabaseUpdater $updater
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		// Add our updater
		$updater->addExtensionUpdate( [ [ CognateUpdater::class, 'update' ] ] );
	}
}
