# Cognate extension

This MediaWiki extension creates a central store where the page titles for a group of sites are stored.
The extension can then generate interwiki links across wiki projects in cases where the titles are the same.

It works on the assumption that (most) page titles are the same across languages, as it is the case for the Wiktionary
projects.
Some normalization is already applied to titles.
Only titles in default MediaWiki namespaces can currently be used with the extension.

This extension is an attempt to solve the task
"[Centralize interwiki language links for Wiktionary](https://phabricator.wikimedia.org/T987)"

"Cognate" is a linguistic concept, meaning same words in different languages developed from the same origin.
Since this extension has a similar translation mechanism, it makes for a nice short extension name, even when the
actual concept is different.

This extension can be used along side the [InterwikiSorting extension](https://www.mediawiki.org/wiki/Extension:InterwikiSorting) to sort the interwiki links that are displayed.

### Notes

 - Sites should have the same interwiki structure for language links.
 - In the case that two different titles result in the same hash during normal operation this will be logged in the 'Cognate' channel.
 - If two different titles result in the same hash during database population using one of the maintenance scripts there will be no log.

## Installation

### Requirements

PHP 5.5 64bit

### Single-Wiki setup

To just test the extension, check out this extension into the `extensions` folder of your MediaWiki installation and add
the following line to  your `LocalSettings.php`:

    wfLoadExtension( 'Cognate' );

Now call the `maintenance/update.php` script from the command line to set up the new database table of the extension.

You can now run the unit tests and check if saving pages writes their titles to the database table.
No translation links will be generated, except if you add manual entries to the database table.

### Multiple language setup

For the multiple language setup (that reflects the state of the Wiktionary projects), you need to have at least 2 MediaWiki installations in different languages that act as the different Wiktionary projects.
You need to set up the load balancer configurations with wiki names for each project.
You need to add a line like this to the `LocalSettings.php` of all the other Wikis:

    $wgCognateDb = 'wiktionary'
    $wgCognateCluster = 'extension1'


## Development

To run Cognate locally for a single wiki setup you can follow the steps in [single-wiki setup section](#single-wiki-setup).

To run Cognate locally in multiple language setup (that reflects the state of the Wiktionary projects), please follow the steps below:

0. Prerequisites
    * Download Cognate into you extensions folder and load it in your `LocalSetting.php` with `wfLoadExtension( 'Cognate' )`.
    * You will need to have two wikis running locally. If you're using [mediawiki-docker-dev](https://github.com/addshore/mediawiki-docker-dev) that can be achieved easily by running `./addsite wikiname`.

1. Set the following in your `LocalSetting.php`:
```
$wgEnableParserCache = false; // Disable the parser cache to be able to see the interwiki links added immediately after you create a page.

$wgCognateDb = 'default'; // The name of the database which will have Cognate, in this case the default wiki.

$wgCognateNamespaces = [ 0 ]; // Cognate will work only for entries in the main namespace

$wgWBClientSettings['excludeNamespaces'] = [ 0 ]; // Exclude the main namespace where Cognate works in from WikibaseClient to avoid Wikibase trying to setup sitelinks
```

2. Run `php maintenance/update.php` for the wiki that will have the Cognate database. In the example's case that would be `default`. This will create the Cognate tables in the db.

3. Both wikis need to be added as sites to the wiki that has the Cognate db. There's a maintenance script that does that. Consider the following examples:
```bash
php maintenance/addSite.php enwiki wiktionary --pagepath 'http://enwiki.web.mw.localhost:8080/mediawiki/index.php?title=$1' --filepath 'http://enwiki.web.mw.localhost:8080/mediawiki/$1' --language 'enwiki'
```

```bash
php maintenance/addSite.php default wiktionary --pagepath 'http://default.web.mw.localhost:8080/mediawiki/index.php?title=$1' --filepath 'http://default.web.mw.localhost:8080/mediawiki/$1' --language 'default'
```
where `wiktionary` is the name of the site group.
It's important to pass `language` because Cognate is made to work on Wiktionary, where each wiki has a different language code and that language code is also setup to work as an interwiki link. The maintenance script to populate the Cognate sites table only uses the lang code of the wiki from the mediawiki sites table.

4. Add the interwiki ids and links to the `interwiki` table as well. For example, if your first wiki is called `default` and your second wiki is called `enwiki`, execute the following in the `default` db:

```sql
INSERT INTO interwiki (iw_prefix, iw_url, iw_api, iw_wikiid, iw_local, iw_trans) VALUES ('enwiki', 'http://enwiki.web.mw.localhost:8080/mediawiki/index.php?title=', '', '', 1, 0);
```

and then in the `enwiki` db:

```sql
INSERT INTO interwiki (iw_prefix, iw_url, iw_api, iw_wikiid, iw_local, iw_trans) VALUES ('default', 'http://default.web.mw.localhost:8080/mediawiki/index.php?title=', '', '', 1, 0);
```

5. For the wiki that has the Cognate db, execute the Cognate maintenance script:
`php maintenance/populateCognateSites.php --site-group wiktionary`
which will populate the `cognate_sites` table with the info from the `sites` table.

6. Optionally, execute `php ./maintenance/populateCognatePages.php` to populate the `cognate_pages` table with the pages on the wiki that were created prior to installing and configuring Cognate.

7. Test the setup by creating a page on one of the wikis and then creating a page with the same title on the other wiki. You should see an interwiki link in the sidebar pointing to the same page on the first wiki.