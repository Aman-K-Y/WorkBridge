<?php
// ============================================
// WorkBridge - API Helper Functions
// Wraps cURL calls to the Python backend so pages don't repeat this logic.
// ============================================

/**
 * Make a request to the API.
 * @param string $method   GET, POST, PUT
 * @param string $endpoint e.g. "/jobs", "/auth/login"
 * @param array|null $data Request body (will be JSON-encoded)
 * @param bool $authRequired Whether to attach the logged-in user's JWT token
 * @return array ['status' => int, 'body' => array]
 */
function apiRequest($method, $endpoint, $data = null, $authRequired = false) {
    $url = API_BASE_URL . $endpoint;
    $ch = curl_init($url);

    $headers = ['Content-Type: application/json'];

    if ($authRequired && isset($_SESSION['token'])) {
        $headers[] = 'Authorization: Bearer ' . $_SESSION['token'];
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['status' => 0, 'body' => ['error' => 'Could not connect to the API: ' . $error]];
    }

    $decoded = json_decode($response, true);
    return ['status' => $httpCode, 'body' => $decoded];
}

function apiGet($endpoint, $authRequired = false) {
    return apiRequest('GET', $endpoint, null, $authRequired);
}

function apiPost($endpoint, $data, $authRequired = false) {
    return apiRequest('POST', $endpoint, $data, $authRequired);
}

function apiPut($endpoint, $data, $authRequired = false) {
    return apiRequest('PUT', $endpoint, $data, $authRequired);
}

/** Check if a user is currently logged in */
function isLoggedIn() {
    return isset($_SESSION['token']) && isset($_SESSION['user']);
}

/** Redirect to login if not authenticated */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/** Redirect if not the correct role */
function requireRole($role) {
    requireLogin();
    if ($_SESSION['user']['role'] !== $role) {
        header('Location: dashboard.php');
        exit;
    }
}
