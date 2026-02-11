# Automatic Renewal System - Complete Guide

## How It Works

The automatic renewal system has **3 layers**:

### Layer 1: Stripe's Native Auto-Renewal
- Stripe automatically renews subscriptions when the billing date arrives
- No additional setup needed - this is built into Stripe subscription mode
- We listen for renewal events via webhooks

### Layer 2: Webhook Event Handlers
Listens to Stripe events and updates the database:
- `customer.subscription.updated` - Catches renewal events and updates billing periods
- `invoice.payment_succeeded` - Confirms payment went through
- `invoice.payment_failed` - Marks subscription as `past_due`

### Layer 3: Cron Jobs & Manual Checks
For edge cases and notifications:
- Checks for expiring subscriptions
- Sends renewal reminders to users
- Manually updates sync with Stripe

---

## Implementation

### 1. **Automatic Renewal (Passive - Stripe Handles It)**

```
Timeline:
Day 0 - User subscribes monthly ($20)
  ↓
Day 30 - Stripe automatically charges card
  ↓
Webhook: invoice.payment_succeeded
  ↓
Local DB updated: new current_period_end = Day 60
  ↓
User can access without interruption
```

**No code needed for this!** Stripe does it automatically.

---

### 2. **Manual Renewal Check (Active - Your Code)**

#### Option A: Check via API
```javascript
// Frontend - Check subscription status
fetch('/api/renewal/status')
  .then(r => r.json())
  .then(data => {
    if (data.data.status === 'expired') {
      // Show renewal button
      showRenewalNotification();
    }
  });
```

#### Option B: Cron Job (Server-side)
```bash
# Run every hour to check for auto-renewals
0 * * * * cd /path/to/project && php spark subscriptions:renew-expired

# Run every day to check upcoming expirations (7 days warning)
0 0 * * * cd /path/to/project && php spark subscriptions:renew-expired --days=7
```

---

## Available Endpoints

### 1. Get Renewal Status
```
GET /api/renewal/status
(Requires authentication)

Response when ACTIVE:
{
  "status": "active",
  "plan_type": "monthly",
  "current_period_end": "2026-03-11 13:30:00",
  "days_remaining": 28,
  "renews_on": "March 11, 2026"
}

Response when EXPIRED:
{
  "status": "expired",
  "expired_days": 2,
  "expired_since": "2026-02-09 13:30:00",
  "plan_type": "monthly"
}
```

### 2. Check Specific User (Auth Admin Only)
```
GET /api/renewal/check/:user_id

Returns same response as above
```

### 3. Trigger Manual Renewal Check
```
POST /api/renewal/process
(Requires authentication)

Checks and processes any pending renewals from Stripe
Returns updated subscription status
```

---

## Cron Job Setup

### Windows Task Scheduler

Create a batch file `renew.bat`:
```batch
@echo off
cd C:\xampp\htdocs\stripe-integration-ci4
C:\xampp\php\php.exe spark subscriptions:renew-expired
```

Schedule task:
1. Open Task Scheduler
2. Create Basic Task
3. Set trigger: Daily at 3:00 AM
4. Set action: Run `renew.bat`

### Linux/Mac Crontab

```bash
# Edit crontab
crontab -e

# Add lines:
# Check for expiring subscriptions every morning at 9 AM
0 9 * * * cd /home/user/stripe-integration-ci4 && php spark subscriptions:renew-expired --days=7

# Check for expired subscriptions every hour
0 * * * * cd /home/user/stripe-integration-ci4 && php spark subscriptions:renew-expired
```

---

## CLI Commands

### Check Expired Subscriptions
```bash
php spark subscriptions:renew-expired
```
Shows: Subscriptions that have passed their renewal date
Does: Updates them with latest Stripe info

### Check Expiring Soon (Warning)
```bash
php spark subscriptions:renew-expired --days=7
```
Shows: Subscriptions expiring in next 7 days
Does: Just displays warning (no auto-charge)

---

## Webhook Events Handled

### `customer.subscription.created`
```
When: New subscription starts
Does: 
  - Creates subscription record in DB
  - Sets current_period_start & current_period_end
  - Handles plan changes (cancels old sub, creates new)
```

### `customer.subscription.updated`
```
When: Subscription renewal or plan change
Does:
  - Updates current_period_start & current_period_end
  - Updates plan_type if changed
  - Updates status (active/past_due/canceled)
```

### `customer.subscription.deleted`
```
When: Subscription canceled
Does:
  - Marks as "canceled"
  - Sets canceled_at timestamp
```

### `invoice.payment_succeeded`
```
When: Payment processed successfully (including renewals)
Does:
  - Sets status to "active"
  - Customer regains access immediately
```

### `invoice.payment_failed`
```
When: Payment declined
Does:
  - Sets status to "past_due"
  - Customer loses access
  - Should send email notification
```

---

## Testing Auto-Renewal

### ⚠️ Important: Stripe Test Clocks

In test mode, subscriptions DON'T actually renew after 30 days. You have **3 options to test**:

### Option 1: Manual Webhook Test (EASIEST)

Use Stripe CLI to send a test webhook event:

```bash
# Terminal 1: Stripe CLI is listening
stripe listen --forward-to localhost:8080/api/webhook

# Terminal 2: Trigger a test event
stripe trigger customer.subscription.updated

# Or with specific data:
stripe trigger customer.subscription.created
stripe trigger invoice.payment_succeeded
```

Check logs after:
```bash
Get-Content "writable\logs\log-$(Get-Date -Format 'yyyy-MM-dd').log" -Tail 50 | Select-String "Subscription Updated"
```

### Option 2: Use Stripe Test Clock (MOST REALISTIC)

Test clocks simulate time passing. This is the proper way to test renewal:

```bash
# 1. Create a test clock (simulates it's March 30, 2026)
stripe fixtures fixtures.json

# Or in Stripe Dashboard:
# Developers → Test Clocks → Create clock → Set date to 1 day before renewal
```

Then:
1. Subscribe with test clock customer (before renewal date)
2. Advance time in dashboard (Developers → Test Clocks)
3. Wait for renewal event
4. Check webhook logs

### Option 3: Database-Level Test (FASTEST)

Manually update database to test the renewal page:

```sql
-- Set subscription to expire tomorrow
UPDATE subscriptions 
SET current_period_end = DATE_ADD(NOW(), INTERVAL 1 DAY)
WHERE user_id = 2;

-- Or set to already expired
UPDATE subscriptions 
SET current_period_end = DATE_SUB(NOW(), INTERVAL 1 DAY)
WHERE user_id = 2;

-- Check it
SELECT * FROM subscriptions WHERE user_id = 2;
```

Then:
1. Go to `/dashboard` → Should redirect to `/subscription/plans`
2. Click "Renew Subscription" → Should work
3. Test API: `curl http://localhost:8080/api/renewal/status`

---

### Scenario: Monthly Subscription Renewal

```bash
Step 1: Subscribe to one-month plan
- User subscribes: Feb 11, 2026
- DB shows: current_period_end = Mar 11, 2026

Step 2: Manually trigger webhook (instead of waiting 30 days)
stripe trigger customer.subscription.updated

Step 3: Check webhook logs
- Get-Content "writable\logs\log-2026-*.log" | Select-String "Subscription Updated"
- Should show: "Subscription Updated" and timestamp updated

Step 4: Verify database
- SELECT * FROM subscriptions WHERE user_id = 1;
- current_period_end should show NEW date (not 1970-01-01)
```

---

## How to Verify Webhooks Are Working

### 1. Check If Stripe CLI Is Connected

```bash
# Terminal window with Stripe CLI running should show:
stripe listen --forward-to localhost:8080/api/webhook

# Output should show:
# Ready! Your webhook signing secret is: whsec_...
# Forwarding events to http://localhost:8080/api/webhook
```

If NOT showing, Stripe CLI isn't connected:
- Make sure command is running
- Check internet connection
- Restart Stripe CLI

### 2. Send a Test Webhook

```bash
# In another terminal, send a test event
stripe trigger customer.subscription.created

# Or send specific webhook types:
stripe trigger invoice.payment_succeeded
stripe trigger invoice.payment_failed
stripe trigger customer.subscription.updated
stripe trigger customer.subscription.deleted
```

### 3. Check Application Logs

```powershell
# Windows PowerShell
Get-Content "writable\logs\log-2026-02-11.log" -Tail 20

# Look for:
# INFO - Stripe Webhook Event: customer.subscription.created
# INFO - Stripe Webhook Event: customer.subscription.updated
# INFO - Stripe Webhook Event: invoice.payment_succeeded
```

### 4. Check Database Was Updated

After triggering a webhook:

```sql
SELECT * FROM subscriptions WHERE user_id = 2;
```

Should show:
```
id | user_id | stripe_subscription_id | plan_type | status | current_period_start | current_period_end
1  | 2       | sub_1Szd6Z...          | monthly   | active | 2026-02-11 13:30:00  | 2026-03-11 13:30:00
```

If dates show **1970-01-01**, webhook wasn't processed correctly.

---

## Real Renewal Cycle (What Actually Happens)

When a subscription actually renews (after 30 days in production):

**Stripe sends these webhooks (IN THIS ORDER):**

```
1. invoice.created
   └─ NEW invoice created for renewal

2. invoice.finalized
   └─ Invoice is now finalized

3. charge.succeeded
   └─ Payment card charged successfully

4. payment_method.attached
   └─ Payment method confirmed

5. payment_intent.created
   └─ Payment intent created

6. payment_intent.succeeded
   └─ Payment intent succeeded

7. invoice.paid
   └─ Invoice marked as paid

8. customer.subscription.updated
   ├─ current_period_start: NEW start date
   ├─ current_period_end: NEW end date (+30 days)
   └─ status: still "active"

9. invoice.payment_succeeded
   └─ Final confirmation payment succeeded
```

**Our handlers catch:**
- ✅ `customer.subscription.updated` → Update billing periods
- ✅ `invoice.payment_succeeded` → Set status to "active"

**Others are logged but ignored** (not critical for renewal)

---

## Event Processing Order in Logs

When you grep logs, you should see:

```
INFO - Stripe Webhook Event: invoice.created
INFO - Stripe Webhook Event: invoice.finalized
INFO - Stripe Webhook Event: charge.succeeded
INFO - Stripe Webhook Event: payment_method.attached
INFO - Stripe Webhook Event: payment_intent.created
INFO - Stripe Webhook Event: payment_intent.succeeded
INFO - Stripe Webhook Event: invoice.paid
INFO - Stripe Webhook Event: customer.subscription.updated      ← WE HANDLE THIS
INFO - Subscription Updated: {...}                          
INFO - Period start timestamp: 1770815117                   
INFO - Period end timestamp: 1773233317                     
INFO - Period start formatted: 2026-03-11 13:30:00
INFO - Period end formatted: 2026-04-11 13:30:00
INFO - Subscription sub_1SzXXX updated in database
INFO - Stripe Webhook Event: invoice.payment_succeeded     ← AND THIS
INFO - Invoice Payment Succeeded: {...}
```

If you DON'T see **both** of these events:
- ❌ Customer has no payment method on file
- ❌ Payment card was declined
- ❌ Webhook isn't forwarding to your server
- ❌ Stripe CLI isn't running



### Scenario: Manual Renewal Check

```bash
# Simulate checking for renewals
php spark subscriptions:renew-expired

# Check what it found
# Should show: "Found X subscriptions expiring soon" or "No expired subscriptions"
```

---

## Frontend Integration Example

```html
<!-- Check subscription status on page load -->
<script>
document.addEventListener('DOMContentLoaded', async () => {
  const response = await fetch('/api/renewal/status');
  const result = await response.json();
  
  if (result.data.status === 'expired') {
    document.getElementById('renewal-banner').innerHTML = `
      <div class="alert alert-warning">
        Your subscription expired ${result.data.expired_days} days ago.
        <a href="/subscription/plans" class="btn btn-primary">Renew Now</a>
      </div>
    `;
  } else if (result.data.status === 'active') {
    const daysLeft = result.data.days_remaining;
    if (daysLeft <= 7) {
      console.warn(`Subscription renews in ${daysLeft} days`);
    }
  }
});
</script>
```

---

## Troubleshooting

### Problem: Subscriptions showing as expired after renewal
**Solution**: Check that webhook is receiving `customer.subscription.updated` events
```bash
grep "Subscription Updated" writable/logs/log-*.log | tail -5
```

### Problem: Cron job not running
**Solution**: Verify cron entry and check logs
```bash
# Check if cron is set
crontab -l

# Check cron logs
grep CRON /var/log/syslog | tail -20
```

### Problem: Payment declining but subscription still shows active
**Solution**: Wait for `invoice.payment_failed` webhook
- Stripe retries failed payments
- System marks as `past_due` after failure
- User gets kicked out on next filter check

---

## Quick Test Checklist ✅

### Step 1: Make Sure Servers Are Running
```
Terminal 1: php spark serve --port 8080
Terminal 2: stripe listen --forward-to localhost:8080/api/webhook
```

### Step 2: Subscribe to Test Plan
```
1. Go to: http://localhost:8080/subscription/plans
2. Select: Monthly ($20)
3. Use test card: 4242 4242 4242 4242
4. Complete checkout
```

### Step 3: Check Database Shows Correct Dates
```sql
SELECT * FROM subscriptions WHERE user_id = 2;

LOOK FOR:
✓ current_period_end ≈ 30 days from today
✗ NOT: 1970-01-01 00:00:00
✗ NOT: Same as current_period_start
```

### Step 4: Trigger Test Webhook
```powershell
stripe trigger customer.subscription.updated

# IMMEDIATELY check logs:
Get-Content "writable\logs\log-2026-02-11.log" -Tail 30 | Select-String "Subscription Updated"

LOOK FOR:
✓ "Period start timestamp: [number]"
✓ "Period end timestamp: [number]"
✓ "Subscription sub_1... updated in database"
```

### Step 5: Verify Database Updated
```sql
-- Run same SELECT as Step 3
SELECT * FROM subscriptions WHERE user_id = 2;

VERIFY:
✓ current_period_end CHANGED (is different than before)
✓ Shows ~60 days from original subscription date
```

### Step 6: Test API
```powershell
curl http://localhost:8080/api/renewal/status

EXPECTED:
{
  "data": {
    "status": "active",
    "days_remaining": 28,
    "renews_on": "March 11, 2026"
  }
}
```

**If you see this → Auto-renewal works!** ✅

---

### If webhook NOT appearing:

**Check 1**: Is Stripe CLI running?
```
Should show continuously in terminal:
"Ready! Your webhook signing secret: whsec_..."
"Forwarding events to http://localhost:8080/api/webhook"
```

**Check 2**: Manually test without subscription:
```powershell
stripe trigger charge.succeeded
Get-Content "writable\logs\log-2026-02-11.log" | Select-String "charge.succeeded"
```

**Check 3**: Check for errors:
```powershell
Get-Content "writable\logs\log-2026-02-11.log" | Select-String -Pattern "error|Error|ERROR"
```

---

## Configuration

### Required .env Variables
```
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_MONTHLY_PRICE_ID=price_1S...
STRIPE_YEARLY_PRICE_ID=price_1S...
STRIPE_WEBHOOK_SECRET=whsec_...
```

### Database Requirements
- `subscriptions` table with status ENUM including 'expired'
- Columns: `current_period_start`, `current_period_end`, `status`
- Foreign key: `user_id`

---

## Best Practices

1. ✅ **Always sync with Stripe** - Don't auto-charge; let Stripe handle it
2. ✅ **Log everything** - Track renewal attempts in logs
3. ✅ **Email on renewal** - Notify user when subscription renews
4. ✅ **Retry failed payments** - Stripe handles this automatically
5. ✅ **Daily cron check** - Ensures DB stays in sync with Stripe
6. ✅ **Show renewal date** - Display when user will be charged next

---

## Summary

| Process | Who Does It | When | Impact |
|---------|------------|------|--------|
| Auto-charge | Stripe | Billing date | Payment taken |
| Webhook listener | Our app | When event fires | DB updated |
| DB sync check | Cron job | Every hour | Catches misses |
| User notification | Frontend | On page load | Shows renewal status |
| Plan changes | Webhook | When subscription.updated fires | Creates new sub |

Everything is **automated** - just make sure webhooks are configured and cron is running!
