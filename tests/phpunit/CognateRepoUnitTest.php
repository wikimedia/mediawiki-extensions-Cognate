<?php

namespace Cognate\Tests;

use Cognate\CacheInvalidator;
use Cognate\CognateRepo;
use Cognate\CognateStore;
use MediaWiki\Linker\LinkTarget;
use PHPUnit_Framework_MockObject_MockObject;
use Title;
use TitleValue;

/**
 * @covers Cognate\CognateRepo
 *
 * @license GNU GPL v2+
 * @author Addshore
 */
class CognateRepoUnitTest extends \MediaWikiTestCase {

	/**
	 * @return PHPUnit_Framework_MockObject_MockObject|CognateStore
	 */
	private function getMockStore() {
		return $this->getMockBuilder( CognateStore::class )
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @param array $expectedInvalidateCalls
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject|CacheInvalidator
	 */
	private function getMockCacheInvalidator( array $expectedInvalidateCalls = [] ) {
		$mock = $this->getMockBuilder( CacheInvalidator::class )
			->disableOriginalConstructor()
			->getMock();
		if ( $expectedInvalidateCalls === [] ) {
			$mock->expects( $this->never() )->method( 'invalidate' );
		}
		foreach ( $expectedInvalidateCalls as $key => $details ) {
			list( $languageCode, $linkTarget ) = $details;
			$mock->expects( $this->at( $key ) )
				->method( 'invalidate' )
				->will( $this->returnCallback( function( $param1, Title $param2 ) use ( $languageCode, $linkTarget ) {
					$this->assertEquals( $languageCode, $param1 );
					/** @var LinkTarget $linkTarget */
					$this->assertInstanceOf( Title::class, $param2 );
					$this->assertEquals( $linkTarget->getDBkey(), $param2->getDBkey() );
					$this->assertEquals( $linkTarget->getNamespace(), $param2->getNamespace() );
				} ) );
		}
		return $mock;
	}

	public function testSavePage_successAndInvalidate() {
		$titleValue = new TitleValue( 0, 'My_test_page' );
		$store = $this->getMockStore();
		$store->expects( $this->once() )
			->method( 'savePage' )
			->with( 'gg', $titleValue )
			->will( $this->returnValue( true ) );

		$repo = new CognateRepo( $store, $this->getMockCacheInvalidator( [ [ 'gg', $titleValue ] ] ) );
		$repo->savePage( 'gg', $titleValue );
	}

	public function testSavePage_failAndNoInvalidate() {
		$titleValue = new TitleValue( 0, 'My_test_page' );
		$store = $this->getMockStore();
		$store->expects( $this->once() )
			->method( 'savePage' )
			->with( 'gg', $titleValue )
			->will( $this->returnValue( false ) );

		$repo = new CognateRepo( $store, $this->getMockCacheInvalidator( [] ) );
		$repo->savePage( 'gg', $titleValue );
	}

	public function testDeletePage_successAndInvalidate() {
		$titleValue = new TitleValue( 0, 'My_test_page' );
		$store = $this->getMockStore();
		$store->expects( $this->once() )
			->method( 'deletePage' )
			->with( 'gg', $titleValue )
			->will( $this->returnValue( true ) );

		$repo = new CognateRepo( $store, $this->getMockCacheInvalidator( [ [ 'gg', $titleValue ] ] ) );
		$repo->deletePage( 'gg', $titleValue );
	}

	public function testDeletePage_failAndNoInvalidate() {
		$titleValue = new TitleValue( 0, 'My_test_page' );
		$store = $this->getMockStore();
		$store->expects( $this->once() )
			->method( 'deletePage' )
			->with( 'gg', $titleValue )
			->will( $this->returnValue( false ) );

		$repo = new CognateRepo( $store, $this->getMockCacheInvalidator( [] ) );
		$repo->deletePage( 'gg', $titleValue );
	}

	public function testGetLinksForPage_passesThrough() {
		$titleValue = new TitleValue( 0, 'My_test_page' );
		$store = $this->getMockStore();
		$store->expects( $this->once() )
			->method( 'getLinksForPage' )
			->with( 'gg', $titleValue )
			->will( $this->returnValue( [ 'foo' ] ) );

		$repo = new CognateRepo( $store, $this->getMockCacheInvalidator( [] ) );
		$result = $repo->getLinksForPage( 'gg', $titleValue );
		$this->assertEquals( [ 'foo' ], $result );
	}

}
