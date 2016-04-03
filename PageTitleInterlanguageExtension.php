<?php


/**
 * @license GNU GPL v2+
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 */
class PageTitleInterlanguageExtension {

	/**
	 * Database object, not necessarily the current wiki DB.
	 *
	 * @var DatabaseBase
	 */
	private $db;

	const TABLE_NAME = 'inter_language_titles';

	/**
	 * @param DatabaseBase $db
	 */
	public function __construct( DatabaseBase $db ) {
		$this->db = $db;
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
		return $this->db->insert( self::TABLE_NAME, $pageData, __METHOD__, [ 'IGNORE' ] );
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
		$result = $this->db->select( self::TABLE_NAME, ['ilt_language'], [
			'ilt_language != ' . $this->db->addQuotes( $language ),
			'ilt_title' => $title
		] );
		while( $row = $result->fetchRow() ) {
			$languages[] = $row[ 'ilt_language' ];
		}
		return $languages;
	}

}