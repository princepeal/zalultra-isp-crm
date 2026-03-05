
## 🚀 Quick Start Guide

---

## 📚 Available SMS Services

| Service | Country | Type | File |
|---------|---------|------|------|
| BulkSMSBD | Bangladesh | SMS | `BulkSMSBDService.php` |
| SMS.NET.BD | Bangladesh | SMS | `SmsNetBDService.php` |
| BongoSMS | Bangladesh | SMS | `BongoSmsService.php` |
| Telenor | Pakistan | SMS | `TelenorSmsService.php` |
| MyAPI.pk | Pakistan | SMS | `MyApiPkService.php` |
| WASend.pk | Pakistan | WhatsApp | `WASendPKService.php` |
| UK SMS Gateway | UK | SMS | `UkSmsGatewayService.php` |
| Whapi | Global | WhatsApp | `WhapiService.php` |
| WhatsAPI Shop | Global | WhatsApp | `WhatsAPIShopService.php` |
| WaApiHub | Global | WhatsApp | `WaApiHubServices.php` |
| OnCloud API | Global | SMS | `OnCloudApiService.php` |
| BD SmartPay | Bangladesh | SMS | `BDSmartPayService.php` |

---

### Adding a New SMS Gateway

1. **Copy the example template:**
   ```bash
   cp notification/sms/Example/ZalUltraExampleSMSFile.php notification/sms/YourProviderService.php
   ```

2. **Modify the class:**
   ```php
   <?php
   
   use App\Gateways\SMS\CustomSmsServices;
   
   class YourProviderService extends CustomSmsServices
   {
       private $deliverySuccessCode = "000";  // Your API success code
       private $smsAPIName = "YourProviderService";

       protected function sendCustomSMS($smsData, $smsAttributes): array
       {
           // Build your API URL
           $apiUrl = 'https://your-sms-api.com/send';
           $params = [
               'api_key' => $smsAttributes['api_key'],
               'phone' => $smsData['phone'],
               'message' => $smsData['message']
           ];
           
           // Make cURL request
           $ch = curl_init();
           curl_setopt($ch, CURLOPT_URL, $apiUrl . '?' . http_build_query($params));
           curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
           $response = curl_exec($ch);
           $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
           curl_close($ch);
           
           // Return required format
           $success = ($httpCode == 200);
           return [
               'status' => $success,
               'status_code' => $success ? 'SUCCESS' : 'FAILED',
               'status_message' => $success ? 'SMS Sent' : 'SMS Failed'
           ];
       }
   }
   ```

3. **Configure in Zal Ultra:**
   - Go to **Settings → SMS Settings**
   - Select **Custom SMS** as gateway
   - Enter your service class name