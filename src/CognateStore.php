<?php

use MediaWiki\Linker\LinkTarget;

/**
 * @license GNU GPL v2+
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 * @author Addshore
 */
class CognateStore {

	/**
	 * @var ILoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	const TITLES_TABLE_NAME = 'cognate_titles';


	/**
	 * @param ILoadBalancer $loadBalancer
	 * @param StringNormalizer $stringNormalizer
	 */
	public function __construct( ILoadBalancer $loadBalancer, StringNormalizer $stringNormalizer ) {
		$this->loadBalancer = $loadBalancer;
		$this->stringNormalizer = $stringNormalizer;
	}

	/**
	 * @param string $siteLinkPrefix The prefix for generated links
	 * @param LinkTarget $linkTarget
	 *
	 * @return bool
	 */
	public function savePage( $siteLinkPrefix, LinkTarget $linkTarget ) {
		$pageData = [
			'cgti_site' => $siteLinkPrefix,
			'cgti_title' => $linkTarget->getDBkey(),
			'cgti_namespace' => $linkTarget->getNamespace(),
			'cgti_key' => $this->stringNormalizer->normalize( $linkTarget->getDBkey() ),
		];
		$dbw = $this->loadBalancer->getConnectionRef( DB_MASTER );
		$result = $dbw->insert( self::TITLES_TABLE_NAME, $pageData, __METHOD__, [ 'IGNORE' ] );

		return $result;
	}

	/**
	 * @param string $siteLinkPrefix The prefix for generated links
	 * @param LinkTarget $linkTarget
	 *
	 * @return bool
	 */
	public function deletePage( $siteLinkPrefix, LinkTarget $linkTarget ) {
		$pageData = [
			'cgti_site' => $siteLinkPrefix,
			'cgti_title' => $linkTarget->getDBkey(),
			'cgti_namespace' => $linkTarget->getNamespace(),
		];
		$dbw = $this->loadBalancer->getConnectionRef( DB_MASTER );
		$result = $dbw->delete( self::TITLES_TABLE_NAME, $pageData, __METHOD__ );

		return (bool)$result;
	}

	/**
	 * @param string $siteLinkPrefix The prefix for generated links
	 * @param LinkTarget $linkTarget
	 * @return string[] language codes
	 */
	public function getLinksForPage( $siteLinkPrefix, LinkTarget $linkTarget ) {
		$dbr = $this->loadBalancer->getConnectionRef( DB_SLAVE );
		$result = $dbr->select(
			self::TITLES_TABLE_NAME,
			[ 'cgti_site' ],
			[
				'cgti_site != ' . $dbr->addQuotes( $siteLinkPrefix ),
				'cgti_key' => $this->stringNormalizer->normalize( $linkTarget->getDBkey() ),
				'cgti_namespace' => $linkTarget->getNamespace(),
			]
		);

		$siteLinkPrefixes = [];
		while ( $row = $result->fetchRow() ) {
			$siteLinkPrefixes[] = $row[ 'cgti_site' ];
		}

		return $siteLinkPrefixes;
	}

}
