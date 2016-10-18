<?php

namespace Cognate;

use ILoadBalancer;
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
	public function __construct(
		ILoadBalancer $loadBalancer,
		StringNormalizer $stringNormalizer
	) {
		$this->loadBalancer = $loadBalancer;
		$this->stringNormalizer = $stringNormalizer;
	}

	/**
	 * @param string $languageCode The language code of the site
	 * @param LinkTarget $linkTarget
	 *
	 * @return bool
	 */
	public function savePage( $languageCode, LinkTarget $linkTarget ) {
		$pageData = [
			'cgti_site' => $languageCode,
			'cgti_title' => $linkTarget->getDBkey(),
			'cgti_namespace' => $linkTarget->getNamespace(),
			'cgti_key' => $this->stringNormalizer->normalize( $linkTarget->getDBkey() ),
		];
		$dbw = $this->loadBalancer->getConnectionRef( DB_MASTER );
		$result = $dbw->insert( self::TITLES_TABLE_NAME, $pageData, __METHOD__, [ 'IGNORE' ] );

		return $result;
	}

	/**
	 * @param string $languageCode The language code of the site
	 * @param LinkTarget $linkTarget
	 *
	 * @return bool
	 */
	public function deletePage( $languageCode, LinkTarget $linkTarget ) {
		$pageData = [
			'cgti_site' => $languageCode,
			'cgti_title' => $linkTarget->getDBkey(),
			'cgti_namespace' => $linkTarget->getNamespace(),
		];
		$dbw = $this->loadBalancer->getConnectionRef( DB_MASTER );
		$result = $dbw->delete( self::TITLES_TABLE_NAME, $pageData, __METHOD__ );

		return (bool)$result;
	}

	/**
	 * @param string $languageCode The language code of the site being linked from
	 * @param LinkTarget $linkTarget
	 * @return string[] language codes, excluding the language passed into this method.
	 */
	public function getLinksForPage( $languageCode, LinkTarget $linkTarget ) {
		$dbr = $this->loadBalancer->getConnectionRef( DB_SLAVE );
		$result = $dbr->select(
			self::TITLES_TABLE_NAME,
			[ 'cgti_site' ],
			[
				'cgti_site != ' . $dbr->addQuotes( $languageCode ),
				'cgti_key' => $this->stringNormalizer->normalize( $linkTarget->getDBkey() ),
				'cgti_namespace' => $linkTarget->getNamespace(),
			]
		);

		$languageCodes = [];
		while ( $row = $result->fetchRow() ) {
			$languageCodes[] = $row[ 'cgti_site' ];
		}

		return $languageCodes;
	}

	/**
	 * @param array $titleDetailsArray where each element contains the keys 'site', 'namespace', 'title'
	 *        e.g. [ [ 'site' => 'en', 'namespace' => 0, 'title' => 'Berlin' ] ]
	 *
	 * @return bool
	 */
	public function addTitles( array $titleDetailsArray ) {
		$dbw = $this->loadBalancer->getConnectionRef( DB_MASTER );

		$toInsert = [];
		foreach ( $titleDetailsArray as $titleDetails ) {
			$toInsert[] = [
				'cgti_site' => $titleDetails['site'],
				'cgti_namespace' => $titleDetails['namespace'],
				'cgti_title' => $titleDetails['title'],
				'cgti_key' => $this->stringNormalizer->normalize( $titleDetails['title'] ),
			];
		}

		$result = $dbw->insert(
			CognateStore::TITLES_TABLE_NAME,
			$toInsert,
			__METHOD__,
			[ 'IGNORE' ]
		);

		return (bool)$result;
	}

}
