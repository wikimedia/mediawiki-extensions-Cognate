<?php

namespace Cognate\Tests;

use Cognate\CacheInvalidator;
use Cognate\CognateRepo;
use Cognate\CognateStore;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TitleFormatter;
use TitleValue;

/**
 * @covers \Cognate\CognateRepo
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class CognateRepoUnitTest extends \MediaWikiIntegrationTestCase {

	/**
	 * @param array[] $expectedInvalidateCalls
	 *
	 * @return MockObject|CacheInvalidator
	 */
	private function getMockCacheInvalidator( array $expectedInvalidateCalls = [] ) {
		$mock = $this->createMock( CacheInvalidator::class );
		if ( $expectedInvalidateCalls === [] ) {
			$mock->expects( $this->never() )->method( 'invalidate' );
		}
		$mock->expects( $this->atLeast( count( $expectedInvalidateCalls ) ) )
			->method( 'invalidate' )
			->withConsecutive( ...$expectedInvalidateCalls );
		return $mock;
	}

	/**
	 * @return MockObject|TitleFormatter
	 */
	private function getMockTitleFormatter() {
		$mock = $this->createMock( TitleFormatter::class );
		$mock->method( 'formatTitle' )
			->will( $this->returnCallback( static function ( $ns, $title, $fragment, $interwiki ) {
				return "$interwiki:$ns:$title";
			} ) );
		return $mock;
	}

	public function testSavePage_successAndInvalidate() {
		$titleValue = new TitleValue( 0, 'My_test_page' );
		$store = $this->createMock( CognateStore::class );
		$store->expects( $this->once() )
			->method( 'insertPage' )
			->with( 'siteName', $titleValue )
			->willReturn( true );

		$repo = new CognateRepo(
			$store,
			$this->getMockCacheInvalidator( [ [ [ 'siteName' ], $titleValue ] ] ),
			$this->getMockTitleFormatter(),
			new NullLogger()
		);
		$repo->savePage( 'siteName', $titleValue );
	}

	public function testSavePage_failLogAndNoInvalidate() {
		$titleValue = new TitleValue( 0, 'My_test_page' );
		$store = $this->createMock( CognateStore::class );
		$store->expects( $this->once() )
			->method( 'insertPage' )
			->with( 'siteName', $titleValue )
			->willReturn( false );

		/** @var LoggerInterface|MockObject $mockLogger */
		$mockLogger = $this->createMock( NullLogger::class );
		$mockLogger->expects( $this->once() )
			->method( 'error' )
			->with(
				'Probable duplicate hash for dbKey: \'My_test_page\'',
				[
					'dbName' => 'siteName',
					'namespace' => 0,
					'dbKey' => 'My_test_page',
				]
			);

		$repo = new CognateRepo(
			$store,
			$this->getMockCacheInvalidator( [] ),
			$this->getMockTitleFormatter(),
			$mockLogger
		);
		$repo->savePage( 'siteName', $titleValue );
	}

	public function testDeletePage_successAndInvalidate() {
		$titleValue = new TitleValue( 0, 'My_test_page' );
		$store = $this->createMock( CognateStore::class );
		$store->expects( $this->once() )
			->method( 'deletePage' )
			->with( 'siteName', $titleValue )
			->willReturn( true );

		$repo = new CognateRepo(
			$store,
			$this->getMockCacheInvalidator( [ [ [ 'siteName' ], $titleValue ] ] ),
			$this->getMockTitleFormatter(),
			new NullLogger()
		);
		$repo->deletePage( 'siteName', $titleValue );
	}

	public function testDeletePage_failAndNoInvalidate() {
		$titleValue = new TitleValue( 0, 'My_test_page' );
		$store = $this->createMock( CognateStore::class );
		$store->expects( $this->once() )
			->method( 'deletePage' )
			->with( 'siteName', $titleValue )
			->willReturn( false );

		$repo = new CognateRepo(
			$store,
			$this->getMockCacheInvalidator( [] ),
			$this->getMockTitleFormatter(),
			new NullLogger()
		);
		$repo->deletePage( 'siteName', $titleValue );
	}

	public function testGetLinksForPage_passesThrough() {
		$titleValue = new TitleValue( 0, 'My_test_page' );
		$store = $this->createMock( CognateStore::class );
		$store->expects( $this->once() )
			->method( 'selectLinkDetailsForPage' )
			->with( 'siteName', $titleValue )
			->willReturn( [ [
				'namespaceID' => 0,
				'title' => 'bar',
				'interwiki' => 'foo',
			] ] );

		$repo = new CognateRepo(
			$store,
			$this->getMockCacheInvalidator( [] ),
			$this->getMockTitleFormatter(),
			new NullLogger()
		);
		$result = $repo->getLinksForPage( 'siteName', $titleValue );
		$this->assertSame( [ 'foo:0:bar' ], $result );
	}

}
