Search pages
============

Search pages are where you can configure the search form, facets, sort options, and more.

.. _create-a-search-page:

Create a search page
--------------------

To create a search page:

* Go to Omeka S administration interface
* In the navigation menu on the left, click on "Search"
* Click on the "Add new page" button
* Choose a name for it, and a path. The path defines where the search page will
  be available on your public sites. For instance if you set the path to
  "search", the search page will be available at ``/s/<site-slug>/search``.
* For "Index", choose the previously created index
* For "Form", select the "Standard" form.
* Once the page is created, you will be redirected to the search page edit
  page, where you will have more settings to configure, depending on the selected
  index and form.

Search page settings
^^^^^^^^^^^^^^^^^^^^

Save queries
    If enabled, a small form will be displayed next to the search results,
    allowing to save the query so you can redo the same search later. Saved
    queries are personnal and not shared with other users.

    The "Saved Queries" page block displays all saved queries for the current user.

Faceted filtering
    If enabled, users can select multiple facets and apply them at once. If
    disabled, each click on a facet refreshes the list of results.

Facet values collapse button label
    When the number of facet values exceeds the maximum number of displayed
    values, a button allows to expand/collapse the list of values.

    This parameter defines the text of the "collapse" button.

Facet values expand button label
    When the number of facet values exceeds the maximum number of displayed
    values, a button allows to expand/collapse the list of values.

    This parameter defines the text of the "expand" button.

Show search summary
    If enabled, show a textual description of the search query next to the
    results.

Facets
    This setting allows to add and configure special filters called facets.
    Enabled facets will display a list of filters next to search results, based
    on indexed data. They can be useful to filter by resource type, topic,
    and/or date for instance.

    The list of available facets depends on the search adapter.

    To enable a facet, select one in the dropdown list and click on the "+"
    button. The settings for this facet will appear in a sidebar.

    Label
        Title of the facet block

    Sort by
        Define the sort order of the facet terms. The list of available options
        depends on the search adapter.

        The default option will most likely be a sort by number of occurrences
        (how many times the term appears in the search results).

    Value renderer
        The value renderer changes how a facet value is displayed to users. It
        is useful when the indexed value is an internal id or code that can be
        associated to a human-friendly text. The default renderer shows the
        indexed value as is.

    Facet fetched limit
        How many terms to retrieve from the search engine

    Facet display limit
        How many terms to display by default. If there are more terms, a button
        to display them will be available.

    Operator
        AND
            When multiple terms are selected, the results will be limited to
            those that match all the selected terms.

        OR
            When multiple terms are selected, the results will be limited to
            those that match at least one of the selected terms.

Sort fields
    This settings allows to add and configure sort options.

    The list of available sort fields depends on the search adapter.

    To enable a sort field, select one in the dropdown list and click on the "+"
    button. The settings for this sort field will appear in a sidebar.

    Label
        Label of the sort field in the sort options dropdown list.

Form settings
-------------

This section is entirely dependent on the form adapter chosen at the creation
of the search page. If you haven chosen the standard form, the following
settings will be available:

Search fields
    These are the fields that will be available in the main filters section of
    the search form. These filters can be combined with and/or boolean
    operators, and can use special search operators like "contains any word",
    "contains all words", ... (the list of available search operators depends
    on the search adapter)

    The list of available search fields depends on the search adapter.

    To add a search field, select one in the dropdown list and click on the "+"
    button. The settings for this search field will appear in a sidebar.

    Label
        Label of the search field in the search field dropdown list.

Proximity
    Enable proximity search options for compatible search fields. Proximity
    search allows you to search multiple terms with a maximum distance between
    them.

Form elements
    Add more filter options to the search form.

    Resource class
        Filter by resource class

    Item sets
        Filter by item set

    Has media
        Filter by media presence

    Other modules can implement their own form elements.
