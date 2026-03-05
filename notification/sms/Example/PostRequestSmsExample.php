<?php

/**
 * Example POST Request SMS Service Implementation
 * 
 * This example shows how to implement a custom SMS service that uses POST requests.
 * Copy this file and modify it according to your SMS API requirements.
 * 
 * Steps to customize:
 * 1. Change the class name
 * 2. Update getRequiredSmsAttributes() with your API's required fields
 * 3. Modify sendCustomSMS() to match your API's request format
 * 4. Adjust success detection logic if needed
 */

use App\Gateways\SMS\CustomSmsServices;
use Illuminate\Support\Facades\Log;

class PostRequestSmsExampleTemplate extends CustomSmsServices
{
    /**
     * Define what credentials/settings your SMS API needs
     * These will be validated automatically before sending SMS
     */
    protected function getRequiredSmsAttributes(): array
    {
        return [
            'api_key',      // Your SMS API key
            'sender_id',    // Sender ID or name
            'base_url'      // Base URL of your SMS API
        ];
    }

    /**
     * Your custom SMS implementation
     * This is where you integrate with your specific SMS API
     */
    protected function sendCustomSMS($smsData, $smsAttributes): array
    {
        // Build your API endpoint URL
        $apiUrl = $smsAttributes['base_url'] . '/api/sms/send';
        
        // Prepare the payload according to your API documentation
        $payload = [
            'api_key' => $smsAttributes['api_key'],
            'sender_id' => $smsAttributes['sender_id'],
            'phone' => $smsData['phone'],
            'message' => $smsData['message'],
            // Add any other fields your API requires
        ];

        // Make the HTTP request using the built-in helper
        $result = $this->makeHttpRequest($apiUrl, 'POST', $payload);

        // Handle cURL errors
        if (!empty($result['error'])) {
            Log::error("Custom SMS API Error: " . $result['error']);
            return [
                'success' => false,
                'response_code' => 'CURL_ERROR',
                'raw_response' => $result['error']
            ];
        }

        // Parse the response (adjust based on your API's response format)
        $responseArray = json_decode($result['response'], true);

        // Determine if the SMS was sent successfully
        // Customize this logic based on your API's response format
        $success = isset($responseArray['status']) && $responseArray['status'] === 'success';
        
        // Return the standardized response format
        return [
            'success' => $success,
            'response_code' => $responseArray['code'] ?? ($success ? 'SUCCESS' : 'FAILED'),
            'raw_response' => $responseArray
        ];
    }

    /**
     * Optional: Custom success detection logic
     * Override this if your API has different success indicators
     */
    protected function isSuccessResponse($response): bool
    {
        // Example: Some APIs might return success with specific codes
        return $response['success'] === true && 
               isset($response['raw_response']['message_id']);
    }

    /**
     * Optional: If you need additional data fields beyond the standard ones
     * Uncomment and modify if needed
     */
    // protected function getAdditionalRequiredSmsData(): array
    // {
    //     return ['priority', 'campaign_id']; // Example additional fields
    // }
}

/**
 * =============================================================================
 * ADVANCED POST REQUEST EXAMPLE WITH JSON AND HEADERS
 * =============================================================================
 */

class AdvancedPostSmsExampleTemplate extends CustomSmsServices
{
    protected function getRequiredSmsAttributes(): array
    {
        return [
            'api_token',    // Bearer token for authentication
            'sender_name',  // Sender name
            'base_url'      // API base URL
        ];
    }

    protected function sendCustomSMS($smsData, $smsAttributes): array
    {
        $apiUrl = $smsAttributes['base_url'] . '/v2/messages';
        
        // Prepare JSON payload
        $payload = json_encode([
            'from' => $smsAttributes['sender_name'],
            'to' => $smsData['phone'],
            'text' => $smsData['message'],
            'type' => 'sms'
        ]);

        // Set custom headers for JSON API with authentication
        $headers = [
            'Authorization: Bearer ' . $smsAttributes['api_token'],
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        $result = $this->makeHttpRequest($apiUrl, 'POST', $payload, $headers);

        if (!empty($result['error'])) {
            Log::error("Advanced SMS API Error: " . $result['error']);
            return [
                'success' => false,
                'response_code' => 'CURL_ERROR',
                'raw_response' => $result['error']
            ];
        }

        $responseArray = json_decode($result['response'], true);

        // Success based on HTTP status code and response content
        $success = $result['http_code'] === 200 && isset($responseArray['message_id']);

        return [
            'success' => $success,
            'response_code' => $responseArray['status'] ?? ($success ? 'SUCCESS' : 'FAILED'),
            'raw_response' => $responseArray
        ];
    }
}
