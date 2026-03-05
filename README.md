<p align="center">
  <img src="https://zalultra.io/assets/media/logos/zal-ultra-logo.png" alt="Zal Ultra Logo" width="200"/>
</p>

<h1 align="center">Zal Ultra ISP CRM</h1>

<p align="center">
  <strong>The Most Powerful ISP Management System for Internet Service Providers</strong>
</p>

<p align="center">
  <a href="https://zalultra.io">Website</a> •
  <a href="https://docs.zalultra.io">Documentation</a> •
  <a href="https://www.facebook.com/zalultra">Facebook</a> •
  <a href="https://www.youtube.com/@ZalUltra">YouTube</a> •
  <a href="https://teams.microsoft.com/l/team/19%3AzalultraSupport%40thread.tacv2/conversations?groupId=zal-ultra-support">MS Teams</a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Version-3.x-blue?style=for-the-badge" alt="Version"/>
  <img src="https://img.shields.io/badge/Clients-500+-green?style=for-the-badge" alt="Clients"/>
  <img src="https://img.shields.io/badge/Countries-15+-orange?style=for-the-badge" alt="Countries"/>
  <img src="https://img.shields.io/badge/License-Proprietary-red?style=for-the-badge" alt="License"/>
</p>

---

## 🌟 About Zal Ultra

**Zal Ultra** is a comprehensive ISP (Internet Service Provider) CRM and billing management system developed by **[Onezeroart LLC](https://onezeroart.com)**. It provides end-to-end solutions for managing subscribers, billing, RADIUS authentication, MikroTik integration, payment gateways, and much more.



**Key Features:**
- 🔐 **RADIUS Authentication** - PPPoE, Hotspot, DHCP management
- 💰 **Billing & Invoicing** - Automated billing with multiple payment gateways
- 📊 **Real-time Monitoring** - Live bandwidth graphs and usage statistics
- 🔄 **Auto-Renew System** - Intelligent subscription renewal
- 👥 **Multi-level Reseller** - Admin → Reseller → Sub-reseller → Retailer hierarchy
- 📱 **SMS/Email/WhatsApp** - Automated notifications for all events
- 🌐 **MikroTik Integration** - Hotspot, PPPoE, API integration
- 📈 **Comprehensive Reports** - Sales, revenue, usage analytics

---

## 🚀 Quick Start Guide

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

### Customizing Email Templates

1. Navigate to `notification/mail/templates/`
2. Edit `notification.php` with your branding
3. Upload to your Zal Ultra installation

### Customizing Hotspot Pages

1. Navigate to `mikrotik/hotspot/`
2. Modify `login.html` and `status.html`
3. Upload to your MikroTik router's hotspot directory

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

## 🌍 Our Global Presence

<p align="center">
  <img src="https://img.shields.io/badge/Bangladesh-🇧🇩-success?style=flat-square" alt="Bangladesh"/>
  <img src="https://img.shields.io/badge/Pakistan-🇵🇰-success?style=flat-square" alt="Pakistan"/>
  <img src="https://img.shields.io/badge/India-🇮🇳-success?style=flat-square" alt="India"/>
  <img src="https://img.shields.io/badge/Nepal-🇳🇵-success?style=flat-square" alt="Nepal"/>
  <img src="https://img.shields.io/badge/Sri Lanka-🇱🇰-success?style=flat-square" alt="Sri Lanka"/>
  <img src="https://img.shields.io/badge/UAE-🇦🇪-success?style=flat-square" alt="UAE"/>
  <img src="https://img.shields.io/badge/Saudi Arabia-🇸🇦-success?style=flat-square" alt="Saudi Arabia"/>
  <img src="https://img.shields.io/badge/UK-🇬🇧-success?style=flat-square" alt="UK"/>
  <img src="https://img.shields.io/badge/USA-🇺🇸-success?style=flat-square" alt="USA"/>
  <img src="https://img.shields.io/badge/Nigeria-🇳🇬-success?style=flat-square" alt="Nigeria"/>
  <img src="https://img.shields.io/badge/Kenya-🇰🇪-success?style=flat-square" alt="Kenya"/>
  <img src="https://img.shields.io/badge/South Africa-🇿🇦-success?style=flat-square" alt="South Africa"/>
</p>

**500+ ISPs** across **15+ countries** trust Zal Ultra for their business operations.

---

## 📞 Contact & Support

<table>
  <tr>
    <td align="center">
      <a href="https://onezeroart.com">
        <img src="https://img.shields.io/badge/Website-onezeroart.com-blue?style=for-the-badge&logo=google-chrome" alt="Website"/>
      </a>
    </td>
    <td align="center">
      <a href="https://docs.onezeroart.com">
        <img src="https://img.shields.io/badge/Docs-docs.onezeroart.com-green?style=for-the-badge&logo=readthedocs" alt="Documentation"/>
      </a>
    </td>
  </tr>
  <tr>
    <td align="center">
      <a href="https://wa.me/8801836216648">
        <img src="https://img.shields.io/badge/WhatsApp-+880_1700_000_000-25D366?style=for-the-badge&logo=whatsapp" alt="WhatsApp"/>
      </a>
    </td>
    <td align="center">
      <a href="mailto:support@onezeroart.com">
        <img src="https://img.shields.io/badge/Email-support@onezeroart.com-red?style=for-the-badge&logo=gmail" alt="Email"/>
      </a>
    </td>
  </tr>
</table>



## 📄 License

This repository contains customizable components for **licensed Zal Ultra users only**. 

- The core Zal Ultra software is proprietary
- SMS services and templates in this repo can be freely modified by licensed users
- Redistribution without authorization is prohibited

---

## 🤝 Contributing

We welcome contributions from our clients! If you've created a new SMS gateway integration:

1. Fork this repository
2. Create your feature branch (`git checkout -b feature/NewSmsGateway`)
3. Commit your changes (`git commit -m 'Add NewSmsGateway integration'`)
4. Push to the branch (`git push origin feature/NewSmsGateway`)
5. Open a Pull Request

---

<p align="center">
  <strong>Made with ❤️ by <a href="https://onezeroart.com">Onezeroart LLC</a></strong>
</p>

<p align="center">
  <sub>© 2018-2025 Onezeroart LLC. All rights reserved.</sub>
</p>

