# Database Schema

The Cognate database schema consists of 3 different tables:

  - cognate_sites
  - cognate_pages
  - cognate_titles

Below is a short description of each table alongside a realworld example of each table in action.

### cognate_sites

Contains a central location for the site data needed by the extension.
Sites are represented by 64 bit hashes of the dbname for efficiency.
This table then merely holds the dbname of the site alongside the interwiki prefix that is needed to link to that site from within the site group.
The table is populated using a maintenance script and only needs to be populated once for all wikis using a single cognate db.

The dbname is used for creating jobs to purge pages on remote wikis when pages on a local wiki are changed.
The interwiki prefix is used for creating language links.

```
# cgsi_key, cgsi_dbname, cgsi_interwiki
9975638506498252800, aawiktionary, aa
2273398888893215505, abwiktionary, ab
-5675348921785790218, afwiktionary, af
```

### cognate_pages

Contains an entry for each page on each wiki that Cognate is configured to run on.

```
# cgpa_site, cgpa_namespace, cgpa_title
9975638506498252800, 0, 9975638506498252800
2273398888893215505, 0, -8817668780587546624
-5675348921785790218, 0, -8817668780587546624
```

Page titles & sites are represented by 64 bit hashes for efficiency.

The namespace ID must be the same for all wikis. This is only guaranteed for the pre-defined namespaces.

This allows pages with equivalent titles on other wikis to be found as follows:
 - For a given title, calculate the normalized hash.
 - Find all entries in cognate_titles with that same cgti_normalized_key and remember their cgti_raw and cgti_raw_key values.
 - Find all entries in cognate_pages matching the cgti_raw_key values.
 - Compute the resulting links from cgpa_site, cgpa_namespace, and cgti_raw.

### cognate_titles

Contains an entry for each title string that the extension comes across, alongside two hashes generated from the title.

cgti_raw holds the actual page title. In the case of a page "Berlin" cgti_raw would be "Berlin" (no matter what namespace the page is in).

A 64 bit hash associated with the raw title is store in cgti_raw_key. This is used to join against cognate_pages.cgpa_title.

cgti_normalized_key holds a 64 bit key computed after applying certain kinds of normalization to the title string.
Different titles with the same cgti_normalized_key are considered equivalent in the context of Cognate.

The purpose of cognate is to automatically link pages with equivalent titles on different wikis via language links.
Having a table of raw titles, raw title hashes, and normalized title hashes, allows this to be done efficiently.

```
# cgti_raw, cgti_raw_key, cgti_normalized_key
Ellipsis..., 9975638506498252800, 9975638506498252800
Ellipsis…, -8817668780587546624, 99975638506498252800
```