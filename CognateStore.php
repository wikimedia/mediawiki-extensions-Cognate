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
		$db = $this->loadBalancer->getConnection( DB_MASTER, [], $this->wikiName );
		return $db->insert( self::TABLE_NAME, $pageData, __METHOD__, [ 'IGNORE' ] );
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
		$db = $this->loadBalancer->getConnection( DB_SLAVE, [], $this->wikiName );
		$result = $db->select( self::TABLE_NAME, ['ilt_language'], [
			'ilt_language != ' . $db->addQuotes( $language ),
			'ilt_title' => $title
		] );
		while( $row = $result->fetchRow() ) {
			$languages[] = $row[ 'ilt_language' ];
		}
		return $languages;
	}

}