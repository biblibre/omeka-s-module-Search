<?php
namespace Search;

return [
    'api_adapters' => [
        'invokables' => [
            'search_indexes' => Api\Adapter\SearchIndexAdapter::class,
            'search_pages' => Api\Adapter\SearchPageAdapter::class,
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
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'view_helpers' => [
        'factories' => [
            'facetLabel' => Service\ViewHelper\FacetLabelFactory::class,
            'facetLink' => Service\ViewHelper\FacetLinkFactory::class,
        ],
        'invokables' => [
            'searchForm' => View\Helper\SearchForm::class,
        ],
    ],
    'form_elements' => [
        'factories' => [
            Form\Admin\SearchIndexConfigureForm::class => Service\Form\SearchIndexConfigureFormFactory::class,
            Form\Admin\SearchIndexForm::class => Service\Form\SearchIndexFormFactory::class,
            Form\Admin\SearchPageConfigureForm::class => Service\Form\SearchPageConfigureFormFactory::class,
            Form\Admin\SearchPageForm::class => Service\Form\SearchPageFormFactory::class,
            Form\BasicForm::class => Service\Form\BasicFormFactory::class,
            Form\Element\SearchPageSelect::class => Service\Form\Element\SearchPageSelectFactory::class,
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Search\Controller\Admin\Index' => Controller\Admin\IndexController::class,
            'Search\Controller\Index' => Controller\IndexController::class,
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
    'service_manager' => [
        'factories' => [
            'Search\AdapterManager' => Service\AdapterManagerFactory::class,
            'Search\FormAdapterManager' => Service\FormAdapterManagerFactory::class,
        ],
    ],
    'navigation' => [
        'AdminGlobal' => [
            [
                'label' => 'Search', // @translate
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
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'search' => [
                        'type' => \Zend\Router\Http\Literal::class,
                        'options' => [
                            'route' => '/search-manager',
                            'defaults' => [
                                '__NAMESPACE__' => 'Search\Controller\Admin',
                                'controller' => 'Index',
                                'action' => 'browse',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'index' => [
                                'type' => \Zend\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/index/:action',
                                    'constraints' => [
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ],
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Search\Controller\Admin',
                                        'controller' => 'SearchIndex',
                                    ],
                                ],
                            ],
                            'index-id' => [
                                'type' => \Zend\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/index/:id[/:action]',
                                    'constraints' => [
                                        'id' => '\d+',
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ],
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Search\Controller\Admin',
                                        'controller' => 'SearchIndex',
                                        'action' => 'show',
                                    ],
                                ],
                            ],
                            'page' => [
                                'type' => \Zend\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/page/:action',
                                    'constraints' => [
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ],
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Search\Controller\Admin',
                                        'controller' => 'SearchPage',
                                    ],
                                ],
                            ],
                            'page-id' => [
                                'type' => \Zend\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/page/:id[/:action]',
                                    'constraints' => [
                                        'id' => '\d+',
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ],
                                    'defaults' => [
                                        '__NAMESPACE__' => 'Search\Controller\Admin',
                                        'controller' => 'SearchPage',
                                        'action' => 'show',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
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
    'js_translate_strings' => [
        'Available', // @translate
        'Enabled', // @translate
        'Find resources…', // @translate
        'Find', // @translate
    ],
    'search_adapters' => [
        'factories' => [
            'internal' => Service\Adapter\InternalAdapterFactory::class,
        ],
    ],
    'search_form_adapters' => [
        'invokables' => [
            'basic' => FormAdapter\BasicFormAdapter::class,
        ],
    ],
    'search' => [
        'settings' => [
            'search_pages' => [],
            'search_main_page' => '',
        ],
        'site_settings' => [
            'search_pages' => [],
        ],
    ],
];
