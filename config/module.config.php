<?php

return [
/*
    'form_elements' => [
        'factories' => [
            'PdfText\Form\Config' => Service\Form\ConfigFactory::class,
        ],
    ], 
*/
    'controllers' => [
        'invokables' => [
            'Sharing\Controller\Index' => 'Sharing\Controller\IndexController',
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => OMEKA_PATH . '/modules/Sharing/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'router' => [
        'routes' => [
                'embed-item' => [
                    'type' => 'Segment',
                    'options' => [
                        'route' => '/embed-item/:site-slug/:item-id',
                        'defaults' => [
                            '__NAMESPACE__' => 'Sharing\Controller',
                            'controller' => 'Index',
                            'action' => 'embedItem',
                        ],
                    ],
                ],
                'embed-page' => [
                    'type' => 'Segment',
                    'options' => [
                        'route' => '/embed-page/:page-id',
                        'defaults' => [
                            '__NAMESPACE__' => 'Sharing\Controller',
                            'controller' => 'Index',
                            'action' => 'embedPage',
                        ],
                    ],
                ],
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ]
    ]
];
