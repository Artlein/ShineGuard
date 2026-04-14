<?php
/**
 * SHINEGUARD MAIL SERVICE
 * Responsibility: Corporate email dispatch via Mailtrap API
 */

namespace ShineGuard\Services;

require_once __DIR__ . '/../Templates/Emails/password_reset_template.php';

class MailService {
    
    /**
     * Send a branded password recovery email via Mailtrap Sandbox API
     * 
     * @param string $to Recipient email address
     * @param string $user_name Name of the user (for personalization)
     * @param string $reset_link The unique recovery URL
     * @return array Result status and message
     */
    public static function sendPasswordReset($to, $user_name, $reset_link) {
        // Fallback check: if credentials are not provided
        if (MAILTRAP_API_TOKEN === 'PASTE_YOUR_MAILTRAP_TOKEN_HERE' || empty(MAILTRAP_INBOX_ID)) {
            return ['success' => false, 'error' => 'Mailtrap credentials not configured. Please check dbconnect.php'];
        }

        $url = "https://sandbox.api.mailtrap.io/api/send/" . MAILTRAP_INBOX_ID;
        $html_content = getPasswordResetTemplate($user_name, $reset_link);

        $payload = [
            "from" => ["email" => SYSTEM_EMAIL, "name" => SYSTEM_NAME],
            "to" => [["email" => $to]],
            "subject" => "🔐 ShineGuard: Password Recovery Request",
            "html" => $html_content,
            "category" => "Authentication"
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Api-Token: ' . MAILTRAP_API_TOKEN,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 || $http_code === 202) {
            return ['success' => true];
        } else {
            return [
                'success' => false, 
                'error' => 'API Error (Status: ' . $http_code . '). Response: ' . $response
            ];
        }
    }
}
