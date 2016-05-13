<?php
return [
    'controllers' => [
        'invokables' => [
            'Search\Controller\Index' => 'Search\Controller\IndexController',
            'Search\Controller\Admin\Index' => 'Search\Controller\Admin\IndexController',
            'Search\Controller\Admin\SearchIndex' => 'Search\Controller\Admin\SearchIndexController',
            'Search\Controller\Admin\SearchPage' => 'Search\Controller\Admin\SearchPageController',
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            __DIR__ . '/../src/Entity',
        ],
    ],
    'api_adapters' => [
        'invokables' => [
            'search_indexes' => 'Search\Api\Adapter\SearchIndexAdapter',
            'search_pages' => 'Search\Api\Adapter\SearchPageAdapter',
        ],
    ],
    'navigation' => [
        'admin' => [
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
            'Search\FormManager' => 'Search\Service\FormManagerFactory',
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
        ],
    ],
    'search' => [
        'adapters' => [],
        'forms' => [
            'basic' => 'Search\Form\BasicForm',
        ],
    ]
];
