<?php

namespace App\CustomSms;

/**
 * WA Send PK SMS Service (WhatsApp)
 * API: http://wa.sendpk.com/api/send.php?api_key={api_key}&whatsapp_id={whatsapp_id}&template_id={template_id}&template_data={json_data}
 *
 * Template ID is extracted from the message using {template:id} format
 * Example message: "Dear {username}, your connection renewed. {template:103}"
 */

use App\Gateways\SMS\CustomSmsServices;
use Illuminate\Support\Facades\Log;

class WASendPKService extends CustomSmsServices
{
    /**
     * Required attributes for this SMS service
     * These will be displayed in the settings UI
     */
    public static array $requiredAttributes = [
        'api_key' => 'API Key',
        'whatsapp_id' => 'WhatsApp ID',
    ];

    private $deliverySuccessCode = "success";

    //change to your own sms api name
    private $smsAPIName = "WA Send PK";

    /**
     * Send SMS - Write your own cURL code here
     */
    protected function sendCustomSMS($smsData, $smsAttributes): array
    {
        // Build the API URL
        $apiUrl = 'http://wa.sendpk.com/api/send.php';

        // Extract template_id from message using {template:id} pattern
        $templateId = $this->extractTemplateId($smsData['message']);

        // Remove the template tag from the message
        $cleanMessage = $this->removeTemplateTag($smsData['message']);

        // Build template_data array
        // The message is split into template body placeholders
        // Format: [{"mobile":"phone","body":[{"type":"text","text":"value1"},{"type":"text","text":"value2"},...]}]
        $templateData = [
            [
                'mobile' => $smsData['phone'],
                'body' => $this->parseMessageToBody($cleanMessage)
            ]
        ];

        $params = [
            'api_key' => $smsAttributes['api_key'],
            'whatsapp_id' => $smsAttributes['whatsapp_id'],
            'template_id' => $templateId,
            'template_data' => json_encode($templateData)
        ];

        // Build the full URL with parameters
        $url = $apiUrl . '?' . http_build_query($params);

        // Log API request details BEFORE sending
        Log::info($this->smsAPIName . ' API Request', [
            'phone' => $smsData['phone'],
            'original_message' => $smsData['message'],
            'template_id' => $templateId,
            'parsed_variables' => $this->parseMessageToBody($cleanMessage),
            'template_data_json' => json_encode($templateData, JSON_PRETTY_PRINT),
            'full_api_url' => $url,
            'timestamp' => now()->toDateTimeString()
        ]);

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

        // Log API response AFTER receiving
        Log::info($this->smsAPIName . ' API Response', [
            'phone' => $smsData['phone'],
            'http_code' => $httpCode,
            'raw_response' => $response,
            'curl_error' => $curlError ?: null,
            'response_decoded' => json_decode($response, true),
            'timestamp' => now()->toDateTimeString()
        ]);

        ######## DO NOT CHANGE ANYTHING BELOW HERE ########
        ######## DO NOT CHANGE ANYTHING BELOW HERE ########
        ######## DO NOT CHANGE ANYTHING BELOW HERE ########

        // Enhanced logging with more details (only if logging is enabled)
        if (isset($smsAttributes['sms_log_status']) && $smsAttributes['sms_log_status'] === 1) {
            Log::info($this->smsAPIName . ' SMS Response', [
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

    /**
     * Extract template ID from message using {template:id} pattern
     *
     * @param string $message
     * @return string|null
     */
    private function extractTemplateId(string $message): ?string
    {
        // Match pattern like {template:103} or {template:64}
        if (preg_match('/\{template:(\d+)\}/i', $message, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Remove template tag from message
     *
     * @param string $message
     * @return string
     */
    private function removeTemplateTag(string $message): string
    {
        // Remove {template:id} pattern from message
        $cleanMessage = preg_replace('/\s*\{template:\d+\}\s*/i', ' ', $message);

        // Clean up extra spaces
        return trim(preg_replace('/\s+/', ' ', $cleanMessage));
    }

    /**
     * Parse message and extract WhatsApp template variables
     * Variables must be in format: {1:value}, {2:value}, {3:value}, {4:value}
     * These map to WhatsApp template placeholders {{1}}, {{2}}, {{3}}, {{4}}
     *
     * @param string $message
     * @return array
     */
    private function parseMessageToBody(string $message): array
    {
        $body = [];
        $variables = [];

        // Match pattern like {1:John}, {2:25-12-2025}, etc.
        // This extracts the position number and the value
        if (preg_match_all('/\{(\d+):([^}]+)\}/', $message, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $position = (int) $match[1];
                $value = trim($match[2]);
                $variables[$position] = $value;
            }
        }

        // Sort by position number to ensure correct order
        ksort($variables);

        // Build the body array in the correct order
        foreach ($variables as $value) {
            $body[] = [
                'type' => 'text',
                'text' => $value
            ];
        }

        return $body;
    }

    /**
     * Remove all variable tags from message (for logging purposes)
     *
     * @param string $message
     * @return string
     */
    private function cleanMessageForLog(string $message): string
    {
        // Remove {template:id} and {n:value} patterns
        $cleanMessage = preg_replace('/\{template:\d+\}/', '', $message);
        $cleanMessage = preg_replace('/\{\d+:[^}]+\}/', '', $cleanMessage);

        return trim(preg_replace('/\s+/', ' ', $cleanMessage));
    }
}

/**
 * WA Send PK SMS Service - Usage Guide
 * =====================================
 *
 * SETTINGS:
 * - gateway_name = 'custom_sms'
 * - sms_service_name = 'WASendPKService'
 * - api_key = 'your-api-key'
 * - whatsapp_id = 'your-whatsapp-id'
 *
 * MESSAGE FORMAT:
 * ---------------
 * Your Zal Ultra SMS template should include:
 * 1. WhatsApp template variables: {1:value}, {2:value}, {3:value}, {4:value}
 * 2. Template ID tag: {template:id}
 *
 * EXAMPLE:
 * --------
 * WhatsApp Template (ID: 103):
 *   "Hi {{1}}, this is to remind you of your upcoming scheduled payment.
 *    Date: {{2}}
 *    Account: {{3}}
 *    Amount: {{4}}
 *    Thank you and have a nice day."
 *
 * Zal Ultra SMS Template:
 *   "{1:{username}}{2:{expirytime}}{3:{invoiceid}}{4:{amount}}{template:103}"
 *
 * This will send to WhatsApp API:
 *   - {{1}} = username value
 *   - {{2}} = expirytime value
 *   - {{3}} = invoiceid value
 *   - {{4}} = amount value
 *
 * The actual SMS text doesn't matter - only the variable values are sent
 * to match the APPROVED WhatsApp template.
 */

