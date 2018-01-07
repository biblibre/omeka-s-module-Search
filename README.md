Search (module for Omeka S)
===========================

[![Build Status](https://travis-ci.org/biblibre/omeka-s-module-Search.svg?branch=master)](https://travis-ci.org/biblibre/omeka-s-module-Search)

[Search] is a module for [Omeka S] that adds search capabilities to the public
interface of Omeka S.

This module alone is basically useless, but it provides a common interface for
other modules to extend it.

It can be extended in two ways:

- Forms that will build the search form and construct the query
- Adapters that will do the real work

A very basic form is provided as an example, but no adapters. However the [Solr module]
provides a search adapter for Solr.


Installation
------------

Uncompress the zip inside the folder `modules` and rename it `Search`.

See general end user documentation for [Installing a module].

### Requirements

- [jQueryUI module] for admin interface


Quick start
-----------

See the readme of the [Solr module].


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
[Solr module]: https://github.com/biblibre/omeka-s-module-Solr
[Installing a module]: http://dev.omeka.org/docs/s/user-manual/modules/#installing-modules
[jQueryUI module]: https://github.com/biblibre/omeka-s-module-jQueryUI
[module issues]: https://github.com/BibLibre/Omeka-S-module-Search/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[BibLibre]: https://github.com/biblibre
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
