<?php

namespace Cognate;

use MediaWiki\Installer\Task\Task;
use MediaWiki\Language\RawMessage;
use MediaWiki\MainConfigNames;
use MediaWiki\Status\Status;
use MediaWiki\WikiMap\WikiMap;

class CognateInstallerTask extends Task {
	/**
	 * @return string
	 */
	public function getName() {
		return 'cognate-sites';
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return 'Adding Cognate site';
	}

	/**
	 * @return string[]
	 */
	public function getDependencies() {
		return [ 'services', 'extension-tables' ];
	}

	public function execute(): Status {
		$status = Status::newGood();
		$interwikis = $this->getConfigVar( MainConfigNames::LocalInterwikis );
		if ( !is_array( $interwikis ) || !isset( $interwikis[0] ) ) {
			$status->warning( new RawMessage( 'Can\'t add Cognate site: no local interwiki prefix' ) );
			return $status;
		}

		CognateServices::getStore( $this->getServices() )
			->insertSites( [ WikiMap::getCurrentWikiId() => $interwikis[0] ] );
		return $status;
	}
}
