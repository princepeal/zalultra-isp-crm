<?php

/**
 * BD Smart Pay SMS Service
 * API: http://bdsmartpay.com/sms/smsapi.php?username={username}&password={password}&mobile={phone}&sms_title={sms_title}&message={message}
 */

use App\Gateways\SMS\CustomSmsServices;

class ZalUltraExampleSMSFile extends CustomSmsServices
{
    // you can define your own variables here which are associated with sms parameters
    // private $username
    // private $password
    // private $token
    // private $mask
    // private $sms_log_status

    // Hardcoded credentials - no need for smsAttributes
    private $deliverySuccessCode = "000";

    //change to your own sms api name
    private $smsAPIName = "ZalUltraExampleSMSFile";

    /**
     * Send SMS - Write your own cURL code here
     */
    protected function sendCustomSMS($smsData, $smsAttributes): array
    {
        // Build the API URL and parameters
        $apiUrl = 'http://your-api-url.com/sms/smsapi.php';
        $params = [
            'username' => $smsAttributes['username'],
            'password' => $smsAttributes['password'],
            'sms_title' => $smsAttributes['sms_title'],
            'mobile' => $smsData['phone'],
            'message' => $smsData['message']
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

        ######## DO NOT CHANGE ANYTHING BELOW HERE ########
        ######## DO NOT CHANGE ANYTHING BELOW HERE ########
        ######## DO NOT CHANGE ANYTHING BELOW HERE ########

        // Enhanced logging with more details (only if logging is enabled)
        if (isset($smsAttributes['sms_log_status']) && $smsAttributes['sms_log_status'] === 1) {
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

/**
 * Usage: Set gateway_name = 'custom_sms', sms_service_name = 'ZalUltraExampleSMSFile'
 * Required settings: username, password, sms_title
 * Example: username = 'username', password = 'password', sms_title = 'title'
 */
