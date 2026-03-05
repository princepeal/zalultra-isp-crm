<?php

/**
 * WaAPI HUB WhatsApp Messaging Service
 * API: https://waapihub.shop/create-message?appkey=APP-KEY&authkey=AUTH-KEY&to={phone}&message={message}
 */

use App\Gateways\SMS\CustomSmsServices;

class WaApiHubServices extends CustomSmsServices
{
    /**
     * Required attributes for this SMS service
     * These will be displayed in the settings UI
     */
    public static array $requiredAttributes = [
        'appkey' => 'App Key',
        'authkey' => 'Auth Key',
    ];

    private $smsAPIName = "WaApi Hub";
    private $deliverySuccessCode = "200";

    protected function sendCustomSMS($smsData, $smsAttributes): array
    {
        // Initialize cURL
        $ch = curl_init();

        // Set cURL options for POST request
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://waapihub.shop/create-message',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => [
                'appkey'  => $smsAttributes['appkey'],
                'authkey' => $smsAttributes['authkey'],
                'to'      => $smsData['phone'],
                'message' => $smsData['message'],
                'sandbox' => $smsAttributes['sandbox'] ?? 'false'
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);



        ######## DO NOT CHANGE ANYTHING BELOW HERE ########
        ######## DO NOT CHANGE ANYTHING BELOW HERE ########
        ######## DO NOT CHANGE ANYTHING BELOW HERE ########

        // Enhanced logging with more details (only if logging is enabled)
        if (isset($smsAttributes['sms_log_status']) && $smsAttributes['sms_log_status'] === 'true') {
            Log::info($this->smsAPIName . ' Response', [
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
            strpos($response, 'delivered') !== false
        ));

        // Return simple array (REQUIRED FORMAT)
        return [
            'status' => $success, // true/false
            'status_code' => $success ? 'SUCCESS' : 'FAILED', // any string
            'status_message' => $success ? 'Message Sent Successfully' : 'Message Failed' // any string
        ];
    }
}

/**
 * Usage:
 * gateway_name = 'custom_sms'
 * sms_service_name = 'WaApiHubServices'
 * Required settings: appkey, authkey, sandbox (optional)
 * Example: appkey = 'xxx', authkey = 'yyy', sandbox = 'false'
 */
