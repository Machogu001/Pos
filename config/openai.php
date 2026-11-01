<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key and Organization
    |--------------------------------------------------------------------------
    |
    | Here you may specify your OpenAI API Key and organization. This will be
    | used to authenticate with the OpenAI API - you can find your API key
    | and organization on your OpenAI dashboard, at https://openai.com.
    */

    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),

    /*
    |--------------------------------------------------------------------------
    | Custom OpenAI-Compatible Endpoint
    |--------------------------------------------------------------------------
    |
    | Optionally point to a self-hosted OpenAI-compatible API (e.g. llama.cpp,
    | Open WebUI, LiteLLM). Leave base_url empty to use the official OpenAI API.
    | The model option selects which model the AiAssistance module requests.
    |
    */

    'base_url' => env('OPENAI_BASE_URL'),
    'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo-1106'),

    /*
    |--------------------------------------------------------------------------
    | Reasoning Token Headroom
    |--------------------------------------------------------------------------
    |
    | Extra tokens added on top of each tool's max_token. Needed for "thinking"
    | models (e.g. Qwen3-Thinking) whose hidden reasoning counts against
    | max_tokens - without headroom the visible answer comes back empty.
    |
    */

    'reasoning_headroom' => (int) env('OPENAI_REASONING_HEADROOM', 0),

];
