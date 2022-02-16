# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.7.2] - 2022-02-16

### Added

- Make index rebuild configurable, which allows:
  - to rebuild the index without clearing it first (which is the default now)
  - to change the batch size

### Fixed

- Limit results to public resources for users without view-all permission

## [0.7.1] - 2020-10-09

### Added

- Added translation for traditional chinese (zh_TW)

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

[0.7.2]: https://github.com/biblibre/omeka-s-module-Search/compare/v0.7.1...v0.7.2
[0.7.1]: https://github.com/biblibre/omeka-s-module-Search/compare/v0.7.0...v0.7.1
[0.7.0]: https://github.com/biblibre/omeka-s-module-Search/compare/v0.6.0...v0.7.0
[0.6.0]: https://github.com/biblibre/omeka-s-module-Search/compare/v0.5.0...v0.6.0
[0.5.0]: https://github.com/biblibre/omeka-s-module-Search/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/biblibre/omeka-s-module-Search/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/biblibre/omeka-s-module-Search/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/biblibre/omeka-s-module-Search/releases/tag/v0.2.0
