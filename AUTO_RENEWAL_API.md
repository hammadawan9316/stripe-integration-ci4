# Auto-Renewal API Reference

## Endpoint 1: Get Subscriber's Renewal Status
```
GET /api/renewal/status
Cookie: PHPSESSID=...  (Authentication required)
```

### Response (Subscription Active)
```json
{
  "status": "success",
  "data": {
    "status": "active",
    "plan_type": "monthly",
    "current_period_end": "2026-03-11 13:30:00",
    "days_remaining": 28,
    "renews_on": "March 11, 2026"
  },
  "message": "Subscription is active",
  "code": 200
}
```

### Response (Subscription Expired)
```json
{
  "status": "success",
  "data": {
    "status": "expired",
    "expired_days": 2,
    "expired_since": "2026-02-09 13:30:00",
    "plan_type": "monthly"
  },
  "message": "Subscription has expired, please renew",
  "code": 200
}
```

### Response (No Subscription)
```json
{
  "status": "success",
  "data": {
    "status": "no_subscription",
    "message": "No active subscription"
  },
  "message": "No subscription found",
  "code": 200
}
```

### Usage in JavaScript
```javascript
async function checkSubscriptionStatus() {
  const response = await fetch('/api/renewal/status');
  const result = await response.json();
  
  if (result.data.status === 'active') {
    console.log(`Subscription renews in ${result.data.days_remaining} days`);
  } else if (result.data.status === 'expired') {
    console.log(`Subscription expired ${result.data.expired_days} days ago`);
  }
}
```

---

## Endpoint 2: Check Specific User's Renewal Status
```
GET /api/renewal/check/:user_id
Cookie: PHPSESSID=...  (Optional, no auth check)
```

### Response Format
Same as Endpoint 1, returns subscription status for any user

### Example
```
GET /api/renewal/check/2
```

### Response
```json
{
  "status": "success",
  "data": {
    "status": "active",
    "plan_type": "yearly",
    "current_period_end": "2027-02-11 13:30:00",
    "days_remaining": 365,
    "renews_on": "February 11, 2027"
  },
  "message": "Subscription is active",
  "code": 200
}
```

---

## Endpoint 3: Trigger Manual Renewal Check
```
POST /api/renewal/process
Cookie: PHPSESSID=...  (Authentication required)
Content-Type: application/json
```

### Request Body
```json
{}
```

### Response (if synced with Stripe)
```json
{
  "status": "success",
  "data": {
    "status": "renewed",
    "subscription": {
      "plan_type": "monthly",
      "new_period_start": "2026-03-11 13:30:00",
      "new_period_end": "2026-04-11 13:30:00",
      "stripe_status": "active"
    }
  },
  "message": "Subscription auto-renewed by Stripe",
  "code": 200
}
```

### Usage in JavaScript
```javascript
async function triggerRenewalCheck() {
  const response = await fetch('/api/renewal/process', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    }
  });
  const result = await response.json();
  console.log(result);
}
```

---

## Complete Frontend Example

```html
<!-- Dashboard Subscription Widget -->
<div id="subscription-widget">
  <div class="loading">Checking subscription status...</div>
</div>

<script>
async function initSubscriptionWidget() {
  try {
    const response = await fetch('/api/renewal/status');
    const result = await response.json();
    const widget = document.getElementById('subscription-widget');
    
    if (result.data.status === 'active') {
      widget.innerHTML = `
        <div class="subscription-card active">
          <h3>${result.data.plan_type} Plan</h3>
          <p>Status: <strong>Active</strong></p>
          <p>Renews: ${result.data.renews_on}</p>
          <p>Days remaining: ${result.data.days_remaining}</p>
          
          ${result.data.days_remaining <= 7 ? `
            <div class="warning">
              ⚠️ Your subscription renews in ${result.data.days_remaining} days
            </div>
          ` : ''}
          
          <button onclick="changeSubscriptionPlan()">Change Plan</button>
          <button onclick="cancelSubscription()">Cancel</button>
        </div>
      `;
    } else if (result.data.status === 'expired') {
      widget.innerHTML = `
        <div class="subscription-card expired">
          <h3>Subscription Expired</h3>
          <p>Status: <strong>Expired</strong></p>
          <p>Expired since: ${result.data.expired_since}</p>
          <p>Days past expiration: ${result.data.expired_days}</p>
          
          <a href="/subscription/plans" class="btn btn-primary">
            Renew Subscription
          </a>
        </div>
      `;
    } else {
      widget.innerHTML = `
        <div class="subscription-card">
          <h3>No Active Subscription</h3>
          <p>You don't have an active subscription.</p>
          
          <a href="/subscription/plans" class="btn btn-primary">
            Subscribe Now
          </a>
        </div>
      `;
    }
  } catch (error) {
    console.error('Error:', error);
    document.getElementById('subscription-widget').innerHTML = 
      '<p class="error">Failed to load subscription status</p>';
  }
}

// Run on page load
document.addEventListener('DOMContentLoaded', initSubscriptionWidget);
</script>

<style>
.subscription-card {
  padding: 20px;
  border-radius: 8px;
  background: #f5f5f5;
  margin: 20px 0;
}

.subscription-card.active {
  border: 2px solid #4caf50;
  background: #f1f8f6;
}

.subscription-card.expired {
  border: 2px solid #f44336;
  background: #fef5f4;
}

.subscription-card h3 {
  margin-top: 0;
}

.subscription-card button {
  margin-right: 10px;
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  background: #667eea;
  color: white;
}

.warning {
  background: #fff3cd;
  padding: 10px;
  border-radius: 4px;
  margin: 10px 0;
  border-left: 4px solid #ffc107;
}
</style>
```

---

## Common Status Codes

| Code | Meaning | Action |
|------|---------|--------|
| 200 | Success | Use the returned data |
| 401 | Unauthorized | Redirect to login |
| 404 | User not found | Invalid user ID |
| 500 | Server error | Check logs |

---

## Error Responses

### Not Authenticated
```json
{
  "error": "Not authenticated",
  "code": 401
}
```

### User Not Found
```json
{
  "error": "User not found",
  "code": 404
}
```

### Server Error
```json
{
  "error": "Failed to check renewal status: [error message]",
  "code": 500
}
```

---

## Webhook Events Related to Renewal

### Event: `customer.subscription.updated`
Fired when subscription renews

```json
{
  "type": "customer.subscription.updated",
  "data": {
    "object": {
      "id": "sub_1Szd6ZFWiwUf6OTC...",
      "customer": "cus_TxXPgRSeShw7Ch",
      "status": "active",
      "current_period_start": 1770815117,
      "current_period_end": 1773233317,
      "items": {
        "data": [{
          "current_period_start": 1770815117,
          "current_period_end": 1773233317
        }]
      }
    }
  }
}
```

**Our handler action**: Updates database with new billing period

### Event: `invoice.payment_succeeded`
Fired after payment is successfully processed

```json
{
  "type": "invoice.payment_succeeded",
  "data": {
    "object": {
      "id": "in_1Szd6X...",
      "subscription": "sub_1Szd6Z...",
      "status": "paid"
    }
  }
}
```

**Our handler action**: Sets subscription status to "active"

### Event: `invoice.payment_failed`
Fired when payment is declined

```json
{
  "type": "invoice.payment_failed",
  "data": {
    "object": {
      "id": "in_1Szd6X...",
      "subscription": "sub_1Szd6Z..."
    }
  }
}
```

**Our handler action**: Sets subscription status to "past_due"

---

## Testing Endpoints

### With cURL

```bash
# Check your own subscription
curl -b "PHPSESSID=your_session_id" \
  http://localhost:8080/api/renewal/status

# Check specific user
curl http://localhost:8080/api/renewal/check/2

# Trigger renewal check
curl -X POST \
  -b "PHPSESSID=your_session_id" \
  http://localhost:8080/api/renewal/process
```

### With Postman

1. GET http://localhost:8080/api/renewal/status
2. Add Cookie: `PHPSESSID=your_session_id`
3. Send

---

## Response Helpers

All responses use the `sendApiResponse()` helper function:

```php
// Success response
sendApiResponse($data, 'Success message', 200);

// Error response
sendApiResponse(null, 'Error message', 400);
```

Format:
```json
{
  "status": "success" | "error",
  "data": {},
  "message": "...",
  "code": 200
}
```

---

## Rate Limiting

Currently no rate limiting. In production, consider adding:

```php
// Example: 10 requests per minute per IP
if (!rateLimitCheck('renewal_api', $_SERVER['REMOTE_ADDR'], 10, 60)) {
  // Too many requests
}
```

---

## Security Notes

1. ✅ **Authentication**: Endpoints check user session
2. ✅ **Stripe Validation**: Webhook signature verified
3. ✅ **Data Validation**: All inputs sanitized
4. ⚠️ **CORS**: Check `app/Filters/CorsFilter.php` for production CORS

For production, ensure:
- Use HTTPS only
- Set secure CORS headers
- Add rate limiting
- Log all API calls
