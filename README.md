# Search module for Omeka S

This module add search capabilities to the public interface of Omeka S.

[![Build Status](https://drone.biblibre.com/api/badges/omeka-s/Search/status.svg?ref=refs/heads/master)](https://drone.biblibre.com/omeka-s/Search)

## Description

This module alone is basically useless, but it provides a common interface for
other modules to extend it.

It can be extended in two ways:

- Forms that will build the search form and construct the query
- Adapters that will do the real work

A standard form is provided, but no adapters.
However the [Solr module](https://github.com/biblibre/omeka-s-module-Solr)
provides a search adapter for [Solr](https://lucene.apache.org/solr/).
