<?php

namespace App\CustomSms;

/**
 * WhatsAPI Shop WhatsApp Service
 * API: https://whatsapi.shop/textapi.php?apikey={apikey}&instance={instance}&type=text&number={number}&message={message}
 */

use App\Gateways\SMS\CustomSmsServices;
use Illuminate\Support\Facades\Log;

class WhatsAPIShopService extends CustomSmsServices
{
    /**
     * Required attributes for this SMS service
     * These will be displayed in the settings UI
     */
    public static array $requiredAttributes = [
        'apikey' => 'API Key',
        'instance' => 'WhatsApp Instance ID',
    ];

    private $deliverySuccessCode = "success";

    //change to your own sms api name
    private $smsAPIName = "WhatsAPI Shop";

    /**
     * Send SMS - Write your own cURL code here
     */
    protected function sendCustomSMS($smsData, $smsAttributes): array
    {
        // ALWAYS log entry to track if method is being called
        Log::info('WhatsAPIShopService: sendCustomSMS called', [
            'phone' => $smsData['phone'] ?? 'MISSING',
            'message_length' => isset($smsData['message']) ? strlen($smsData['message']) : 0,
            'sms_attributes_keys' => array_keys($smsAttributes),
            'timestamp' => now()->toDateTimeString()
        ]);

        // Validate required parameters
        if (!isset($smsAttributes['apikey'])) {
            Log::error('WhatsAPIShopService: Missing apikey in smsAttributes');
            return [
                'status' => false,
                'status_code' => 'MISSING_APIKEY',
                'status_message' => 'Missing API key in SMS settings'
            ];
        }

        if (!isset($smsAttributes['instance'])) {
            Log::error('WhatsAPIShopService: Missing instance in smsAttributes');
            return [
                'status' => false,
                'status_code' => 'MISSING_INSTANCE',
                'status_message' => 'Missing instance ID in SMS settings'
            ];
        }

        // Build the API URL and parameters
        $apiUrl = 'https://whatsapi.shop/textapi.php';
        $params = [
            'apikey' => $smsAttributes['apikey'], // API Key from settings
            'instance' => $smsAttributes['instance'], // WhatsApp Instance ID from settings
            'type' => 'text',
            'number' => $smsData['phone'],
            'message' => urlencode($smsData['message']) // URL encode the message as per documentation
        ];

        // Log the request details
        Log::info('WhatsAPIShopService: Making API request', [
            'url' => $apiUrl,
            'params' => array_merge($params, ['apikey' => '***HIDDEN***']), // Hide API key in logs
            'phone' => $smsData['phone']
        ]);

        // Build the full URL with parameters
        $url = $apiUrl . '?' . http_build_query($params);

        // Initialize cURL and set options
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // As per API documentation
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // As per API documentation
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // ALWAYS log the response for debugging
        Log::info('WhatsAPIShopService: API Response received', [
            'raw_response' => $response,
            'http_code' => $httpCode,
            'phone' => $smsData['phone'],
            'curl_error' => $curlError ?: 'No cURL error',
            'response_length' => $response ? strlen($response) : 0,
            'timestamp' => now()->toDateTimeString()
        ]);

        ######## DO NOT CHANGE ANYTHING BELOW HERE ########
        ######## DO NOT CHANGE ANYTHING BELOW HERE ########
        ######## DO NOT CHANGE ANYTHING BELOW HERE ########

        // Enhanced logging with more details (only if logging is enabled)
        if (isset($smsAttributes['sms_log_status']) && $smsAttributes['sms_log_status'] === 1) {
            Log::info($this->smsAPIName . ' WhatsApp Response', [
                'raw_response' => $response,
                'http_code' => $httpCode,
                'phone' => $smsData['phone'],
                'curl_error' => $curlError ?: 'No cURL error',
                'timestamp' => now()->toDateTimeString()
            ]);
        }

        // Handle cURL errors first
        if ($curlError) {
            Log::error('WhatsAPIShopService: cURL Error', [
                'error' => $curlError,
                'phone' => $smsData['phone'],
                'url' => $url
            ]);
            return [
                'status' => false,
                'status_code' => 'CURL_ERROR',
                'status_message' => 'Network error: ' . $curlError
            ];
        }

        // Check if WhatsApp message was sent successfully (customize this for your API)
        $success = ($httpCode == 200 && (
            strpos($response, 'success') !== false ||
            strpos($response, 'sent') !== false ||
            strpos($response, 'delivered') !== false ||
            strpos($response, $this->deliverySuccessCode) !== false));

        // Log the final result
        Log::info('WhatsAPIShopService: Final result', [
            'success' => $success,
            'http_code' => $httpCode,
            'phone' => $smsData['phone'],
            'response_contains_success' => strpos($response, 'success') !== false,
            'response_preview' => substr($response, 0, 200)
        ]);

        // Return simple array (REQUIRED FORMAT)
        return [
            'status' => $success, // true/false
            'status_code' => $success ? 'SUCCESS' : 'FAILED', // any string
            'status_message' => $success ? 'WhatsApp Message Sent Successfully' : 'WhatsApp Message Failed' // any string
        ];
    }
}

/**
 * Usage: Set gateway_name = 'custom_sms', sms_service_name = 'WhatsAPIShopService'
 * Required settings: apikey, instance
 * Example: apikey = 'aaaa', instance = 'dddd'
 */
