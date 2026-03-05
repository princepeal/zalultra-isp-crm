<?php

/**
 * Example GET Request SMS Service Implementation
 * 
 * This example shows how to implement a custom SMS service that uses GET requests.
 * Copy this file and modify it according to your SMS API requirements.
 * 
 * Steps to customize:
 * 1. Change the class name
 * 2. Update getRequiredSmsAttributes() with your API's required fields
 * 3. Modify sendCustomSMS() to match your API's request format
 * 4. Adjust success detection logic based on your API's response format
 */

use App\Gateways\SMS\CustomSmsServices;
use Illuminate\Support\Facades\Log;

class GetRequestSmsExampleTemplate extends CustomSmsServices
{
    /**
     * Define what credentials/settings your SMS API needs
     * These will be validated automatically before sending SMS
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
        // Example 1: Simple text response
        $success = (strpos(strtolower($response), 'success') !== false) || 
                   (strpos($response, '200') !== false) ||
                   (strpos(strtolower($response), 'sent') !== false);
        
        // Example 2: If your API returns JSON in GET response
        // $responseArray = json_decode($response, true);
        // $success = isset($responseArray['status']) && $responseArray['status'] === 'OK';
        
        return [
            'success' => $success,
            'response_code' => $success ? 'SUCCESS' : 'FAILED',
            'raw_response' => $response
        ];
    }
}

/**
 * =============================================================================
 * ADVANCED GET REQUEST EXAMPLE WITH CUSTOM HEADERS
 * =============================================================================
 */

class AdvancedGetSmsExampleTemplate extends CustomSmsServices
{
    protected function getRequiredSmsAttributes(): array
    {
        return [
            'api_key',      // API key for authentication
            'sender_id',    // Sender ID
            'base_url'      // API base URL
        ];
    }

    protected function sendCustomSMS($smsData, $smsAttributes): array
    {
        $apiUrl = $smsAttributes['base_url'] . '/api/v1/send';
        
        // Prepare GET parameters
        $params = [
            'key' => $smsAttributes['api_key'],
            'from' => $smsAttributes['sender_id'],
            'to' => $smsData['phone'],
            'message' => urlencode($smsData['message']), // URL encode for GET
            'format' => 'json'
        ];

        // Custom headers (some GET APIs still require headers)
        $headers = [
            'User-Agent: ZalUltra-SMS-Client/1.0',
            'Accept: application/json'
        ];

        $result = $this->makeHttpRequest($apiUrl, 'GET', $params, $headers);

        if (!empty($result['error'])) {
            Log::error("Advanced GET SMS API Error: " . $result['error']);
            return [
                'success' => false,
                'response_code' => 'CURL_ERROR',
                'raw_response' => $result['error']
            ];
        }

        // Parse JSON response
        $responseArray = json_decode($result['response'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // If not JSON, treat as text response
            $response = $result['response'];
            $success = strpos(strtolower($response), 'success') !== false;
            $responseCode = $success ? 'SUCCESS' : 'FAILED';
        } else {
            // Handle JSON response
            $success = isset($responseArray['status']) && 
                      in_array(strtolower($responseArray['status']), ['success', 'ok', 'sent']);
            $responseCode = $responseArray['code'] ?? ($success ? 'SUCCESS' : 'FAILED');
        }

        return [
            'success' => $success,
            'response_code' => $responseCode,
            'raw_response' => $responseArray ?? $result['response']
        ];
    }
}

/**
 * =============================================================================
 * XML RESPONSE GET REQUEST EXAMPLE
 * =============================================================================
 */

class XmlGetSmsExampleTemplate extends CustomSmsServices
{
    protected function getRequiredSmsAttributes(): array
    {
        return [
            'username',
            'password',
            'sender_id',
            'base_url'
        ];
    }

    protected function sendCustomSMS($smsData, $smsAttributes): array
    {
        $apiUrl = $smsAttributes['base_url'] . '/sendsms';
        
        $params = [
            'user' => $smsAttributes['username'],
            'pass' => $smsAttributes['password'],
            'sid' => $smsAttributes['sender_id'],
            'msisdn' => $smsData['phone'],
            'msg' => $smsData['message'],
            'fl' => '0' // Response format flag
        ];

        $result = $this->makeHttpRequest($apiUrl, 'GET', $params);

        if (!empty($result['error'])) {
            Log::error("XML SMS API Error: " . $result['error']);
            return [
                'success' => false,
                'response_code' => 'CURL_ERROR',
                'raw_response' => $result['error']
            ];
        }

        // Parse XML response
        $response = $result['response'];
        
        // Simple XML parsing for common SMS API responses
        $success = false;
        $responseCode = 'FAILED';
        
        if (strpos($response, '<status>') !== false) {
            // Extract status from XML
            preg_match('/<status>(.*?)<\/status>/', $response, $matches);
            if (isset($matches[1])) {
                $status = strtolower(trim($matches[1]));
                $success = in_array($status, ['success', 'ok', '1', 'sent']);
                $responseCode = $matches[1];
            }
        } else {
            // Fallback to text parsing
            $success = strpos(strtolower($response), 'success') !== false ||
                      strpos($response, '1701') !== false; // Common success code
        }

        return [
            'success' => $success,
            'response_code' => $responseCode,
            'raw_response' => $response
        ];
    }
}
