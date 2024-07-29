<?php

namespace Cognate\Tests;

use Cognate\CognateStore;
use Cognate\PopulateCognatePages;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use MediaWiki\Title\TitleValue;

// files in maintenance/ are not autoloaded, so load explicitly
require_once __DIR__ . '/../../maintenance/populateCognatePages.php';

/**
 * @covers \Cognate\PopulateCognatePages
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch <mail@mariushoch.de>
 *
 * @group Database
 */
class PopulateCognatePagesIntegrationTest extends MaintenanceBaseTestCase {

	public function getMaintenanceClass() {
		return PopulateCognatePages::class;
	}

	use CheckSystemReqsTrait;

	/** @var CognateStore */
	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->markTestSkippedIfNo64bit();

		$this->overrideConfigValue( 'CognateNamespaces', [ $this->getDefaultWikitextNS() ] );
		$this->store = $this->getServiceContainer()->getService( 'CognateStore' );
	}

	public function testExecute() {
		$namespace = $this->getDefaultWikitextNS();
		$services = $this->getServiceContainer();
		$config = $services->getMainConfig();
		$dbName = $config->get( 'DBname' );

		$this->insertPage( 'PageInCognateNS', 'page text', $namespace );
		$this->insertPage( 'PageNotInCognateNS', 'page text', $namespace + 1 );

		// The page was added on creation, thus empty the store to actually test the maintenance script.
		$this->store->deletePagesForSite( $dbName );

		$this->store->insertSites( [ $dbName => 'foo' ] );
		$this->assertTrue( $this->createMaintenance()->execute() );

		$this->assertSame(
			[ $dbName ],
			$this->store->selectSitesForPage( new TitleValue( $namespace, 'PageInCognateNS' ) )
		);
		$this->assertSame(
			[],
			$this->store->selectSitesForPage( new TitleValue( $namespace + 1, 'PageNotInCognateNS' ) )
		);
	}

}
