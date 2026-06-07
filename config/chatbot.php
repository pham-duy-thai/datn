<?php

return [
    'ai_provider' => env('AI_PROVIDER', 'openai'),
    'openai_api_key' => env('OPENAI_API_KEY'),
    'openai_model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    'openai_url' => env('OPENAI_RESPONSES_URL', 'https://api.openai.com/v1/responses'),
    'gemini_api_key' => env('GEMINI_API_KEY'),
    'gemini_model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    'gemini_url' => env('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models'),
    'hospital_name' => env('HOSPITAL_NAME', 'Hệ thống bệnh viện'),
    'hotline' => env('HOSPITAL_HOTLINE', '1900 1000'),
    'support_email' => env('HOSPITAL_SUPPORT_EMAIL', 'support@hospital.test'),
];
