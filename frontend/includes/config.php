<?php
// ============================================
// WorkBridge - Frontend Configuration
// ============================================

// Base URL of the Python backend API
define('API_BASE_URL', 'http://localhost:5000/api');

// Start session on every page that includes this file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
