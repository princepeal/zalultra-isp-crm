<?php

namespace App\CustomSms;

/**
 * WhAPI SMS Service
 * API: https://whapi.shop/api/send?number={phone}&type=text&message={message}&instance_id=6894F29EC47E2&access_token=688bbc76c6e38
 */

use App\Gateways\SMS\CustomSmsServices;
use Illuminate\Support\Facades\Log;

class WhapiService extends CustomSmsServices
{
    /**
     * Required attributes for this SMS service
     * Credentials are hardcoded - no attributes needed from settings
     */
    public static array $requiredAttributes = [];

    // Hardcoded credentials - no need for smsAttributes
    private $instanceId = '';
    private $accessToken = '';
    private $deliverySuccessCode = "000";

    /**
     * Send SMS - WhAPI integration with hardcoded credentials
     */
    protected function sendCustomSMS($smsData, $smsAttributes): array
    {
        // Build the API URL and parameters
        $apiUrl = 'https://whapi.shop/api/send';
        $params = [
            'number' => $smsData['phone'],
            'type' => 'text',
            'message' => $smsData['message'],
            'instance_id' => $this->instanceId,
            'access_token' => $this->accessToken
        ];

        // Build the full URL with parameters
        $url = $apiUrl . '?' . http_build_query($params);

        // Initialize cURL and set options
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Enhanced logging with more details
        Log::info('WhAPI SMS Response', [
            'raw_response' => $response,
            'http_code' => $httpCode,
            'phone' => $smsData['phone'],
            'curl_error' => $curlError ?: 'No cURL error',
            'timestamp' => now()->toDateTimeString()
        ]);

        // Check if SMS was sent successfully (customize this for your API)
        $success = ($httpCode == 200 && (
            strpos($response, 'success') !== false ||
            strpos($response, 'sent') !== false ||
            strpos($response, 'delivered') !== false ||
            strpos($response, $this->deliverySuccessCode) !== false));

        // Return simple array (REQUIRED FORMAT)
        return [
            'status' => $success, // true/false
            'status_code' => $success ? 'SUCCESS' : 'FAILED', // any string
            'status_message' => $success ? 'WhatsApp Message Sent Successfully' : 'WhatsApp Message Failed' // any string
        ];
    }
}

/**
 * Usage: Set gateway_name = 'custom_sms', sms_service_name = 'WhapiService'
 * No additional settings required - credentials are hardcoded in the file
 * Instance ID: 
 * Access Token: 
 */
