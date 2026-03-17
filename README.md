# Search (Omeka S module)

This module add search capabilities to the public interface of Omeka S. It
provides a common interface for other modules to extend it.

It can be extended in two ways:

* Forms adapters: they build the search form and convert form data into
  queries
* Search adapters: they fetch results based on queries built by form adapters

This module provides a configurable form adapter, but no search adapters.

The [Solr module](https://github.com/biblibre/omeka-s-module-Solr)
provides a search adapter for [the Solr search engine](https://lucene.apache.org/solr/).

## Requirements

* Omeka S >= 3.1.0
* At least one module implementing a search adapter (like
  [Solr](https://github.com/biblibre/omeka-s-module-Solr) for instance)

## Features

* Search for items, item sets, and/or media
* Build search forms using configurable form blocks, including:
    * A complex query builder that allow to use different fields, operators
      (contains, exact match, ...) and boolean operators (AND, OR)
    * A resource class filter
    * An item set filter
    * And more (other modules can add their own)
* Filter results using customizable facets
* Order results using configurable sort options
* Search history

## How to contribute

You can contribute to this module in many ways. Discover how by reading
[Contributing](CONTRIBUTING.md).

## License

Search is distributed under the Cea Cnrs Inria Logiciel Libre License, version
2.1 (CeCILL-2.1). The full text of this license is given in the `LICENSE` file.
