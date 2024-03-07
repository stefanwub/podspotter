<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'podcast_index' => [
        'key' => env('PODCAST_INDEX_KEY'),
        'secret' => env('PODCAST_INDEX_SECRET')
    ],

    'ssh' => [
        'username' => env('SSH_USERNAME'),
        'host' => env('SSH_HOST'),
        'key_path' => env('SSH_KEY_PATH', '/home/forge/podspotter.nl/.ssh/id_rsa')
    ],

    'gpus' => [
        // 'instance-5' => [
        //     'host' => '34.170.77.202',
        //     'gpus' => [0]
        // ],
        // 'instance-6' => [
        //     'host' => '34.68.89.76',
        //     'gpus' => [0]
        // ],
        'instance-7' => [
            'host' => '34.170.77.202',
            'gpus' => [0]
        ],
        'instance-8' => [
            'host' => '34.90.34.14',
            'gpus' => [0]
        ]
    ],

    'pinecone' => [
        'host' => env('PINECONE_HOST'),
        'api_key' => env('PINECONE_API_KEY')
    ],

    'google_cloud' => [
        'project' => env('GOOGLE_CLOUD_PROJECT')
    ]

];
