<?php
/**
 * Albatros Frei - Mail & Webhook Handler
 * This script processes form submissions, sends an email, and optionally triggers a webhook.
 */

// --- CONFIGURATION ---
$targetEmail = 'info@albatros-frei.com';
$emailSubject = 'Neue Kontaktanfrage von Albatros-Frei.com';

// Redirect URLs (Domain and path are fully flexible)
$redirectSuccess = 'https://www.albatros-frei.com/danke.html';
$redirectError = 'https://www.albatros-frei.com/index.html#contact';

// Webhook settings
$enableWebhook = false;
$webhookUrl = 'https://dein-webhook-url.com';

// CORS - Allow your domain (e.g., 'https://www.albatros-frei.com')
$allowedOrigin = '*';
// ----------------------

// Helper to check if it's an AJAX request
function isAjax()
{
    return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
        || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
}

// Set headers for CORS
header("Access-Control-Allow-Origin: $allowedOrigin");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

// Get POST data
// If sending as application/json
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Fallback for standard form data
if (!$data) {
    $data = $_POST;
}

// Basic validation
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$subject = trim($data['subject'] ?? 'Allgemeine Anfrage');
$message = trim($data['message'] ?? '');

if (empty($name) || empty($email) || empty($message)) {
    if (isAjax()) {
        http_response_code(400);
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(['status' => 'error', 'message' => 'Bitte fülle alle Pflichtfelder aus.']);
    } else {
        header("Location: $redirectError?error=fields");
    }
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    if (isAjax()) {
        http_response_code(400);
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(['status' => 'error', 'message' => 'Ungültige E-Mail Adresse.']);
    } else {
        header("Location: $redirectError?error=email");
    }
    exit;
}

// 1. Send Email
$mailContent = "Neue Nachricht über das Kontaktformular:\n\n";
$mailContent .= "Name: $name\n";
$mailContent .= "E-Mail: $email\n";
$mailContent .= "Thema: $subject\n\n";
$mailContent .= "Nachricht:\n$message\n";

$headers = "From: webmaster@albatros-frei.com\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$mailSent = mail($targetEmail, $emailSubject, $mailContent, $headers);

// 2. Trigger Webhook (optional)
$webhookStatus = 'disabled';
if ($enableWebhook && !empty($webhookUrl)) {
    $curl = curl_init($webhookUrl);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $webhookStatus = ($httpCode >= 200 && $httpCode < 300) ? 'success' : 'failed';
}

// Final Response
if ($mailSent) {
    if (isAjax()) {
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode([
            'status' => 'success',
            'message' => 'Deine Nachricht wurde erfolgreich versendet.',
            'webhook' => $webhookStatus
        ]);
    } else {
        header("Location: $redirectSuccess");
    }
} else {
    if (isAjax()) {
        http_response_code(500);
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(['status' => 'error', 'message' => 'Fehler beim Senden der E-Mail.']);
    } else {
        header("Location: $redirectError?error=server");
    }
}
