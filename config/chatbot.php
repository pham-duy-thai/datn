<?php

return [
    'openai_api_key' => env('OPENAI_API_KEY'),
    'openai_model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    'openai_url' => env('OPENAI_RESPONSES_URL', 'https://api.openai.com/v1/responses'),
    'hospital_name' => env('HOSPITAL_NAME', 'Hệ thống bệnh viện'),
    'hotline' => env('HOSPITAL_HOTLINE', '1900 1000'),
    'support_email' => env('HOSPITAL_SUPPORT_EMAIL', 'support@hospital.test'),
];
