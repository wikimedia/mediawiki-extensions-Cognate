<?php

namespace Cognate\Tests;

use Cognate\CognateStore;
use Cognate\StringHasher;
use Cognate\StringNormalizer;
use PHPUnit\Framework\TestCase;
use TitleValue;
use Wikimedia\Rdbms\ConnectionManager;
use Wikimedia\Rdbms\DBReadOnlyError;

/**
 * @covers \Cognate\CognateStore
 *
 * @license GPL-2.0-or-later
 */
class CognateStoreUnitTest extends TestCase {

	use CheckSystemReqsTrait;

	private function newReadOnlyCognateStore() {
		/** @var ConnectionManager $connectionManager */
		$connectionManager = $this->createMock( ConnectionManager::class );

		return new CognateStore(
			$connectionManager,
			new StringNormalizer(),
			new StringHasher(),
			true
		);
	}

	public function testWhenInReadOnlyMode_insertPageThrowsException() {
		$this->markTestSkippedIfNo64bit();

		$store = $this->newReadOnlyCognateStore();

		$this->expectException( DBReadOnlyError::class );

		$store->insertPage( 'foo', new TitleValue( 0, 'Some_Page' ) );
	}

	public function testWhenInReadOnlyMode_deletePageThrowsException() {
		$this->markTestSkippedIfNo64bit();

		$store = $this->newReadOnlyCognateStore();

		$this->expectException( DBReadOnlyError::class );

		$store->deletePage( 'foo', new TitleValue( 0, 'Some_Page' ) );
	}

}
