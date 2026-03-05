# MikroTik Hotspot Setup Guide

This guide explains how to customize and upload hotspot pages for Zal Ultra integration.

---

## ⚠️ IMPORTANT: Before Uploading

**You MUST update the IP address or domain URL in the hotspot files before uploading to MikroTik!**

### Files to Modify:
- `login.html` - Contains redirect URL to Zal Ultra
- `status.html` - Contains redirect URL to Zal Ultra

### What to Change:

Open each file and find the redirect URL:
```html
<!-- Find this line and change the IP/domain -->
<meta http-equiv="refresh" content="0;url=http://192.168.68.109/self?...">
```

**Replace `192.168.68.109` with your Zal Ultra server IP or domain:**
```html
<!-- Example with IP -->
<meta http-equiv="refresh" content="0;url=http://YOUR_SERVER_IP/self?...">

<!-- Example with domain -->
<meta http-equiv="refresh" content="0;url=https://your-domain.com/self?...">
```

> **⚠️ If you don't update the IP/domain, MikroTik cannot redirect users to your Zal Ultra login page!**

---

## How It Works

When a subscriber logs in through the hotspot:

1. **User connects to WiFi/Hotspot** → MikroTik captures the connection
2. **MikroTik redirects to `login.html`** → Which redirects to Zal Ultra `/self` page
3. **User enters credentials on Zal Ultra** → Logs into both:
   - ✅ **Zal Ultra Captive Portal** (session created)
   - ✅ **MikroTik Hotspot** (RADIUS authentication)
4. **User gets internet access** → Authenticated on both systems

```
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│   WiFi User  │───▶│   MikroTik   │───▶│  Zal Ultra   │
│              │    │   Hotspot    │    │  /self page  │
└──────────────┘    └──────────────┘    └──────────────┘
                           │                    │
                           │                    ▼
                           │            ┌──────────────┐
                           │◀───────────│  RADIUS Auth │
                           │            └──────────────┘
                           ▼
                    Internet Access ✓
```

---

## Setup Steps

### Step 1: Modify the Files

1. Navigate to `mikrotik/hotspot/`
2. Edit `login.html` - Update the Zal Ultra IP/domain
3. Edit `status.html` - Update the Zal Ultra IP/domain

### Step 2: Upload to MikroTik

**Via Winbox:**
1. Open Winbox → **Files**
2. Navigate to `flash/hotspot/`
3. Drag and drop `login.html` and `status.html`

**Via SCP (command line):**
```bash
scp login.html admin@MIKROTIK_IP:/flash/hotspot/
scp status.html admin@MIKROTIK_IP:/flash/hotspot/
```

### Step 3: Clear Hotspot Cache

After uploading, clear the cache to see changes:
```routeros
/ip hotspot cookie remove [find]
```

---

## MikroTik Configuration Commands

### Configure RADIUS Client
```routeros
/radius add service=hotspot address=YOUR_ZALULTRA_IP secret=your-radius-secret authentication-port=1812 accounting-port=1813
```

### Configure Walled Garden (CRITICAL!)

Allow access to Zal Ultra server **before** authentication:
```routeros
# Allow Zal Ultra server (REQUIRED - change IP to your server)
/ip hotspot walled-garden ip add dst-address=YOUR_ZALULTRA_IP action=accept

# Allow DNS queries
/ip hotspot walled-garden ip add dst-port=53 protocol=udp action=accept
/ip hotspot walled-garden ip add dst-port=53 protocol=tcp action=accept
```

> **⚠️ Without walled garden, users cannot reach the Zal Ultra login page!**

### Configure Hotspot Profile
```routeros
/ip hotspot profile add \
    name=hsprof-zal \
    hotspot-address=10.5.50.1 \
    dns-name="" \
    login-by=http-chap,http-pap \
    use-radius=yes \
    radius-interim-update=5m \
    html-directory=flash/hotspot
```

### Configure NAT/Masquerade
```routeros
# For default hotspot pool
/ip firewall nat add chain=srcnat src-address=10.5.50.0/24 action=masquerade

# For each RADIUS IP pool (add as needed)
/ip firewall nat add chain=srcnat src-address=10.5.10.0/24 action=masquerade
```

---

## Troubleshooting

### Check Walled Garden Rules
```routeros
/ip hotspot walled-garden ip print
```

### Check Active Hotspot Users
```routeros
/ip hotspot active print
```

### Check RADIUS Communication
```routeros
/radius print
/radius monitor 0
```

### Clear Hotspot Sessions
```routeros
/ip hotspot cookie remove [find]
/ip hotspot active remove [find]
```

### Restart Hotspot
```routeros
/ip hotspot disable hotspot1
/ip hotspot enable hotspot1
```

---

## MikroTik Variables for login.html

| Variable | Description |
|----------|-------------|
| `$(link-login)` | Full login URL |
| `$(link-login-only)` | Login URL path only |
| `$(link-orig)` | Original URL user was accessing |
| `$(mac)` | Client MAC address |
| `$(ip)` | Client IP address |

---

## Quick Reference

| Task | Command/Action |
|------|----------------|
| Upload files | `scp file.html admin@MIKROTIK_IP:/flash/hotspot/` |
| Add walled garden | `/ip hotspot walled-garden ip add dst-address=IP action=accept` |
| Clear cache | `/ip hotspot cookie remove [find]` |
| View active users | `/ip hotspot active print` |
| Check RADIUS | `/radius monitor 0` |

---

*For complete MikroTik hotspot setup guide, refer to Zal Ultra documentation.*