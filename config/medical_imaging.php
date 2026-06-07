<?php

return [
    'ai_provider' => env('MEDICAL_IMAGE_AI_PROVIDER', 'gemini'),
    'ai_service_url' => env('MEDICAL_IMAGE_AI_URL'),
    'timeout' => env('MEDICAL_IMAGE_AI_TIMEOUT', 45),
];
