# Cloudflare Setup Guide for RevisionHub

## Why Cloudflare? (FREE Benefits)

✅ **Free CDN** - Speeds up your site globally  
✅ **DDoS Protection** - Protects against attacks  
✅ **Free SSL** - HTTPS encryption  
✅ **Caching** - Reduces server load  
✅ **Web Application Firewall** - Basic security  

## Step-by-Step Setup

### 1. Create Cloudflare Account
1. Go to [cloudflare.com](https://cloudflare.com)
2. Sign up for a **FREE** account
3. No credit card required

### 2. Add Your Website
1. Click "Add a Site"
2. Enter: `revisionhubkenya.com`
3. Select **FREE** plan
4. Cloudflare will scan your DNS records

### 3. Update Nameservers
1. Cloudflare will give you 2 nameservers (e.g., `lara.ns.cloudflare.com` and `bob.ns.cloudflare.com`)
2. Go to your domain registrar (where you bought `revisionhubkenya.com`)
3. Replace current nameservers with Cloudflare's nameservers
4. Wait 24-48 hours for propagation

### 4. SSL/TLS Settings
In Cloudflare dashboard:
1. Go to **SSL/TLS** → **Overview**
2. Select **Full** or **Full (Strict)** mode
3. Under **Edge Certificates**:
   - Enable **Always Use HTTPS**
   - Enable **Minimum TLS Version**: 1.2
   - Enable **Opportunistic Encryption**
   - Enable **TLS 1.3**

### 5. Caching Settings
1. Go to **Caching** → **Configuration**
2. Set **Caching Level**: Standard
3. Enable **Browser Cache TTL**: 4 hours
4. Under **Cache Rules**, add:
   ```
   Cache everything for: *.js, *.css, *.jpg, *.jpeg, *.png, *.gif, *.svg, *.webp
   ```

### 6. Page Rules (Important!)
Create these Page Rules:

**Rule 1: Cache Static Assets**
```
URL: revisionhubkenya.com/storage/*
Settings: Cache Level: Cache Everything
          Browser Cache TTL: 1 month
          Edge Cache TTL: 1 month
```

**Rule 2: Bypass Cache for Dynamic Content**
```
URL: revisionhubkenya.com/api/*
Settings: Cache Level: Bypass
```

**Rule 3: Bypass Cache for Admin**
```
URL: revisionhubkenya.com/admin/*
Settings: Cache Level: Bypass
```

### 7. Security Settings
1. Go to **Security** → **Settings**
2. Set **Security Level**: Medium
3. Enable **SSL**: Full
4. Under **WAF** (Web Application Firewall):
   - Enable **WAF** (free rules are included)

### 8. Speed Optimization
1. Go to **Speed** → **Optimization**
2. Enable:
   - ✅ Auto Minify: CSS, JavaScript, HTML
   - ✅ Brotli Compression
   - ✅ Early Hints

### 9. Update .htaccess for Cloudflare
Add this to your `.htaccess` file (in public/ directory):

```apache
# Trust Cloudflare IPs
SetEnvIf X-Forwarded-For "^103\.21\.244\.0" trust_proxy
SetEnvIf X-Forwarded-For "^103\.22\.200\.0" trust_proxy
SetEnvIf X-Forwarded-For "^103\.31\.4\.0" trust_proxy
SetEnvIf X-Forwarded-For "^104\.16\.0\.0" trust_proxy
SetEnvIf X-Forwarded-For "^108\.162\.192\.0" trust_proxy
SetEnvIf X-Forwarded-For "^131\.0\.72\.0" trust_proxy
SetEnvIf X-Forwarded-For "^141\.101\.64\.0" trust_proxy
SetEnvIf X-Forwarded-For "^162\.158\.0\.0" trust_proxy
SetEnvIf X-Forwarded-For "^172\.64\.0\.0" trust_proxy
SetEnvIf X-Forwarded-For "^173\.245\.48\.0" trust_proxy
SetEnvIf X-Forwarded-For "^188\.114\.96\.0" trust_proxy
SetEnvIf X-Forwarded-For "^190\.93\.240\.0" trust_proxy
SetEnvIf X-Forwarded-For "^197\.234\.240\.0" trust_proxy
SetEnvIf X-Forwarded-For "^198\.41\.128\.0" trust_proxy
SetEnvIf X-Forwarded-For "^2400:cb00::" trust_proxy
SetEnvIf X-Forwarded-For "^2606:4700::" trust_proxy
SetEnvIf X-Forwarded-For "^2803:f800::" trust_proxy
SetEnvIf X-Forwarded-For "^2405:b500::" trust_proxy
SetEnvIf X-Forwarded-For "^2405:8100::" trust_proxy
SetEnvIf X-Forwarded-For "^2c0f:f248::" trust_proxy
SetEnvIf X-Forwarded-For "^2a06:98c0::" trust_proxy

# Get real IP from Cloudflare
RewriteEngine On
RewriteCond %{HTTP:CF-Connecting-IP} ^(\d+\.\d+\.\d+\.\d+)$
RewriteRule .* - [E=REMOTE_ADDR:%1]
```

### 10. Update Laravel TrustProxies
In `app/Http/Middleware/TrustProxies.php`:

```php
<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = [
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '108.162.192.0/18',
        '131.0.72.0/22',
        '141.101.64.0/18',
        '162.158.0.0/15',
        '172.64.0.0/13',
        '173.245.48.0/20',
        '188.114.96.0/20',
        '190.93.240.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2c0f:f248::/32',
        '2a06:98c0::/32',
    ];

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
```

## Expected Performance Improvements

- **50-70% faster** page load times
- **80% reduction** in server bandwidth
- **Better SEO** (Google loves fast sites)
- **Protection** from DDoS attacks
- **Global reach** with CDN

## Monitoring

After setup, monitor:
1. **Cloudflare Analytics** → See cached vs uncached requests
2. **Laravel Debugbar** → Check query times
3. **Google PageSpeed Insights** → Test performance

## Troubleshooting

### Issue: Site shows "Too Many Redirects"
**Solution**: In Cloudflare SSL/TLS settings, switch from "Flexible" to "Full"

### Issue: Real visitor IPs not showing
**Solution**: Make sure TrustProxies is configured correctly (step 10)

### Issue: API not working
**Solution**: Check Page Rules - API should bypass cache

## Next Steps

After Cloudflare setup:
1. Enable **Laravel Caching** (already configured)
2. Optimize images before upload
3. Use lazy loading for images
4. Minimize JavaScript bundles

---

**Need help?** Contact Cloudflare support (free community support available)