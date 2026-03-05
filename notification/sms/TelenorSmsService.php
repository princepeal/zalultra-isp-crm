<?php

namespace App\CustomSms;

/**
 * Telenor Corporate SMS Service (Pakistan)
 * 
 * Authentication API: https://telenorcsms.com.pk:27677/corporate_sms2/api/auth.jsp?msisdn={msisdn}&password={password}
 * Send SMS API: https://telenorcsms.com.pk:27677/corporate_sms2/api/sendsms.jsp?session_id={session_id}&to={phone}&text={message}&mask={mask}
 * 
 * Required smsAttributes:
 * - msisdn: Corporate account mobile number
 * - password: Account password
 * - mask: SMS sender mask/title
 * 
 * Optional smsAttributes:
 * - unicode: Set to 'true' for non-English messages (Urdu, etc.)
 */

use App\Gateways\SMS\CustomSmsServices;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TelenorSmsService extends CustomSmsServices
{
    /**
     * Required attributes for this SMS service
     * These will be displayed in the settings UI
     */
    public static array $requiredAttributes = [
        'msisdn' => 'Corporate Account Number',
        'password' => 'Account Password',
        'mask' => 'SMS Sender Mask/Title',
    ];

    private $smsAPIName = "Telenor Corporate SMS";
    
    // Session expires after 30 minutes of inactivity
    private $sessionCacheTTL = 25; // 25 minutes (safe margin before 30 min expiry)
    
    // API Base URL
    private $baseUrl = "https://telenorcsms.com.pk:27677/corporate_sms2/api";

    /**
     * Send SMS - Handles authentication and message sending
     */
    protected function sendCustomSMS($smsData, $smsAttributes): array
    {
        // Validate required attributes
        if (empty($smsAttributes['msisdn']) || empty($smsAttributes['password']) || empty($smsAttributes['mask'])) {
            return [
                'status' => false,
                'status_code' => 'CONFIG_ERROR',
                'status_message' => 'Missing Required Configuration: msisdn, password, or mask'
            ];
        }
        
        // Step 1: Get or create session ID
        $sessionId = $this->getSessionId($smsAttributes);
        
        if (!$sessionId) {
            return [
                'status' => false,
                'status_code' => 'AUTH_FAILED',
                'status_message' => 'Failed To Authenticate With Telenor SMS API. Check msisdn And Password.'
            ];
        }
        
        // Step 2: Send the SMS
        $result = $this->sendMessage($sessionId, $smsData, $smsAttributes);
        
        // If session expired (auth error during send), clear cache and retry once
        if (!$result['status'] && strpos($result['status_message'], 'Session') !== false) {
            $this->clearSessionCache($smsAttributes['msisdn']);
            $sessionId = $this->getSessionId($smsAttributes);
            if ($sessionId) {
                $result = $this->sendMessage($sessionId, $smsData, $smsAttributes);
            }
        }
        
        return $result;
    }
    
    /**
     * Clear session cache
     */
    private function clearSessionCache($msisdn): void
    {
        $cacheKey = 'telenor_sms_session_' . md5($msisdn);
        Cache::forget($cacheKey);
    }
    
    /**
     * Get session ID from cache or authenticate
     */
    private function getSessionId($smsAttributes): ?string
    {
        $cacheKey = 'telenor_sms_session_' . md5($smsAttributes['msisdn']);
        
        // Check if we have a cached session
        $sessionId = Cache::get($cacheKey);
        
        if ($sessionId) {
            return $sessionId;
        }
        
        // Authenticate and get new session ID
        $sessionId = $this->authenticate($smsAttributes);
        
        if ($sessionId) {
            // Cache the session ID for 25 minutes
            Cache::put($cacheKey, $sessionId, now()->addMinutes($this->sessionCacheTTL));
        }
        
        return $sessionId;
    }
    
    /**
     * Authenticate with Telenor API and get session ID
     */
    private function authenticate($smsAttributes): ?string
    {
        // Build URL exactly as per documentation
        // Format: https://telenorcsms.com.pk:27677/corporate_sms2/api/auth.jsp?msisdn=xxxx&password=xxx
        $authUrl = $this->baseUrl . '/auth.jsp?msisdn=' . urlencode($smsAttributes['msisdn']) 
                 . '&password=' . urlencode($smsAttributes['password']);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $authUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/xml',
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);
        
        // Always log authentication attempts for debugging
        Log::info($this->smsAPIName . ' Authentication Attempt', [
            'url' => preg_replace('/password=[^&]+/', 'password=***', $authUrl), // Hide password in logs
            'http_code' => $httpCode,
            'response' => $response,
            'curl_error' => $curlError ?: 'None',
            'curl_errno' => $curlErrno,
            'timestamp' => now()->toDateTimeString()
        ]);
        
        // Check for cURL errors first
        if ($curlErrno !== 0) {
            Log::error($this->smsAPIName . ' Authentication cURL Error', [
                'error' => $curlError,
                'errno' => $curlErrno
            ]);
            return null;
        }
        
        // Parse XML response
        if ($httpCode == 200 && $response) {
            // Try to parse XML
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response);
            
            if ($xml === false) {
                $xmlErrors = libxml_get_errors();
                libxml_clear_errors();
                Log::error($this->smsAPIName . ' XML Parse Error', [
                    'response' => $response,
                    'errors' => $xmlErrors
                ]);
                return null;
            }
            
            if ((string)$xml->response === 'OK') {
                $sessionId = (string)$xml->data;
                Log::info($this->smsAPIName . ' Authentication Successful', [
                    'session_id' => substr($sessionId, 0, 10) . '...' // Log partial session ID
                ]);
                return $sessionId;
            } else {
                // Log the error code from API
                Log::error($this->smsAPIName . ' Authentication Failed', [
                    'response_status' => (string)$xml->response,
                    'error_code' => (string)$xml->data,
                    'command' => (string)$xml->command
                ]);
            }
        }
        
        return null;
    }
    
    /**
     * Send SMS message using session ID
     */
    private function sendMessage($sessionId, $smsData, $smsAttributes): array
    {
        // Format phone number to 923xxxxxxxxx format
        $phone = $this->formatPhoneNumber($smsData['phone']);
        
        // Build URL exactly as per documentation
        // Format: https://telenorcsms.com.pk:27677/corporate_sms2/api/sendsms.jsp?session_id=xxxx&to=923xxxxxxxxx&text=xxxx&mask=xxxx
        $smsUrl = $this->baseUrl . '/sendsms.jsp?session_id=' . urlencode($sessionId)
                . '&to=' . urlencode($phone)
                . '&text=' . urlencode($smsData['message'])
                . '&mask=' . urlencode($smsAttributes['mask']);
        
        // Add unicode parameter for non-English messages
        if (isset($smsAttributes['unicode']) && $smsAttributes['unicode'] === 'true') {
            $smsUrl .= '&unicode=true';
        }
        
        // Add operator_id if specified
        if (isset($smsAttributes['operator_id']) && !empty($smsAttributes['operator_id'])) {
            $smsUrl .= '&operator_id=' . urlencode($smsAttributes['operator_id']);
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $smsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/xml',
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);
        
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

        // Parse XML response to check success
        $success = false;
        $messageId = null;
        $errorMessage = 'SMS Failed';
        
        if ($curlErrno !== 0) {
            $errorMessage = 'Connection Error: ' . $curlError;
        } elseif ($httpCode == 200 && $response) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response);
            
            if ($xml === false) {
                $errorMessage = 'Invalid Response From API';
            } elseif ((string)$xml->response === 'OK') {
                $success = true;
                $messageId = (string)$xml->data; // Message ID(s)
            } else {
                // Get error code from response
                $errorCode = (string)$xml->data;
                $errorMessage = 'SMS Failed. Error Code: ' . $errorCode;
                
                // Check if session expired
                if (strpos($errorCode, 'Session') !== false || $errorCode === '1' || $errorCode === '2') {
                    $errorMessage = 'Session Expired Or Invalid. Will Retry.';
                }
            }
            libxml_clear_errors();
        } else {
            $errorMessage = 'HTTP Error: ' . $httpCode;
        }

        // Return simple array (REQUIRED FORMAT)
        return [
            'status' => $success,
            'status_code' => $success ? 'SUCCESS' : 'FAILED',
            'status_message' => $success ? 'SMS Sent Successfully. Message ID: ' . $messageId : $errorMessage
        ];
    }
    
    /**
     * Format phone number to Pakistan format (923xxxxxxxxx)
     */
    private function formatPhoneNumber($phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If starts with 0, replace with 92
        if (substr($phone, 0, 1) === '0') {
            $phone = '92' . substr($phone, 1);
        }
        
        // If doesn't start with 92, add it
        if (substr($phone, 0, 2) !== '92') {
            $phone = '92' . $phone;
        }
        
        return $phone;
    }
}

/**
 * Usage: Set gateway_name = 'custom_sms', sms_service_name = 'TelenorSmsService'
 * 
 * Required smsAttributes:
 * - msisdn: Your corporate account mobile number
 * - password: Your account password
 * - mask: SMS sender mask/title
 * 
 * Optional smsAttributes:
 * - unicode: 'true' for non-English messages (Urdu, Arabic, etc.)
 * - operator_id: Specific operator ID (see Telenor documentation Appendix D)
 * 
 * Session Management:
 * - Session ID is cached for 25 minutes (auto-expires after 30 min inactivity)
 * - Re-authentication happens automatically when session expires
 * 
 * API Endpoints:
 * - Auth: https://telenorcsms.com.pk:27677/corporate_sms2/api/auth.jsp?msisdn=xxxx&password=xxx
 * - Send: https://telenorcsms.com.pk:27677/corporate_sms2/api/sendsms.jsp?session_id=xxxx&to=923xxxxxxxxx&text=xxxx&mask=xxxx
 * - Query: https://telenorcsms.com.pk:27677/corporate_sms2/api/querymsg.jsp?session_id=xxx&msg_id=xxxx
 */
