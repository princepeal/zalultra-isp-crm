<?php

/**
 * =============================================================================
 * CUSTOM SMS SERVICE EXAMPLES FOR CLIENT IMPLEMENTATION
 * =============================================================================
 * 
 * This file contains example implementations showing how to create custom SMS services.
 * 
 * INSTRUCTIONS FOR CLIENTS:
 * 1. Copy one of the example classes below
 * 2. Rename the class to match your SMS provider
 * 3. Update the getRequiredSmsAttributes() method with your API credentials
 * 4. Modify the sendCustomSMS() method to integrate with your SMS API
 * 5. Adjust success detection logic if needed
 * 
 * The base CustomSmsServices class handles all validation, subscriber updates,
 * and logging automatically. You only need to implement the SMS API integration.
 * 
 * IMPORTANT: 
 * - Do not modify the base class methods (validateInputs, processResponse, etc.)
 * - Always return the required response format from sendCustomSMS()
 * - Use the provided makeHttpRequest() helper for HTTP calls
 */

use App\Gateways\SMS\CustomSmsServices;
use Illuminate\Support\Facades\Log;

/**
 * =============================================================================
 * SIMPLE POST REQUEST EXAMPLE
 * =============================================================================
 */

/**
 * Example: Simple POST Request SMS Service
 * Use this template for SMS APIs that accept POST requests with form data
 */
class MyPostSmsServiceExample extends CustomSmsServices
{
    /**
     * Define what credentials your SMS API needs
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
}

/**
 * =============================================================================
 * SIMPLE GET REQUEST EXAMPLE  
 * =============================================================================
 */

/**
 * Example: Simple GET Request SMS Service
 * Use this template for SMS APIs that accept GET requests with URL parameters
 */
class MyGetSmsServiceExample extends CustomSmsServices
{
    /**
     * Define what credentials your SMS API needs
     */
    protected function getRequiredSmsAttributes(): array
    {
        return [
            'username',     // API username
            'password',     // API password
            'sender_id',    // Sender ID
            'base_url'      // Base URL of your SMS API
        ];
    }

    /**
     * Your custom SMS implementation for GET request APIs
     */
    protected function sendCustomSMS($smsData, $smsAttributes): array
    {
        // Build your API endpoint URL
        $apiUrl = $smsAttributes['base_url'] . '/sms/send';
        
        // Prepare URL parameters for GET request
        $params = [
            'username' => $smsAttributes['username'],
            'password' => $smsAttributes['password'],
            'sender' => $smsAttributes['sender_id'],
            'to' => $smsData['phone'],
            'text' => $smsData['message'],
            'type' => 'text'
            // Add any other parameters your API requires
        ];

        // Make the GET request using the built-in helper
        $result = $this->makeHttpRequest($apiUrl, 'GET', $params);

        // Handle cURL errors
        if (!empty($result['error'])) {
            Log::error("Custom SMS API Error: " . $result['error']);
            return [
                'success' => false,
                'response_code' => 'CURL_ERROR',
                'raw_response' => $result['error']
            ];
        }

        // For GET APIs, response might be simple text, XML, or JSON
        $response = $result['response'];
        
        // Parse response based on your API's format
        // Example: Simple text response
        $success = (strpos(strtolower($response), 'success') !== false) || 
                   (strpos($response, '200') !== false) ||
                   (strpos(strtolower($response), 'sent') !== false);
        
        return [
            'success' => $success,
            'response_code' => $success ? 'SUCCESS' : 'FAILED',
            'raw_response' => $response
        ];
    }
}