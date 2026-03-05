<?php

namespace App\CustomSms;

use App\Gateways\SMS\CustomSmsServices;
use Illuminate\Support\Facades\Log;

class OnCloudApiService extends CustomSmsServices
{
    /**
     * Required attributes for this SMS service
     * These will be displayed in the settings UI
     */
    public static array $requiredAttributes = [
        'token' => 'API Token',
    ];

    private $deliverySuccessCode = "success"; // OnCloud returns status = success
    private $smsAPIName = "On Cloud Api";

    /**
     * Send WhatsApp message via OnCloud API
     *
     * @param array $smsData        Contains 'phone' and 'message'
     * @param array $smsAttributes  Contains 'token'
     * @return array
     */
    protected function sendCustomSMS($smsData, $smsAttributes): array
    {
        $apiUrl = "https://apps.oncloudapi.com/api/send-message";

        // Prepare POST data
        $params = [
            "token"   => $smsAttributes['token'], // OnCloud API token
            "number"  => $smsData['phone'],       // WhatsApp number with country code
            "message" => $smsData['message'],     // Text message
            "type"    => "text"                   // Message type
        ];

        // Initialize cURL
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

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
