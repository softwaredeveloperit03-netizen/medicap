<?php
/**
 * OpenAI API Configuration
 * 
 * IMPORTANT: Never commit this file with your actual API key to version control!
 * Add this file to .gitignore
 * 
 * To use this file:
 * 1. Replace 'YOUR_OPENAI_API_KEY_HERE' with your actual OpenAI API key
 * 2. Keep this file secure and server-side only
 * 3. Never expose this key in client-side code
 */

// OpenAI API Key
// Get your API key from: https://platform.openai.com/api-keys
define('OPENAI_API_KEY', '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');

// OpenAI API Configuration
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');
define('OPENAI_MODEL', 'gpt-3.5-turbo'); // Options: 'gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo-preview'
define('OPENAI_MAX_TOKENS', 1000); // Maximum tokens for response
define('OPENAI_TEMPERATURE', 0.7); // 0.0 (deterministic) to 2.0 (creative)

// Optional: Set timeout for API calls (in seconds)
define('OPENAI_TIMEOUT', 30);

?>
