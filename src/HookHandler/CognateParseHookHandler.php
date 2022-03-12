<?php

namespace Cognate\HookHandler;

use Cognate\CognateRepo;
use Cognate\CognateServices;
use MediaWiki\MediaWikiServices;
use ParserOutput;
use Title;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class CognateParseHookHandler {

	/**
	 * @var CognateRepo
	 */
	private $repo;

	/**
	 * @var int[]
	 */
	private $namespaces;

	/**
	 * @var string
	 */
	private $dbName;

	public static function newFromGlobalState() {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		return new CognateParseHookHandler(
			CognateServices::getRepo(),
			$config->get( 'CognateNamespaces' ),
			$config->get( 'DBname' )
		);
	}

	/**
	 * @param CognateRepo $repo
	 * @param int[] $namespaces
	 * @param string $dbName
	 */
	public function __construct(
		CognateRepo $repo,
		array $namespaces,
		$dbName
	) {
		$this->repo = $repo;
		$this->namespaces = $namespaces;
		$this->dbName = $dbName;
	}

	/**
	 * Hook runs after internal parsing
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ContentAlterParserOutput
	 *
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 *
	 * @return bool
	 */
	public function doContentAlterParserOutput( Title $title, ParserOutput $parserOutput ) {
		if ( !in_array( $title->getNamespace(), $this->namespaces ) ) {
			return true;
		}

		$links = $parserOutput->getLanguageLinks();

		$presentLanguages = [];
		foreach ( $links as $linkString ) {
			$linkParts = explode( ':', $linkString, 2 );
			$presentLanguages[$linkParts[0]] = true;
		}

		$cognateLinks = $this->repo->getLinksForPage( $this->dbName, $title );

		foreach ( $cognateLinks as $cognateLink ) {
			$cognateLinkParts = explode( ':', $cognateLink, 2 );
			if ( !array_key_exists( $cognateLinkParts[0], $presentLanguages ) ) {
				$links[] = $cognateLink;
			}
		}

		$parserOutput->setLanguageLinks( $links );

		return true;
	}

}
