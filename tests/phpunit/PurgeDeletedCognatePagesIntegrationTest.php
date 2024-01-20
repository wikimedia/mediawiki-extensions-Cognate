<?php

namespace Cognate\Tests;

use Cognate\CognateStore;
use Cognate\PurgeDeletedCognatePages;
use MediaWiki\MediaWikiServices;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use MediaWiki\Title\TitleValue;

// files in maintenance/ are not autoloaded, so load explicitly
require_once __DIR__ . '/../../maintenance/purgeDeletedCognatePages.php';

/**
 * @covers \Cognate\PurgeDeletedCognatePages
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch <mail@mariushoch.de>
 *
 * @group Database
 */
class PurgeDeletedCognatePagesIntegrationTest extends MaintenanceBaseTestCase {

	public function getMaintenanceClass() {
		return PurgeDeletedCognatePages::class;
	}

	use CheckSystemReqsTrait;

	/** @var CognateStore */
	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->markTestSkippedIfNo64bit();

		$this->overrideConfigValue( 'CognateNamespaces', [ $this->getDefaultWikitextNS() ] );
		$this->store = MediaWikiServices::getInstance()->getService( 'CognateStore' );
	}

	public function testExecute() {
		$namespace = $this->getDefaultWikitextNS();
		$services = $this->getServiceContainer();
		$config = $services->getMainConfig();
		$dbName = $config->get( 'DBname' );
		$this->store->insertSites( [ $dbName => 'foo' ] );

		$this->insertPage( 'PageInCognateNS', 'Text', $namespace );
		$this->insertPage( 'RedirectInCognateNS', '#REDIRECT [[PageInCognateNS]]', $namespace );

		$titleValueDoesNotExist = new TitleValue( $namespace, 'PageInCognateNS-DoesNotActuallyExist' );
		$this->store->insertPage( $dbName, $titleValueDoesNotExist );

		// Manually added, pages should be known to us
		$this->assertSame(
			[ $dbName ],
			$this->store->selectSitesForPage( $titleValueDoesNotExist )
		);
		$assertPagesPresent = function () use ( $dbName, $namespace ) {
			$this->assertSame(
				[ $dbName ],
				$this->store->selectSitesForPage( new TitleValue( $namespace, 'PageInCognateNS' ) )
			);
			$this->assertSame(
				[ $dbName ],
				$this->store->selectSitesForPage( new TitleValue( $namespace, 'RedirectInCognateNS' ) )
			);
		};
		$assertPagesPresent();

		$this->assertTrue( $this->createMaintenance()->execute() );

		// After purging, bogus page entry should be gone
		$this->assertSame(
			[],
			$this->store->selectSitesForPage( $titleValueDoesNotExist )
		);
		// After purging, real page entry still exists
		$assertPagesPresent();
	}

}
