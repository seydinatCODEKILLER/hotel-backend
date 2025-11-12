<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Documentation
    |--------------------------------------------------------------------------
    */
    'default' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Documentation Definitions
    |--------------------------------------------------------------------------
    */
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'Hotel Backend API',
                'description' => 'Documentation de l\'API Hotel Backend',
                'version' => '1.0.0',
                'contact' => [
                    'name' => 'Support',
                    'email' => 'support@hotelbackend.com',
                ],
                'servers' => [
                    [
                        'url' => env('SWAGGER_LOCAL_URL', 'http://127.0.0.1:8000/api'),
                        'description' => 'Serveur local',
                    ],
                    [
                        'url' => env('SWAGGER_PROD_URL', 'https://hotel-backend-production-eaf0.up.railway.app/api'),
                        'description' => 'Serveur production',
                    ],
                ],
            ],

            'routes' => [
                'api' => 'api/documentation',
            ],

            'paths' => [
                'docs' => storage_path('api-docs'),

                // ✅ Force Swagger à servir les assets en HTTPS
                'use_absolute_path' => true,
                'assets' => env('L5_SWAGGER_ASSETS', 'https://hotel-backend-production-eaf0.up.railway.app/docs/asset'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'proxy' => false,
        'operations_sort' => null,
        'additional_config_url' => null,
        'validator_url' => null,

        'routes' => [
            'docs' => 'docs',
            'oauth2_callback' => 'api/oauth2-callback',
            'middleware' => [
                'api' => [],
                'asset' => [],
                'docs' => [],
                'oauth2_callback' => [],
            ],
            'group_options' => [],
        ],

        'paths' => [
            'docs' => storage_path('api-docs'),
            'docs_json' => 'api-docs.json',
            'docs_yaml' => 'api-docs.yaml',
            'format_to_use_for_docs' => 'json',
            'annotations' => [
                base_path('app'),
            ],
            'excludes' => [],
            'base' => '/',
        ],

        'securityDefinitions' => [
            'securitySchemes' => [],
            'security' => [],
        ],

        'swagger_ui' => [
            'display' => true,
            'validator_url' => null,
        ],

        'generate_always' => true,
        'generate_yaml_copy' => false,
        'swagger_version' => '4.1.0',
    ],
];
