<?php

namespace Cognate;

use JobQueueGroup;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;
use Wikimedia\Rdbms\ConnectionManager;

/**
 * Cognate wiring for MediaWiki services.
 */

return [
	'CognateLogger' => function ( MediaWikiServices $services ) {
		return LoggerFactory::getInstance( 'Cognate' );
	},

	'CognateRepo' => function ( MediaWikiServices $services ) {
		/** @var CognateStore $store */
		$store = $services->getService( 'CognateStore' );
		/** @var CacheInvalidator $cacheInvalidator */
		$cacheInvalidator = $services->getService( 'CognateCacheInvalidator' );
		/** @var LoggerInterface $logger */
		$logger = $services->getService( 'CognateLogger' );

		$repo = new CognateRepo(
			$store,
			$cacheInvalidator,
			$services->getTitleFormatter(),
			$logger
		);

		$repo->setStatsdDataFactory( $services->getStatsdDataFactory() );

		return $repo;
	},

	'CognateConnectionManager' => function ( MediaWikiServices $services ) {
		$lbFactory = $services->getDBLoadBalancerFactory();
		$cognateDb = $services->getMainConfig()->get( 'CognateDb' );
		$cognateCluster = $services->getMainConfig()->get( 'CognateCluster' );

		if ( $cognateCluster ) {
			$lb = $lbFactory->getExternalLB( $cognateCluster );
		} else {
			$lb = $lbFactory->getMainLB( $cognateDb );
		}

		return new ConnectionManager(
			$lb,
			$cognateDb
		);
	},

	'CognateStore' => function ( MediaWikiServices $services ) {
		/** @var ConnectionManager $connectionManager */
		$connectionManager = $services->getService( 'CognateConnectionManager' );
		$cognateReadOnly = $services->getMainConfig()->get( 'CognateReadOnly' );

		return new CognateStore(
			$connectionManager,
			new StringNormalizer(),
			new StringHasher(),
			$cognateReadOnly
		);
	},

	'CognatePageHookHandler' => function ( MediaWikiServices $services ) {
		return new CognatePageHookHandler(
			$services->getMainConfig()->get( 'CognateNamespaces' ),
			$services->getMainConfig()->get( 'DBname' )
		);
	},

	'CognateCacheInvalidator' => function ( MediaWikiServices $services ) {
		return new CacheInvalidator( JobQueueGroup::singleton() );
	},
];
