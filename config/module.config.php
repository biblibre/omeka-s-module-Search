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
        'invokables' => [
            'searchForm' => View\Helper\SearchForm::class,
        ],
        'factories' => [
            'apiSearch' => Service\ViewHelper\ApiSearchFactory::class,
            'apiSearchOne' => Service\ViewHelper\ApiSearchOneFactory::class,
            'facetLabel' => Service\ViewHelper\FacetLabelFactory::class,
            'facetLink' => Service\ViewHelper\FacetLinkFactory::class,
            'searchIndexConfirm' => Service\ViewHelper\SearchIndexConfirmFactory::class,
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\ConfigForm::class => Form\ConfigForm::class,
            Form\Element\OptionalSelect::class => Form\Element\OptionalSelect::class,
        ],
        'factories' => [
            Form\Admin\ApiFormConfigFieldset::class => Service\Form\ApiFormConfigFieldsetFactory::class,
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
            Controller\Admin\IndexController::class => Controller\Admin\IndexController::class,
            Controller\IndexController::class => Controller\IndexController::class,
        ],
        'factories' => [
            Controller\Admin\SearchIndexController::class => Service\Controller\Admin\SearchIndexControllerFactory::class,
            Controller\Admin\SearchPageController::class => Service\Controller\Admin\SearchPageControllerFactory::class,
        ],
    ],
    'controller_plugins' => [
        'factories' => [
            'apiSearch' => Service\ControllerPlugin\ApiSearchFactory::class,
            'apiSearchOne' => Service\ControllerPlugin\ApiSearchOneFactory::class,
            'searchForm' => Service\ControllerPlugin\SearchFormFactory::class,
        ],
    ],
    'service_manager' => [
        'delegators' => [
           'Omeka\ApiManager' => [Service\ApiManagerDelegatorFactory::class],
        ],
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
                'resource' => Controller\Admin\IndexController::class,
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
                                'controller' => Controller\Admin\IndexController::class,
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
                                        'controller' => Controller\Admin\SearchIndexController::class,
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
                                        'controller' => Controller\Admin\SearchIndexController::class,
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
                                        'controller' => Controller\Admin\SearchPageController::class,
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
                                        'controller' => Controller\Admin\SearchPageController::class,
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
        'Automatic mapping of empty values', // @translate
        'Available', // @translate
        'Enabled', // @translate
        'Find', // @translate
        'Find resources…', // @translate
        'Try to map automatically the metadata and the properties that are not mapped yet with the fields of the index', // @translate
    ],
    'search_adapters' => [
        'factories' => [
            'internal' => Service\Adapter\InternalAdapterFactory::class,
        ],
    ],
    'search_form_adapters' => [
        'invokables' => [
            'api' => FormAdapter\ApiFormAdapter::class,
            'basic' => FormAdapter\BasicFormAdapter::class,
        ],
    ],
    'search' => [
        'config' => [
            'search_batch_size' => 100,
        ],
        'settings' => [
            'search_main_page' => '',
            'search_pages' => [],
            'search_api_page' => '',
        ],
        'site_settings' => [
            'search_main_page' => null,
            'search_pages' => [],
        ],
    ],
];
