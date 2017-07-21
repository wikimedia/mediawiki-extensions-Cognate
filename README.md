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
