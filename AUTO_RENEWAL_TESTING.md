# Auto-Renewal Testing - Quick Reference

## The Problem You're Seeing

You don't see `customer.subscription.updated` events because:

1. ❌ **Stripe doesn't auto-charge in test mode after 30 days**
   - In production, it waits 30 days then charges
   - In test mode, nothing happens until you manually trigger it

2. ✅ **Solution: Manually trigger webhook events**
   - Use Stripe CLI to send fake webhook
   - Or update database dates to test expiration

---

## Testing Steps (5 minutes)

### Step 1: Start Servers
```powershell
# Terminal 1
php spark serve --port 8080

# Terminal 2
stripe listen --forward-to localhost:8080/api/webhook
```

### Step 2: Subscribe
- Go to: http://localhost:8080/subscription/plans
- Choose Monthly ($20)
- Test card: 4242 4242 4242 4242
- Complete payment

### Step 3: Verify Database
```sql
SELECT * FROM subscriptions WHERE user_id = 2;
```

**Expected:**
```
current_period_start: 2026-02-11 13:30:00
current_period_end: 2026-03-11 13:30:00  ✓ Not 1970-01-01!
```

### Step 4: Trigger Webhook Manually
```powershell
stripe trigger customer.subscription.updated
```

### Step 5: Check Logs
```powershell
Get-Content "writable\logs\log-2026-02-11.log" -Tail 50 | Select-String "Subscription Updated"
```

**Expected to see:**
```
INFO - Stripe Webhook Event: customer.subscription.updated
INFO - Subscription Updated: {...}
INFO - Period start timestamp: 1770815117
INFO - Period end timestamp: 1773233317
INFO - Subscription sub_1SzXXX updated in database
```

### Step 6: Verify Database Changed
```sql
SELECT * FROM subscriptions WHERE user_id = 2;
```

**Expected:**
```
current_period_end: 2026-04-11 13:30:00  ✓ CHANGED!
```

### Step 7: Test API
```powershell
curl http://localhost:8080/api/renewal/status
```

**Expected:**
```json
{
  "status": "active",
  "days_remaining": 28,
  "renews_on": "March 11, 2026"
}
```

---

## What Happens in REAL Production

```
User Subscribes (Day 0)
    ↓
Wait 30 days (Stripe automatically triggers renewal)
    ↓
Stripe charges card
    ↓
Stripe sends webhook events:
  - invoice.created
  - invoice.finalized
  - charge.succeeded
  - payment_intent.created
  - payment_intent.succeeded
  - customer.subscription.updated  ← WE HANDLE THIS
  - invoice.payment_succeeded
    ↓
Our webhook handler updates database
    ↓
User still has access (no interruption)
    ↓
Repeat every 30 days automatically
```

---

## Testing Different Scenarios

### Scenario A: Test Webhook Reception
```powershell
# ANY webhook event
stripe trigger charge.succeeded

# Check logs
Get-Content "writable\logs\log-2026-02-11.log" | Select-String "Webhook Event"
```

If you see "Webhook Event: charge.succeeded" → Webhooks are working

### Scenario B: Test Expired Subscription
```sql
-- Make subscription expire yesterday
UPDATE subscriptions 
SET current_period_end = DATE_SUB(NOW(), INTERVAL 1 DAY)
WHERE user_id = 2;

-- Verify
SELECT current_period_end FROM subscriptions WHERE user_id = 2;
```

Then:
1. Go to http://localhost:8080/dashboard
2. Should redirect to /subscription/plans
3. Should see "Your subscription has expired"
4. Test API: `curl http://localhost:8080/api/renewal/status`
5. Should show: `"status": "expired"`

### Scenario C: Test Renewal API
```powershell
# Requires login (sets session)
curl http://localhost:8080/api/renewal/status

# Should return status and days_remaining
```

---

## Troubleshooting Checklist

| ❌ Problem | ✅ Check | ✓ Solution |
|-----------|---------|-----------|
| No webhook events | Is Stripe CLI running? | `stripe listen --forward-to localhost:8080/api/webhook` |
| Logs empty | Is server running? | `php spark serve --port 8080` |
| Dates still 1970 | Did webhook fire? | `stripe trigger customer.subscription.updated` |
| Database not changing | Check for errors | `Get-Content writable/logs/log-*.log \| Select-String error` |
| API returns 401 | Are you logged in? | Login first, then test |

---

## Expected Webhook Events in Logs

When you trigger `stripe trigger customer.subscription.updated`, you should see:

```
INFO - Stripe Webhook Event: customer.subscription.updated
INFO - Subscription Updated: {...full JSON object...}
INFO - Period start timestamp: 1770815117
INFO - Period end timestamp: 1773233317
INFO - Period start formatted: 2026-03-11 13:30:00
INFO - Period end formatted: 2026-04-11 13:30:00
INFO - Subscription sub_1Szd6ZFWiwUf6OTC... updated in database
```

If you only see some of these, webhook wasn't fully processed.

---

## Key Dates to Know

- **Subscription created**: Feb 11, 2026
- **First renewal (after 30 days)**: Mar 11, 2026
- **Second renewal (after 60 days)**: Apr 11, 2026

So if your database shows Feb 11 - Mar 11, that's correct! ✓

---

## Files to Check

| File | What to Look For |
|------|------------------|
| `writable/logs/log-2026-02-11.log` | Webhook events, errors |
| Database table `subscriptions` | Dates, status |
| `app/Controllers/Api/WebhookController.php` | Our webhook handler |
| `app/Controllers/Api/RenewalController.php` | Our renewal API |

---

## Success Indicators

✓ Database shows correct dates (not 1970-01-01)
✓ Webhook events appear in logs
✓ API `/api/renewal/status` returns JSON
✓ Can manually trigger subscriptions renewed
✓ Dashboard only accessible with active subscription
✓ API shows days_remaining = ~28-30

**When you see all of these → System is working!** 🎉

---

## Real-World Renewal Flow

```
Timeline:
---------
Feb 11  → User subscribes monthly
        → DB: end = Mar 11
        → User has access ✓

Feb 12-Mar 10 → User can access dashboard
              → No action needed
              → Works automatically

Mar 11 (BILLING DATE) → Stripe charges card
                      → Stripe sends webhooks
                      → Our code catches them
                      → DB: end = Apr 11
                      → User still has access ✓

Mar 12-Apr 10 → User continues access
              → No action needed

Apr 11 (BILLING DATE) → Repeat cycle
```

**This all happens automatically!** You just:
1. Make sure Stripe CLI is running
2. Make sure server is running
3. Wait for renewal date (or fake it with webhooks)
4. Check database gets updated

That's it! No code changes needed for renewals to work.

---

## Commands You'll Need

```powershell
# Start servers
php spark serve --port 8080
stripe listen --forward-to localhost:8080/api/webhook

# Check logs
Get-Content "writable\logs\log-2026-02-11.log" -Tail 50

# Search logs
Get-Content "writable\logs\log-2026-02-11.log" | Select-String "Subscription Updated"

# Test webhook
stripe trigger customer.subscription.updated

# Test API (need to be logged in)
curl http://localhost:8080/api/renewal/status

# Test database
SELECT * FROM subscriptions WHERE user_id = 2;
```

---

## Summary

1. **You saw webhook events before** when you subscribed (checkout.session.completed)
2. **customer.subscription.updated** comes 30 days later (or when you manually trigger it)
3. **To test it now**, use `stripe trigger customer.subscription.updated`
4. **Check logs** to verify webhook was received
5. **Check database** to verify dates were updated
6. **Done!** Auto-renewal is working

Everything else happens automatically through Stripe webhooks. No manual renewal code needed! ✅
