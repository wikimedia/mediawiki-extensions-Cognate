# Cognate extension

This MediaWiki extension creates a central store where the page titles of all translations for a page are stored. 
The extension can then generate translation links across wiki projects. 

It works on the assumption that (most) page titles are the same across languages, as it is the case for the Wiktionary 
projects. Some research still has to be done to what extent the page titles differ according to project/language-specific
spelling rules.

This extension is an attempt to solve the task
"[Centralize interwiki language links for Wiktionary](https://phabricator.wikimedia.org/T987)"

"Cognate" is a linguistic concept, meaning same words in different languages developed from the same origin. 
Since this extension has a similar translation mechanism, it makes for a nice short extension name, even when the 
actual concept is different.

## Installation
### Single-Wiki setup
To just test the extension, check out this extension into the `extensions` folder of your MediaWiki installation and add 
the following line to  your `LocalSettings.php`:

    wfLoadExtension( 'Cognate' );

Now call the `maintenance/update.php` script from the command line to set up the new database table of the extension.

You can now run the unit tests and check if saving pages writes their titles to the database table. No translation links will be generated, except if you add manual entries to the database table. 

### Multiple language setup
For the multiple language setup (that reflects the state of the Wiktionary projects), you need have several MediaWiki installations in different languages that act as the different Wiktionary projects. You need to set up the load balancer configurations with wiki names for each project. One of the wikis will be the "main wiki" that contains the "central store" database table, set up as above. You need to add a line like this to the `LocalSettings.php` of all the other Wikis:

    $wgCognateWiki = 'enwiktionary'
 
Replace `enwiktionary` with the load balancer wiki name of of the main wiki.
 
## Known Issues
This extension is by no means finished and just a suggestion on how the task T987 might be tackled. There are lots of 
TODOs in the code and in the task comments.  
