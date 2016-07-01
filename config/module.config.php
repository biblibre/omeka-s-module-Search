<?php
return [
    'controllers' => [
        'invokables' => [
            'Search\Controller\Index' => 'Search\Controller\IndexController',
            'Search\Controller\Admin\Index' => 'Search\Controller\Admin\IndexController',
        ],
        'factories' => [
            'Search\Controller\Admin\SearchIndex' => 'Search\Service\Controller\Admin\SearchIndexControllerFactory',
            'Search\Controller\Admin\SearchPage' => 'Search\Service\Controller\Admin\SearchPageControllerFactory',
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            __DIR__ . '/../src/Entity',
        ],
        'proxy_paths' => [
            __DIR__ . '/../data/doctrine-proxies',
        ],
    ],
    'api_adapters' => [
        'invokables' => [
            'search_indexes' => 'Search\Api\Adapter\SearchIndexAdapter',
            'search_pages' => 'Search\Api\Adapter\SearchPageAdapter',
        ],
    ],
    'form_elements' => [
        'factories' => [
            'Search\Form\Admin\SearchIndexForm' => 'Search\Service\Form\SearchIndexFormFactory',
            'Search\Form\Admin\SearchIndexConfigureForm' => 'Search\Service\Form\SearchIndexConfigureFormFactory',
            'Search\Form\Admin\SearchPageForm' => 'Search\Service\Form\SearchPageFormFactory',
            'Search\Form\Admin\SearchPageConfigureForm' => 'Search\Service\Form\SearchPageConfigureFormFactory',
            'Search\Form\BasicForm' => 'Search\Service\Form\BasicFormFactory',
            'Search\Form\Element\SearchPageSelect' => 'Search\Service\Form\Element\SearchPageSelectFactory',
        ],
    ],
    'navigation' => [
        'AdminGlobal' => [
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
            'search-page' => 'Search\Site\Navigation\Link\SearchPage',
        ],
    ],
    'router' =>[
        'routes' => [
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
                        ],
                    ],
                ],
            ],
        ],
    ],
    'service_manager' => [
        'factories' => [
            'Search\AdapterManager' => 'Search\Service\AdapterManagerFactory',
            'Search\FormAdapterManager' => 'Search\Service\FormAdapterManagerFactory',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'facetLink' => 'Search\View\Helper\FacetLink',
            'facetLabel' => 'Search\View\Helper\FacetLabel',
            'searchForm' => 'Search\View\Helper\SearchForm',
        ],
    ],
    'search' => [
        'adapters' => [],
        'form_adapters' => [
            'basic' => 'Search\FormAdapter\BasicFormAdapter',
        ],
    ]
];
