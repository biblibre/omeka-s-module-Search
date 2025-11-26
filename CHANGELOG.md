# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

- Fix index rebuilding

## [0.18.1] - 2025-11-26

- Insert into `search_resource` only resources of configured types (e.g. if a
  search index is configured to index only items, then only items will be
  inserted into `search_resource` for that search index).

  It makes the table smaller, and the sync process faster.

## [0.18.0] - 2025-11-25

- Always index resources in a separate process

  Indexing resources in the same process as the one modifying resources can
  cause a lot of different issues: performance, memory usage, conflicting use
  of the Doctrine entity manager causing errors or data duplication, ...

  Doing it in a separate process ensure that we do not disturb the main process.

  There is a new background job (`Search\Job\Sync`) that is run periodically
  when there are resources that need to be indexed.
  Alternatively, there is also a new script (`bin/sync`) that can be executed
  periodically (using cron for instance). It does the same thing as the new
  background job.

- Show the latest indexation date and time on admin resource details page

## [0.17.5] - 2025-04-16

- Fix missing TestAdapter methods (causing tests failure)

## [0.17.4] - 2025-04-15

- Fix missing FacetFormFactory

## [0.17.3] - 2025-04-14

- Add index order sorting for facets (asc and desc) 

## [0.17.2] - 2025-04-01

- Fix thumbnail display on results to used custom thumbnail if exists and primary media thumbnail otherwise 

## [0.17.1] - 2025-03-10

- Fix SaveQueryForm construction 

## [0.17.0] - 2024-12-17

- Fix sort option label being empty
- Add a summary of the search query above the results (optional, disabled by
  default)

## [0.16.0] - 2024-10-25

### Breaking changes

- Search adapters are now entirely responsible for filtering out
  results that users cannot access.
  `Search\Query::setIsPublic` and `Search\Query::getIsPublic` methods, which
  were previously used for that purpose, are kept for user queries (for
  instance if a user want only private resources)

## [0.15.5] - 2024-10-04

- CSS: Prevent facet column to be shrinked
- Prevent type error when facet value is an integer

## [0.15.4] - 2024-05-15

- When indexing outside of UpdateIndex job, detach all entities that have been
  attached by the indexer, as they can cause weird bugs if not detached.
  For instance, a batch update adding a value could result in the same value
  added multiple times to the same resource

## [0.15.3] - 2024-02-21

- Standard form: be more lenient about DOM structure when adding a new filter
  or a new group of filters (this allow themes that override
  standard-match-group.phtml to wrap these buttons in other HTML elements)

## [0.15.2] - 2024-01-08

- Avoid "undefined variable $title" warning in resource-list.phtml
- Escape HTML in $title in resource-list.phtml

## [0.15.1] - 2024-01-03

- Fix order of search fields returned by
  Search\View\Helper\SearchForm::getAvailableSearchFields

## [0.15.0] - 2023-11-16

- Add highlighting support

## [0.14.0] - 2023-11-16

### Added

- Add proximity setting to add an input on search form to choose distance between terms.
- Change UI of search page configuration form for facet fields, sort fields and
  search fields to be more easily configurable and more "Omeka-like".
  Remove dependency on jQuery UI.
- Add "facet value renderers" (extensible by modules) that allow to show to
  users a value different from what is indexed.
  The new built-in facet value renderer allows to render a resource's title
  when only it's ID is indexed.
- Makes the standard search form easily extensible by breaking it into smaller
  parts, allowing modules to add new parts and allowing user to enable each
  part individually and arrange them in any order.

## [0.13.0] - 2023-06-23

### Changed

- Resources that are created or updated in batch are now indexed in batch.
  Previously each resource was indexed separately. It allows adapters to
  optimize the indexing process.
- Resources that are created or updated are now indexed in a background job in
  order to not slow down the request. Except when we are already in a
  background job or a script executed from command line. In that case resources
  are indexed immediately.

## [0.12.1] - 2023-05-09

### Fixed

- Fixed display of facets when the "save queries" feature is off

## [0.12.0] - 2023-05-04

This version is now compatible with Omeka S 4.0.0

The minimum Omeka S version required is 3.0.0

## [0.11.0] - 2022-12-15

### Added

- Added ability to save queries and manage them with block display

### Fixed

- Add advanced-search js and css assets to correctly add specific input on form (class, item set)

## [0.10.0] - 2022-11-03

### Added

- Added ability to modify the order of search fields in standard form's
  configuration

### Fixed

- Exceptions thrown by indexers are now caught and logged

## [0.9.0] - 2021-04-14
### Added
- Provides to search adapters the ability to returns the resources they can
  handle (contributed by @kyfr59)
- Make index rebuild configurable, which allows:
  - to rebuild the index without clearing it first (which is the default now)
  - to change the batch size
- Add progress information in logs

### Changed
- Improved performances of index rebuild

## [0.8.0] - 2020-10-14

**BREAKING CHANGE** The module is no longer compatible with Omeka S version 2.x

- Added compatibility with Omeka S version 3.x

## [0.7.1] - 2020-10-09

### Added

- Added translation for traditional chinese (`zh_TW`)

### Fixed

- Fixed omeka version constraint in config/module.ini

## [0.7.0] - 2020-09-28

### Changed

- Reword 'Search full-text' into 'Search everywhere'
- Drop dependency to jQueryUI module
- Prevent use of invalid operators in standard form

### Fixed

- Make standard form submit button translatable
- Prevent indexation job to run out of memory and make it faster

## [0.6.0] - 2020-04-08

### Added

- Added a standard form which mimics the item advanced search form

### Changed

- *BREAKING CHANGE* `Search\Adapter\AdapterInterface::getAvailableFields` was
  renamed to `getAvailableSearchFields`
- *BREAKING CHANGE* A new method `getAvailableOperators` was added to
  `Search\Adapter\AdapterInterface`
- *BREAKING CHANGE* `Search\Query::addFilter` was renamed to `addFacetFilter`
- *BREAKING CHANGE* `Search\Query::getFilters` was renamed to `getFacetFilters`
- *BREAKING CHANGE* New methods `addQueryFilter` and `getQueryFilters` were
  added to `Search\Query`
- Search form is now hidden when displaying results.

### Removed

- Basic form was removed (it can be replaced by standard form)

## [0.5.0] - 2019-03-06

### Changed

- Omeka S >= 2.0.0 required
- Replace refresh icon by sync icon

### Fixed

- Fixed compatibility issues with Omeka S 2.x
- Fixed result page without sort options
- Fixed search inside a site


## [0.4.0] - 2017-11-09

### Changed

- Travis: use node 7


## [0.3.0] - 2017-08-07

### Added

- Added current site to the search query

### Changed

- Redirect to configure after editing a search page

### Fixed

- Fixed display of actions links in tables
- Fixed table display on admin/index/browse
- Fixed indexing job
- Add required [info] header in module.ini


## [0.2.0] - 2016-12-20

First release

[0.18.1]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.18.1
[0.18.0]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.18.0
[0.17.5]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.17.5
[0.17.4]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.17.4
[0.17.3]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.17.3
[0.17.2]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.17.2
[0.17.1]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.17.1
[0.17.0]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.17.0
[0.16.0]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.16.0
[0.15.5]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.15.5
[0.15.4]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.15.4
[0.15.3]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.15.3
[0.15.2]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.15.2
[0.15.1]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.15.1
[0.15.0]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.15.0
[0.14.0]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.14.0
[0.13.0]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.13.0
[0.12.1]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.12.1
[0.12.0]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.12.0
[0.11.0]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.11.0
[0.10.0]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.10.0
[0.9.0]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.9.0
[0.8.0]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.8.0
[0.7.1]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.7.1
[0.7.0]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.7.0
[0.6.0]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.6.0
[0.5.0]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.5.0
[0.4.0]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.4.0
[0.3.0]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.3.0
[0.2.0]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.2.0
