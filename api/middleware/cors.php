<?php
// FILE: api/middleware/cors.php

/**
 * PRODUCTION CORS CONFIGURATION
 * Allows the Vercel-hosted frontend to communicate with the HelioHost-hosted backend.
 */

// Define allowed origins
$allowed_origins = [
    'http://localhost:5173', // Vite default local
    'http://127.0.0.1:5173',
    // ADD YOUR VERCEL URL HERE, e.g., 'https://your-app.vercel.app'
];

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowed_origins) || empty($origin)) {
    header("Access-Control-Allow-Origin: " . ($origin ?: "*"));
} else {
    // For production security, you might want to restrict this further
    // header("Access-Control-Allow-Origin: https://your-app.vercel.app");
    header("Access-Control-Allow-Origin: $origin"); // Dynamic for now, but restrict in $allowed_origins above
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-USER-ROLE");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 86400"); // Cache preflight for 24 hours

// Handle preflight (OPTIONS) requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
