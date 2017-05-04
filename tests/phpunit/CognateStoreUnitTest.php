<?php

namespace Cognate\Tests;

use Cognate\CognateStore;
use Cognate\StringHasher;
use Cognate\StringNormalizer;
use TitleValue;
use Wikimedia\Rdbms\ConnectionManager;
use Wikimedia\Rdbms\DBReadOnlyError;

/**
 * @cover Cognate\CognateStore
 *
 * @license GNU GPL v2+
 */
class CognateStoreUnitTest extends \PHPUnit_Framework_TestCase {

	private function newReadOnlyCognateStore() {
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

	public function testWhenInReadOnlyMode_insertPagesThrowsException() {
		$store = $this->newReadOnlyCognateStore();

		$this->setExpectedException( DBReadOnlyError::class );

		$store->insertPages( [ [ 'site' => 'foo', 'namespace' => 0, 'title' => 'Some_Page' ] ] );
	}

	public function testWhenInReadOnlyMode_insertSitesThrowsException() {
		$store = $this->newReadOnlyCognateStore();

		$this->setExpectedException( DBReadOnlyError::class );

		$store->insertSites( [ 'foo' => 'f' ] );
	}

}
