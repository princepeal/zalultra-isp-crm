<?php

namespace App\CustomSms;

use App\Gateways\SMS\CustomSmsServices;
use Illuminate\Support\Facades\Log;

class MyApiPkService extends CustomSmsServices
{
    private $deliverySuccessCode = "success";
    private $smsAPIName = "MyApi.pk SMS Service";
    private $baseUrl = 'https://myapi.pk/api/send.php';

    /**
     * Send SMS via MyApi.pk
     *
     * @param array $smsData        Contains 'phone' and 'message'
     * @param array $smsAttributes  Contains 'api_key' and other parameters
     * @return array
     */
    protected function sendCustomSMS($smsData, $smsAttributes): array
    {
        // Prepare query parameters
        $params = [
            'api_key' => $smsAttributes['api_key'] ?? '',
            'mobile'  => $smsData['phone'],
            'message' => $smsData['message']
        ];

        // Build the full URL with query parameters
        $url = $this->baseUrl . '?' . http_build_query($params);

        // Initialize cURL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, // Set to true in production with valid SSL
            CURLOPT_TIMEOUT => 10
        ]);

        // Execute and capture response
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        ######## DO NOT CHANGE ANYTHING BELOW HERE ########
        ######## DO NOT CHANGE ANYTHING BELOW HERE ########
        ######## DO NOT CHANGE ANYTHING BELOW HERE ########

        // Enhanced logging with more details (only if logging is enabled)
        if (isset($smsAttributes['sms_log_status']) && $smsAttributes['sms_log_status'] === 'true') {
            Log::info($this->smsAPIName . 'SMS Response', [
                'raw_response' => $response,
                'http_code' => $httpCode,
                'phone' => $smsData['phone'],
                'curl_error' => $curlError ?: 'No cURL error',
                'timestamp' => now()->toDateTimeString()
            ]);
        }

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
