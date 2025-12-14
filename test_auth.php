<?php

// Simple test script to check auth endpoint responses
function testLogin($email, $password, $description) {
    $url = 'http://127.0.0.1:8000/api/auth/login';
    $data = json_encode(['email' => $email, 'password' => $password]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    if (curl_error($ch)) {
        echo "=== $description ===\n";
        echo "cURL Error: " . curl_error($ch) . "\n\n";
        curl_close($ch);
        return;
    }

    curl_close($ch);

    echo "=== $description ===\n";
    echo "HTTP Status: $httpCode\n";

    // Split headers and body
    $body = substr($response, $headerSize);
    echo "Response Body: " . substr($body, 0, 200) . "\n\n";
}

// Test correct credentials
testLogin('admin@example.com', 'admin123', 'Correct Credentials');

// Test wrong password
testLogin('admin@example.com', 'wrongpassword', 'Wrong Password');

// Test non-existent user
testLogin('nonexistent@example.com', 'password', 'Non-existent User');
