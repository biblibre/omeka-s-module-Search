Search (module for Omeka S)
===========================

[![Build Status](https://travis-ci.org/biblibre/omeka-s-module-Search.svg?branch=master)](https://travis-ci.org/biblibre/omeka-s-module-Search)

[Search] is a module for [Omeka S] that adds search capabilities to the public
interface of Omeka S, in particular filters and facets. Furthermore, it provides
a common interface for other modules to extend it (forms, indexers, queriers).

It can be extended in two ways:

- Forms that will build the search form and construct the query
- Adapters that will do the real work (indexing and querying)

A basic form is provided, with one single main search field without filters,
enough in most of the cases for the end uers, especially because the results
allow facets. An advanced example of a full form is [Psl Search Form], that
displays filters for item sets, selected properties, range of dates and map
locations. Note: some features of this advanced form are not managed by the
internal adapter currently, in particular the queries on a range of dates.

An internal adapter is provided too. It uses the internal Api of Omeka to search
resources. There is no indexer currently, and the search engine is the sql one,
so it is limited strictly to the request like the standard Omeka S search engine
(no wildcards, no management of singular/plural, etc.). Nevertheless, it
provides the facets to improve the results. A module is available for [Solr],
one of the most used search engine.

The Psl search form and the Solr modules were initially built by [BibLibre] and
are used by the [digital library of PSL], a French university.


Installation
------------

Uncompress the zip inside the folder `modules` and rename it `Search`.

See general end user documentation for [Installing a module].

### Requirements

- Module [jQueryUI] for admin interface, to manage the fields used by the form.

### Optional dependency

- Module [Reference] to display facets in the results with the internal adapter.
  It is not needed for external search engines.


Quick start
-----------

The main admin menu `Search` allows to manage the search indexes and the search
pages: an instance of Omeka can contain multiple indexes, for example to hide
some fields in the public front-end, and multiple pages, for example a single
field search and an advanced search with filters, or different parameters for
different sites or different resource types (items or item sets).

An index and a page for the internal adapter are automatically prepared during
install. This search engine can be enabled in main settings and site settings.
It can be removed too.

To create a new search engine, follow these steps.

1. Create an index
    1. Add a new index with name `Internal` or whatever you want, using the
       `Internal` adapter. The index can be set for items and/or item sets.
    2. The internal adapter doesn’t create any index, so you don’t need to
       launch the indexation by clicking on the "reindex" button (two arrows
       forming a circle).
2. Create a page
    1. Add a page named `Internal search` or whatever you want, a path to access
       it, for example `search` or `find`, the index that was created in the
       previous step (`Internal` here), and a form adapter (`Basic`) that will
       do the mapping between the form and the index. Forms added by modules can
       manage an advanced input field and/or filters.
    2. In the page configuration, you can enable/disable facet and sort fields
       by drag-drop. The order of the fields will be the one that will be used
       for display. Note that some indexers may have fields that seem
       duplicated, but they aren’t: some of them allow to prepare search indexes
       and some other facets or sort indexes. Some of them may be used for all
       uses. This is not the case for the internal indexer, since there is no
       index.
       For example, you can use `dcterms:type`, `dcterms:subject`,
       `dcterms:creator`, `dcterms:date`, `dcterms:spatial`, `dcterms:language`
       and `dcterms:rights` as facets, and `dcterms:title`, `dcterms:date`, and
       `dcterms:creator` as sort fields.
       **Important**: with the internal adapter, the facets work only if the
       module [Reference] is enabled.
    3. Edit the name of the label that will be used for facets and sort fields
       in the same page. The string will be automatically translated if it
       exists in Omeka.
3. In admin and site settings
    1. To access to the search form, enable it in the main settings (for the
       admin board) and in the site settings (for the front-end sites). So the
       search engine will be available in the specified path: `https://example.com/s/my-site/search`
       or `https://example.com/admin/search` in this example.
    2. Optionally, add a custom navigation link to the search page in the
       navigation settings of the site.

The search form should appear. Type some text then submit the form to display
the results as grid or as list. The page can be themed.

**IMPORTANT**

The Search module  does not replace the default search page neither the default
search engine. So the theme should be updated.


Indexation
----------

The indexation of items and item sets is automatic and all new metadata can be
searched in the admin board. Note that there may be a cache somewhere, and they
may be not searchable in the public sites.

So when the item pool of a site or the item sets attached to it are modified, a
manual reindexation should be done in the Search board. This job can be done via
a cron too (see your system cron).


TODO
----

- Normalize the url query with a true standard (or the Omeka S one, or at the
  choice of the admin, or the developer of the forms and queriers).
- Make the search arguments groupable to allow smart facets: always display all
  facets from the original queries, with "or" between facets of the same group,
  and "and" between groups. Require that the core api allows groups.
- Use the standard view with tabs and property selector for the page creation,
  in order not to limit it to Dublin Core terms. It will avoid the dependency to
  the module [jQueryUI] too. The tabs may be "Filters", "Facets", and "Sort".
- Create an internal index (see Omeka Classic) and move all related code into
  another module.
- Allow to remove an index without removing pages.
- Allow to import/export a mapping via json, for example the default one.


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [module issues] page on GitHub.


License
-------

This module is published under the [CeCILL v2.1] licence, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

This software is governed by the CeCILL license under French law and abiding by
the rules of distribution of free software. You can use, modify and/ or
redistribute the software under the terms of the CeCILL license as circulated by
CEA, CNRS and INRIA at the following URL "http://www.cecill.info".

As a counterpart to the access to the source code and rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software’s author, the holder of the economic rights, and the
successive licensors have only limited liability.

In this respect, the user’s attention is drawn to the risks associated with
loading, using, modifying and/or developing or reproducing the software by the
user in light of its specific status of free software, that may mean that it is
complicated to manipulate, and that also therefore means that it is reserved for
developers and experienced professionals having in-depth computer knowledge.
Users are therefore encouraged to load and test the software’s suitability as
regards their requirements in conditions enabling the security of their systems
and/or data to be ensured and, more generally, to use and operate it in the same
conditions as regards security.

The fact that you are presently reading this means that you have had knowledge
of the CeCILL license and that you accept its terms.


Contact
-------

Current maintainers of the module:

* BibLibre (see [BibLibre])
* Daniel Berthereau (see [Daniel-KM])


Copyright
---------

See commits for full list of contributors.

* Copyright BibLibre, 2016-2017
* Copyright Daniel Berthereau, 2017-2018


[Search]: https://github.com/BibLibre/Omeka-S-module-Search
[Omeka S]: https://omeka.org/s
[Psl Search Form]: https://github.com/BibLibre/Omeka-S-module-PslSearchForm
[Solr]: https://github.com/biblibre/Omeka-S-module-Solr
[digital library of PSL]: https://bibnum.explore.univ-psl.fr
[Installing a module]: http://dev.omeka.org/docs/s/user-manual/modules/#installing-modules
[jQueryUI]: https://github.com/biblibre/omeka-s-module-jQueryUI
[Reference]: https://github.com/Daniel-KM/Omeka-S-module-Reference
[module issues]: https://github.com/BibLibre/Omeka-S-module-Search/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[BibLibre]: https://github.com/biblibre
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
