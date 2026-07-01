Quick start
===========

This page will guide you through the necessary steps to have a working search
page on your public site.

The first step is to create a search index.

Create a search index
---------------------

A search index represents a searchable pool of resources. It can include items,
item sets and/or media. A single search index can be used by one or more search
pages.

To create a search index:

* Go to Omeka S administration interface
* In the navigation menu on the left, click on "Search"
* Click on the "Add new index" button
* Choose a name for it, and select a search adapter. If no adapter is
  available, install the `Solr module <https://omeka.org/s/modules/Solr/>`__
  first.
* Once the index is created, you will be redirected to the index edit page,
  where you will have more settings to configure, depending on the selected
  adapter.
  One important setting is "Resources indexed", which allow you to choose what
  kind of resources will be searchable (items, item sets and/or media).

The next step is to create a search page.

Create a search page
--------------------

The search page is where most of the configuration happens. You will be able to
configure the search form, facets, sort options, and more.

:ref:`Learn how to create a search page <create-a-search-page>`

Add the search page to your site navigation
-------------------------------------------

* Go to your site's navigation section
* In the right sidebar, under "Add a custom link", click on "Search". This will
  add a block in the central area where you can choose a label for the
  navigation item, and the search page you want to link to
* Click on the "Save" button at the top of the page

Now you can go to your public sites, click on the new link and start using the search form.
