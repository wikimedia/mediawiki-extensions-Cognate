<?php

namespace Cognate\Tests;

use Cognate\CognateStore;
use Cognate\RecalculateCognateNormalizedHashes;
use MediaWiki\MediaWikiServices;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use TitleValue;

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

		$this->tablesUsed = [
			CognateStore::PAGES_TABLE_NAME,
			CognateStore::TITLES_TABLE_NAME,
			CognateStore::SITES_TABLE_NAME,
		];
		$this->overrideConfigValue( 'CognateNamespaces', [ $this->getDefaultWikitextNS() ] );
		$this->store = MediaWikiServices::getInstance()->getService( 'CognateStore' );
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
		$this->db->update(
			CognateStore::TITLES_TABLE_NAME,
			[ 'cgti_normalized_key' => 123 ],
			[ 'cgti_raw' => 'PageWithInValidHash' ],
			__METHOD__
		);
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
