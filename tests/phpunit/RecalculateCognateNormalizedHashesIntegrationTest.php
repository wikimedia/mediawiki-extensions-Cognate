<?php

namespace Cognate\Tests;

use Cognate\CognateStore;
use Cognate\RecalculateCognateNormalizedHashes;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use MediaWiki\Title\TitleValue;

// files in maintenance/ are not autoloaded, so load explicitly
require_once __DIR__ . '/../../maintenance/recalculateCognateNormalizedHashes.php';

/**
 * @covers \Cognate\RecalculateCognateNormalizedHashes
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch <mail@mariushoch.de>
 *
 * @group Database
 */
class RecalculateCognateNormalizedHashesIntegrationTest extends MaintenanceBaseTestCase {

	public function getMaintenanceClass() {
		return RecalculateCognateNormalizedHashes::class;
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
		$this->store->insertSites( [ $dbName => 'foo' ] );

		$this->insertPage( 'PageWithValidHash', 'Text', $namespace );
		$this->insertPage( 'PageWithInValidHash', 'Text', $namespace );

		// Manually screw up the hash for "PageWithInValidHash"
		$this->getdb()->newUpdateQueryBuilder()
			->update( CognateStore::TITLES_TABLE_NAME )
			->set( [ 'cgti_normalized_key' => 123 ] )
			->where( [ 'cgti_raw' => 'PageWithInValidHash' ] )
			->caller( __METHOD__ )
			->execute();
		// Make sure the hash is actually incorrect now
		$this->assertSame(
			[],
			$this->store->selectSitesForPage( new TitleValue( $namespace, 'PageWithInValidHash' ) )
		);

		$this->assertTrue( $this->createMaintenance()->execute() );
		$this->assertMatchesRegularExpression( '/^1 hashes recalculated/m', $this->getActualOutput() );

		$this->assertSame(
			[ $dbName ],
			$this->store->selectSitesForPage( new TitleValue( $namespace, 'PageWithValidHash' ) )
		);
		$this->assertSame(
			[ $dbName ],
			$this->store->selectSitesForPage( new TitleValue( $namespace, 'PageWithInValidHash' ) )
		);
	}

}
