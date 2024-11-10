<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cloudinary Configuration
    |--------------------------------------------------------------------------
    */
    
    // Configuration de base
    'cloud_name' => env('CLOUDINARY_CLOUD_NAME', 'dw3vcn5ae'),
    'api_key' => env('CLOUDINARY_API_KEY', '911558151572114'),
    'api_secret' => env('CLOUDINARY_API_SECRET', 'Z9O0oOtS2knyfwanxKTYo_lUGis'),
    'secure' => true,

    // URL pour les webhooks
    'notification_url' => env('CLOUDINARY_NOTIFICATION_URL'),

    // URL complète de Cloudinary (format alternatif de configuration)
    'cloud_url' => env('CLOUDINARY_URL', 'cloudinary://911558151572114:Z9O0oOtS2knyfwanxKTYo_lUGis@dw3vcn5ae'),

    // Configurations supplémentaires
    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),
    'upload_route' => env('CLOUDINARY_UPLOAD_ROUTE', 'cloudinary.upload'),
    'upload_action' => env('CLOUDINARY_UPLOAD_ACTION', 'App\Http\Controllers\CloudinaryController@upload'),

    // Paramètres par défaut pour les uploads
    'defaults' => [
        'folder' => env('CLOUDINARY_FOLDER', 'my_uploads'),
        'resource_type' => 'auto',
        'invalidate' => true,
    ],
];