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

	const TABLE_NAME = 'inter_language_titles';

	/**
	 * @param LoadBalancer $loadBalancer
	 */
	public function __construct( LoadBalancer $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
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
		$dbw = $this->loadBalancer->getConnectionRef( DB_MASTER );
		$result = $dbw->insert( self::TABLE_NAME, $pageData, __METHOD__, [ 'IGNORE' ] );

		return $result;
	}

	/**
	 * @param string $language Language code, taken from $wgLanguageCode
	 * @param string $title Page title
	 * @return bool
	 */
	public function deletePage( $language, $title ) {
		$pageData = [
			'ilt_language' => $language,
			'ilt_title' => $title
		];
		$dbw = $this->loadBalancer->getConnection( DB_MASTER );
		$result = $dbw->delete( self::TABLE_NAME, $pageData, __METHOD__ );
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

		$dbr = $this->loadBalancer->getConnectionRef( DB_SLAVE );
		$result = $dbr->select( self::TABLE_NAME, ['ilt_language'], [
			'ilt_language != ' . $dbr->addQuotes( $language ),
			'ilt_title' => $title
		] );

		while ( $row = $result->fetchRow() ) {
			$languages[] = $row[ 'ilt_language' ];
		}

		return $languages;
	}

}
