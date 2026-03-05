<?php

namespace App\CustomSms;

/**
 * SMS.NET.BD SMS Service
 * API: https://api.sms.net.bd/sendsms?api_key={YOUR_API_KEY}&msg={YOUR_MSG}&to=8801800000000,8801700000000&schedule=2021-10-13 16:00:52
 */

use App\Gateways\SMS\CustomSmsServices;
use Illuminate\Support\Facades\Log;

class SmsNetBDService extends CustomSmsServices
{
    /**
     * Required attributes for this SMS service
     * These will be displayed in the settings UI
     */
    public static array $requiredAttributes = [
        'api_key' => 'API Key',
    ];

    private $deliverySuccessCode = "000";

    /**
     * Send SMS - Write your own cURL code here
     */
    protected function sendCustomSMS($smsData, $smsAttributes): array
    {
        // Build the API URL and parameters
        $apiUrl = 'https://api.sms.net.bd/sendsms';
        $params = [
            'api_key' => $smsAttributes['api_key'],
            'msg' => $smsData['message'],
            'to' => $smsData['phone']
            // Note: schedule parameter is optional - not including it for immediate sending
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
        Log::info('SMS.NET.BD SMS Response', [
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
            'status_message' => $success ? 'SMS Sent Successfully' : 'SMS Failed' // any string
        ];
    }
}

/**
 * Usage: Set gateway_name = 'custom_sms', sms_service_name = 'SmsNetBDService'
 * Required settings: api_key
 * Example: api_key = 'your_api_key_here'
 */
