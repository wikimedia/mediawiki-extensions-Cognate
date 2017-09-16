<?php

namespace Cognate\Tests;

use Cognate\CognateStore;
use Cognate\StringHasher;
use Cognate\StringNormalizer;
use TitleValue;
use Wikimedia\Rdbms\ConnectionManager;
use Wikimedia\Rdbms\DBReadOnlyError;

/**
 * @covers Cognate\CognateStore
 *
 * @license GNU GPL v2+
 */
class CognateStoreUnitTest extends \PHPUnit_Framework_TestCase {

	private function newReadOnlyCognateStore() {
		/** @var ConnectionManager $connectionManager */
		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		return new CognateStore(
			$connectionManager,
			new StringNormalizer(),
			new StringHasher(),
			true
		);
	}

	public function testWhenInReadOnlyMode_insertPageThrowsException() {
		$store = $this->newReadOnlyCognateStore();

		$this->setExpectedException( DBReadOnlyError::class );

		$store->insertPage( 'foo', new TitleValue( 0, 'Some_Page' ) );
	}

	public function testWhenInReadOnlyMode_deletePageThrowsException() {
		$store = $this->newReadOnlyCognateStore();

		$this->setExpectedException( DBReadOnlyError::class );

		$store->deletePage( 'foo', new TitleValue( 0, 'Some_Page' ) );
	}

}
