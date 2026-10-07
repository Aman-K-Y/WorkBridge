<?php
// ============================================
// WorkBridge - Frontend Configuration
// ============================================

define('API_BASE_URL', getenv('API_BASE_URL') ?: 'http://localhost:5000/api');

// Start session on every page that includes this file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
