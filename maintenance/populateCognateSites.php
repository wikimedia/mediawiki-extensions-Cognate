<?php

namespace Cognate;

use MediaWiki\Maintenance\Maintenance;
use MediaWiki\Site\Site;
use MediaWiki\Site\SiteList;

// @codeCoverageIgnoreStart
if ( getenv( 'MW_INSTALL_PATH' ) !== false ) {
	require_once getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php';
} else {
	require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}
// @codeCoverageIgnoreEnd

/**
 * Maintenance script for populating the Cognate sites table.
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class PopulateCognateSites extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Populate the Cognate sites table' );
		$this->addOption( 'site-group', 'Site group that this wiki is a member of. '
			. 'For example, "wiktionary".', true, true );
		$this->requireExtension( 'Cognate' );
	}

	/** @inheritDoc */
	public function execute() {
		$siteGroup = $this->getOption( 'site-group' );
		$services = $this->getServiceContainer();

		$this->output( "Getting sites.\n" );
		$siteList = $services->getSiteLookup()->getSites();
		$siteList = $siteList->getGroup( $siteGroup );

		$this->output( 'Got ' . $siteList->count() . " sites.\n" );
		$sites = $this->getSiteDetailsFromSiteList( $siteList );

		$this->output( "Inserting sites.\n" );
		/** @var CognateStore $store */
		$store = $services->getService( 'CognateStore' );
		$store->insertSites( $sites );

		$this->output( "Done.\n" );
		return true;
	}

	/**
	 * @param SiteList $siteList
	 * @return string[]
	 */
	private function getSiteDetailsFromSiteList( SiteList $siteList ) {
		$sites = [];
		foreach ( $siteList as $site ) {
			/** @var Site $site */
			$sites[$site->getGlobalId()] = $site->getLanguageCode();
		}
		return $sites;
	}

}

// @codeCoverageIgnoreStart
$maintClass = PopulateCognateSites::class;
require_once RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd
