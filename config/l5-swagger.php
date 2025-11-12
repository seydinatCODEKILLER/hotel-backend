<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Documentation
    |--------------------------------------------------------------------------
    |
    | La documentation par défaut à utiliser.
    |
    */
    'default' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Documentation Definitions
    |--------------------------------------------------------------------------
    |
    | Ici tu peux définir plusieurs documentations si besoin.
    |
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
                        'url' => env('SWAGGER_PROD_URL', 'https://mon-backend.example.com/api'),
                        'description' => 'Serveur production',
                    ],
                ],
            ],

            'routes' => [
                'api' => 'api/documentation', // URL de Swagger UI
            ],

            'paths' => [
                'docs' => storage_path('api-docs'), // Dossier de stockage
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | Paramètres par défaut de L5-Swagger.
    |
    */
    'defaults' => [

        // ✅ Ajout de la clé 'proxy' pour éviter l'erreur
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
                base_path('app'), // Analyse tous les fichiers dans app/
            ],
            'excludes' => [],
            'base' => '/',
        ],

        'securityDefinitions' => [
            'securitySchemes' => [],
            'security' => [],
        ],

        // Paramètres supplémentaires pour Swagger UI
        'swagger_ui' => [
            'display' => true,
            'validator_url' => null,
        ],

        'generate_always' => true, // génère automatiquement à chaque refresh (optionnel)
        'generate_yaml_copy' => false,
        'swagger_version' => '4.1.0',
    ],
];
