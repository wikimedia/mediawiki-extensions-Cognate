<?php

/**
 * @license GNU GPL v2+
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 */
class CognateStore {

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @var string
	 */
	private $wikiName;

	const TABLE_NAME = 'inter_language_titles';

	/**
	 * @param LoadBalancer $loadBalancer
	 * @param string $wikiName
	 */
	public function __construct( LoadBalancer $loadBalancer, $wikiName ) {
		$this->loadBalancer = $loadBalancer;
		$this->wikiName = $wikiName;
	}

	/**
	 * @param string $language Language code, taken from $wgLanguageCode
	 * @param string $title Page title
	 * @return bool
	 */
	public function savePage( $language, $title ) {
		$pageData = [
			'ilt_language' => $language,
			'ilt_title' => $title
		];
		$dbw = $this->loadBalancer->getConnection( DB_MASTER, [], $this->wikiName );
		$result = $dbw->insert( self::TABLE_NAME, $pageData, __METHOD__, [ 'IGNORE' ] );
		$this->loadBalancer->reuseConnection( $dbw );

		return $result;
	}

	/**
	 * Get the language codes where a translations is available
	 *
	 * @param string $language Language code to exclude
	 * @param string $title Page title
	 * @return array language codes
	 */
	public function getTranslationsForPage( $language, $title ) {
		$languages = [];

		$dbr = $this->loadBalancer->getConnection( DB_SLAVE, [], $this->wikiName );
		$result = $dbr->select( self::TABLE_NAME, ['ilt_language'], [
			'ilt_language != ' . $dbr->addQuotes( $language ),
			'ilt_title' => $title
		] );
		$this->loadBalancer->reuseConnection( $dbr );

		while( $row = $result->fetchRow() ) {
			$languages[] = $row[ 'ilt_language' ];
		}

		return $languages;
	}

}
