<?php
namespace Search;

return [
    'controllers' => [
        'invokables' => [
            'Search\Controller\Index' => Controller\IndexController::class,
            'Search\Controller\SavedQuery' => Controller\SavedQueryController::class,
            'Search\Controller\Admin\Index' => Controller\Admin\IndexController::class,
            'Search\Controller\Admin\Facets' => Controller\Admin\FacetsController::class,
            'Search\Controller\Admin\SearchFields' => Controller\Admin\SearchFieldsController::class,
            'Search\Controller\Admin\SortFields' => Controller\Admin\SortFieldsController::class,
        ],
        'factories' => [
            'Search\Controller\Admin\SearchIndex' => Service\Controller\Admin\SearchIndexControllerFactory::class,
            'Search\Controller\Admin\SearchPage' => Service\Controller\Admin\SearchPageControllerFactory::class,

        ],
    ],
    'controller_plugins' => [
        'factories' => [
            'searchForm' => Service\Mvc\Controller\Plugin\SearchFormFactory::class,
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            dirname(__DIR__) . '/src/Entity',
        ],
        'proxy_paths' => [
            dirname(__DIR__) . '/data/doctrine-proxies',
        ],
    ],
    'api_adapters' => [
        'invokables' => [
            'search_indexes' => Api\Adapter\SearchIndexAdapter::class,
            'search_pages' => Api\Adapter\SearchPageAdapter::class,
            'saved_queries' => Api\Adapter\SavedQueryAdapter::class,
        ],
    ],
    'form_elements' => [
        'invokables' => [
            'Search\Form\Admin\SearchIndexRebuildForm' => Form\Admin\SearchIndexRebuildForm::class,
        ],
        'factories' => [
            'Search\Form\Admin\SearchIndexForm' => Service\Form\SearchIndexFormFactory::class,
            'Search\Form\Admin\SearchIndexConfigureForm' => Service\Form\SearchIndexConfigureFormFactory::class,
            'Search\Form\Admin\SearchPageForm' => Service\Form\SearchPageFormFactory::class,
            'Search\Form\Admin\SearchPageConfigureForm' => Service\Form\SearchPageConfigureFormFactory::class,
            'Search\Form\StandardForm' => Service\Form\StandardFormFactory::class,
            'Search\Form\StandardConfigForm' => Service\Form\StandardConfigFormFactory::class,
            'Search\Form\Element\SearchPageSelect' => Service\Form\Element\SearchPageSelectFactory::class,
            'Search\Form\SaveQueryForm' => Service\Form\SaveQueryFormFactory::class,
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Search',
                'route' => 'admin/search',
                'resource' => 'Search\Controller\Admin\Index',
                'privilege' => 'browse',
                'class' => 'o-icon-search',
            ],
        ],
    ],
    'navigation_links' => [
        'invokables' => [
            'search-page' => Site\Navigation\Link\SearchPage::class,
        ],
    ],
    'block_layouts' => [
        'invokables' => [
            'savedQueries' => Site\BlockLayout\SavedQueries::class,
        ],
    ],
    'router' => [
        'routes' => [
            'site' => [
                'child_routes' => [
                    'save-query' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/save-query',
                            'defaults' => [
                                '__NAMESPACE__' => 'Search\Controller',
                                'controller' => 'SavedQuery',
                                'action' => 'save',
                            ],
                        ],
                    ],
                    'delete-query' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/delete-query',
                            'defaults' => [
                                '__NAMESPACE__' => 'Search\Controller',
                                'controller' => 'SavedQuery',
                                'action' => 'delete',
                            ],
                        ],
                    ],
                ],
            ],
            'admin' => [
                'child_routes' => [
                    'search' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/search',
                            'defaults' => [
                                '__NAMESPACE__' => 'Search\Controller\Admin',
                                'controller' => 'Index',
                                'action' => 'browse',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'index' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/index/:action',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Search\Controller\Admin',
                                        'controller' => 'SearchIndex',
                                    ],
                                    'constraints' => [
                                        'id' => '\d+',
                                    ],
                                ],
                            ],
                            'index-id' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/index/:id[/:action]',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Search\Controller\Admin',
                                        'controller' => 'SearchIndex',
                                        'action' => 'show',
                                    ],
                                    'constraints' => [
                                        'id' => '\d+',
                                    ],
                                ],
                            ],
                            'page' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/page/:action',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Search\Controller\Admin',
                                        'controller' => 'SearchPage',
                                    ],
                                    'constraints' => [
                                        'id' => '\d+',
                                    ],
                                ],
                            ],
                            'page-id' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/page/:id[/:action]',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Search\Controller\Admin',
                                        'controller' => 'SearchPage',
                                        'action' => 'show',
                                    ],
                                    'constraints' => [
                                        'id' => '\d+',
                                    ],
                                ],
                            ],
                            'facets' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/facets/:action',
                                    'defaults' => [
                                        'controller' => 'Facets',
                                    ],
                                ],
                            ],
                            'sort-fields' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/sort-fields/:action',
                                    'defaults' => [
                                        'controller' => 'SortFields',
                                    ],
                                ],
                            ],
                            'search-fields' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/search-fields/:action',
                                    'defaults' => [
                                        'controller' => 'SearchFields',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'service_manager' => [
        'factories' => [
            'Search\AdapterManager' => Service\AdapterManagerFactory::class,
            'Search\FormAdapterManager' => Service\FormAdapterManagerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'view_helpers' => [
        'factories' => [
            'facetLink' => Service\ViewHelper\FacetLinkFactory::class,
            'facetLabel' => Service\ViewHelper\FacetLabelFactory::class,
            'saveQuery' => Service\ViewHelper\SaveQueryFactory::class,

        ],
        'invokables' => [
            'searchForm' => View\Helper\SearchForm::class,
            'showSavedQueries' => View\Helper\ShowSavedQueries::class,
            'formFields' => Form\View\Helper\FormFields::class,
        ],
        'delegators' => [
            'Laminas\Form\View\Helper\FormElement' => [
                Service\Delegator\FormElementDelegatorFactory::class,
            ],
        ],
    ],
    'search_form_adapters' => [
        'factories' => [
            'standard' => Service\FormAdapter\StandardFormAdapterFactory::class,
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],

];
