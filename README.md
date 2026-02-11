# Stripe Subscription Integration with CodeIgniter 4

A complete, production-ready subscription management system using Stripe and CodeIgniter 4. Supports multiple pricing plans with automatic renewal, webhook handling, and subscription management.

## 🎯 Features

### ✅ Core Features
- **User Authentication** - Register, login, logout with secure password hashing
- **Stripe Checkout Integration** - Seamless payment processing
- **Multiple Pricing Plans** - Monthly ($20) and Yearly ($200) plans
- **Automatic Renewal** - Stripe automatically charges and renews subscriptions
- **Webhook Handling** - Real-time sync with Stripe events
- **Access Control** - Protect pages with subscription filter
- **Plan Management** - Change plans, cancel subscription
- **Dashboard** - Protected content for subscribers only

### ✅ Advanced Features
- **Plan Changes** - Upgrade/downgrade between plans seamlessly
- **Renewal Status API** - Check subscription expiration via API
- **Expiration Handling** - Graceful handling of expired subscriptions
- **Database Sync** - CLI commands to sync with Stripe
- **Error Handling** - Comprehensive logging and error management
- **Test Mode** - Fully functional in Stripe test mode

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.1 or higher
- MySQL/MariaDB
- Composer
- Git
- Stripe Account (free test mode account)
- Stripe CLI (for webhooks)

### Installation

#### 1. Clone or Download Project
```bash
git clone https://github.com/yourusername/stripe-integration-ci4.git
cd stripe-integration-ci4
```

#### 2. Install Dependencies
```bash
composer install
```

#### 3. Setup Environment
```bash
# Copy environment file
cp env .env

# Edit .env with your Stripe keys and database info
```

#### 4. Create Database
```bash
# Create MySQL database
mysql -u root -p
CREATE DATABASE strip;
EXIT;
```

#### 5. Run Migrations
```bash
php spark migrate
```

#### 6. Start Servers

**Terminal 1: PHP Development Server**
```bash
php spark serve --port 8080
```

**Terminal 2: Stripe Webhook Listener**
```bash
stripe listen --forward-to localhost:8080/api/webhook
```

---

## 🔑 Configuration

### Environment Variables (.env)

Create `.env` file in project root:

```env
# Database
database.default.hostname = localhost
database.default.database = strip
database.default.username = root
database.default.password = 
database.default.port = 3306

# Stripe
STRIPE_PUBLIC_KEY=pk_test_YOUR_PUBLIC_KEY
STRIPE_SECRET_KEY=sk_test_YOUR_SECRET_KEY
STRIPE_MONTHLY_PRICE_ID=price_1SzbM2FWiwUf6OTCCfkDu7eq
STRIPE_YEARLY_PRICE_ID=price_1SzbM2FWiwUf6OTCswqYdNWx
STRIPE_WEBHOOK_SECRET=whsec_YOUR_WEBHOOK_SECRET

# Application
CI_ENVIRONMENT=development
app.baseURL='http://localhost:8080/'
```

### Getting Stripe Keys

1. Go to [Stripe Dashboard](https://dashboard.stripe.com)
2. Sign up for free test account
3. Go to Developers → API Keys
4. Copy **Publishable key** and **Secret key**
5. Create test products and prices → Get price IDs
6. Set webhook secret after configuring webhook endpoint

---

## 📁 Project Structure

```
stripe-integration-ci4/
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php              # User registration & login
│   │   ├── SubscriptionViewController.php  # View rendering
│   │   ├── Api/
│   │   │   ├── SubscriptionController.php  # Subscription API
│   │   │   ├── WebhookController.php       # Stripe webhooks
│   │   │   └── RenewalController.php       # Renewal status API
│   ├── Models/
│   │   ├── UserModel.php                   # User database
│   │   └── SubscriptionModel.php           # Subscription database
│   ├── Filters/
│   │   └── SubscriptionFilter.php          # Access control filter
│   ├── Commands/
│   │   └── RenewExpiredSubscriptions.php   # Cron command
│   ├── Views/
│   │   ├── auth/
│   │   │   ├── register.php
│   │   │   └── login.php
│   │   ├── subscription/
│   │   │   ├── plans.php
│   │   │   ├── success.php
│   │   │   ├── cancel.php
│   │   │   └── status_card.php
│   │   └── dashboard.php
│   ├── Database/
│   │   └── Migrations/
│   │       ├── 2026-02-11-000001_CreateUsersTable.php
│   │       └── 2026-02-11-000002_CreateSubscriptionsTable.php
│   └── Config/
│       ├── Routes.php
│       └── Filters.php
├── public/
│   └── index.php                     # Application entry point
├── writable/
│   ├── logs/                         # Application logs
│   └── uploads/                      # File uploads
├── .env                              # Environment variables
├── composer.json                     # PHP dependencies
└── README.md                         # This file
```

---

## 💻 Usage

### User Registration
```
http://localhost:8080/register
- Fill in email, name, password
- Creates new user account
- Auto-links to Stripe customer
```

### User Login
```
http://localhost:8080/login
- Enter email and password
- Sets session
- Redirects to dashboard
```

### Browse Plans
```
http://localhost:8080/subscription/plans
- View monthly ($20) and yearly ($200) options
- Click to start checkout process
```

### Checkout
```
Stripe Checkout Modal
- Enter test card: 4242 4242 4242 4242
- Enter any future expiry (e.g., 12/25)
- Enter any CVC (e.g., 123)
- Complete payment
```

### Access Dashboard
```
http://localhost:8080/dashboard
- Only accessible with active subscription
- Shows subscription details
- Auto-redirects if not subscribed
```

### Change Plans
```
http://localhost:8080/subscription/plans
- Click different plan while subscribed
- Old subscription auto-canceled
- New subscription created
```

---

## 🔗 API Endpoints

### Get Subscription Plans
```
GET /api/subscription/plans
Response: List of available plans with prices
```

### Create Checkout Session
```
POST /api/subscription/checkout
Body: {
  "user_id": 2,
  "plan_type": "monthly"  // or "yearly"
}
Response: Stripe checkout session URL
```

### Get User Subscription Status
```
GET /api/subscription/status/:user_id
Response: Current subscription details
```

### Check Renewal Status
```
GET /api/renewal/status
(Requires login)
Response: Days remaining, renewal date
```

### Get Renewal Status For User
```
GET /api/renewal/check/:user_id
Response: Renewal status, expiration info
```

### Process Renewal
```
POST /api/renewal/process
(Requires login)
Response: Updated subscription info
```

---

## 🔄 How Automatic Renewal Works

### Timeline

```
Day 0: User subscribes monthly
├─ Payment processed
├─ Webhook: checkout.session.completed
├─ Webhook: customer.subscription.created
└─ Database: current_period_end = Day 30

Days 1-29: User has access
├─ Dashboard accessible
├─ Protected pages work
└─ No action needed

Day 30 (Billing Date): AUTOMATIC RENEWAL
├─ Stripe charges card
├─ Webhook: invoice.created
├─ Webhook: charge.succeeded
├─ Webhook: customer.subscription.updated
│  ├─ current_period_start = Day 30
│  └─ current_period_end = Day 60
├─ Database updated automatically
└─ Webhook: invoice.payment_succeeded

Days 31-60: User still has access
└─ Process repeats every 30 days...
```

### What Happens Automatically

✅ **Stripe Does**:
- Charges card on billing date
- Retries if payment fails
- Sends webhook events

✅ **Our Code Does**:
- Listens for webhooks
- Updates database with new billing periods
- Redirects expired users to renewal page
- Provides API to check renewal status

✅ **No Manual Action Needed**:
- Users don't need to do anything
- Subscriptions renew seamlessly
- Dashboard access uninterrupted

---

## 🧪 Testing

### Test Scenario 1: Subscribe and Check

```bash
# Step 1: Open browser
http://localhost:8080/register
# Create test account

http://localhost:8080/subscription/plans
# Subscribe to monthly plan ($20)
# Use test card: 4242 4242 4242 4242

# Step 2: Check database
SELECT * FROM subscriptions WHERE user_id = 1;
# Should show:
# current_period_start: 2026-02-11 13:30:00
# current_period_end: 2026-03-11 13:30:00 (NOT 1970-01-01!)

# Step 3: Access dashboard
http://localhost:8080/dashboard
# Should show subscription info and grant access
```

### Test Scenario 2: Trigger Webhook

```bash
# Terminal 1: Start Stripe listener
stripe listen --forward-to localhost:8080/api/webhook

# Terminal 2: Trigger renewal event
stripe trigger customer.subscription.updated

# Terminal 1: Should show event forwarded
# Check logs: writable/logs/log-2026-02-11.log
# Should show: "Subscription Updated"

# Check database:
SELECT * FROM subscriptions WHERE user_id = 1;
# current_period_end should be updated
```

### Test Scenario 3: Check Renewal API

```bash
# Login first (creates session)
http://localhost:8080/login

# Then test API
curl http://localhost:8080/api/renewal/status

# Response should show:
{
  "status": "active",
  "plan_type": "monthly",
  "days_remaining": 28,
  "renews_on": "March 11, 2026"
}
```

### Test Scenario 4: Test Expired Subscription

```sql
-- Make subscription expire
UPDATE subscriptions 
SET current_period_end = DATE_SUB(NOW(), INTERVAL 1 DAY)
WHERE user_id = 1;

-- Access dashboard
http://localhost:8080/dashboard
-- Should redirect to /subscription/plans with message
-- Should see renewal button
```

---

## 🔔 Webhook Events Handled

| Event | Action | Log |
|-------|--------|-----|
| `checkout.session.completed` | Order confirmation | checkpoint logged |
| `customer.subscription.created` | New subscription | subscription created in DB |
| `customer.subscription.updated` | Renewal or plan change | billing periods updated |
| `customer.subscription.deleted` | Subscription canceled | marked as canceled |
| `invoice.payment_succeeded` | Payment successful | status set to active |
| `invoice.payment_failed` | Payment declined | status set to past_due |

---

## 🛠️ Database Schema

### Users Table
```sql
CREATE TABLE users (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(255) UNIQUE,
  name VARCHAR(255),
  password VARCHAR(255),
  stripe_customer_id VARCHAR(255),
  created_at DATETIME,
  updated_at DATETIME
);
```

### Subscriptions Table
```sql
CREATE TABLE subscriptions (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id INT UNSIGNED,
  stripe_subscription_id VARCHAR(255),
  stripe_price_id VARCHAR(255),
  plan_type ENUM('monthly', 'yearly'),
  status ENUM('active', 'expired', 'canceled', 'past_due', 'incomplete'),
  current_period_start DATETIME,
  current_period_end DATETIME,
  canceled_at DATETIME,
  created_at DATETIME,
  updated_at DATETIME,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 📊 Key Files Explained

### AuthController.php
- User registration form and processing
- User login form and session creation
- Password hashing and validation

### SubscriptionController.php (API)
- `getPlans()` - Returns available plans
- `createCheckoutSession()` - Creates Stripe checkout
- `getSubscriptionStatus()` - Returns user's subscription
- `cancelSubscription()` - Cancels active subscription

### WebhookController.php
- `handle()` - Receives and verifies Stripe webhooks
- `handleSubscriptionCreated()` - Saves new subscription
- `handleSubscriptionUpdated()` - Updates billing periods on renewal
- `handleInvoicePaymentSucceeded()` - Sets status to active

### RenewalController.php
- `getStatus()` - Returns current renewal status
- `checkAndRenew()` - Checks and processes renewal
- `processRenewal()` - Manual renewal trigger

### SubscriptionFilter.php
- Checks if user has active subscription
- Blocks access to protected pages
- Redirects expired subscriptions to renewal page
- Allows renewal page access when expired

---

## 📋 CLI Commands

### Run Migrations
```bash
php spark migrate
```

### Refresh Database (Reset)
```bash
php spark migrate:refresh --force
```

### Check Expired Subscriptions
```bash
php spark subscriptions:renew-expired
```

### Check Expiring Soon (Warning)
```bash
php spark subscriptions:renew-expired --days=7
```

---

## ⚙️ Setup for Production

### 1. Set Environment
```env
CI_ENVIRONMENT=production
```

### 2. Setup Cron Jobs
```bash
# Check renewals hourly
0 * * * * cd /path/to/project && php spark subscriptions:renew-expired

# Daily expiration warning
0 9 * * * cd /path/to/project && php spark subscriptions:renew-expired --days=7
```

### 3. Configure Webhooks
In Stripe Dashboard:
1. Go to Developers → Webhooks
2. Add endpoint: `https://yourdomain.com/api/webhook`
3. Select events:
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
   - `checkout.session.completed`
4. Copy webhook signing secret to `.env`

### 4. Enable HTTPS
All Stripe requests must use HTTPS in production.

### 5. Database Backups
Regular backups of subscriptions table recommended.

---

## 🚨 Troubleshooting

### Dates Showing 1970-01-01
**Problem**: Webhook timestamp not parsed correctly
**Solution**: 
- Verify webhook received with `stripe trigger customer.subscription.updated`
- Check logs: `writable/logs/log-*.log`
- Timestamps should show as Unix timestamps in logs

### "Already Have Subscription" Error
**Problem**: Can't renew after expiration
**Solution**:
- Check `current_period_end` in database is in the past
- Clear old subscription first or wait for system to mark as expired
- API should auto-allow renewal for expired subscriptions

### Webhooks Not Received
**Problem**: No webhook events arriving
**Solution**:
- Is Stripe CLI running? `stripe listen --forward-to localhost:8080/api/webhook`
- Check webhook secret in `.env` matches CLI output
- Verify server is running and accessible

### API Returns 401 Unauthorized
**Problem**: Can't access protected API
**Solution**:
- Must be logged in for authenticated endpoints
- Login first: `http://localhost:8080/login`
- Some endpoints don't require auth (e.g., `/api/renewal/check/:user_id`)

### Payment Failing
**Problem**: Test payment not processing
**Solution**:
- Use test card: 4242 4242 4242 4242
- Use any future expiry date
- Use any 3-digit CVC
- Ensure database subscription created first

---

## 📚 Documentation Files

For more detailed information, see:

- **[AUTO_RENEWAL_GUIDE.md](AUTO_RENEWAL_GUIDE.md)** - How renewal system works
- **[AUTO_RENEWAL_API.md](AUTO_RENEWAL_API.md)** - API reference with examples
- **[AUTO_RENEWAL_TESTING.md](AUTO_RENEWAL_TESTING.md)** - Testing procedures
- **[AUTO_RENEWAL_IMPLEMENTATION.md](AUTO_RENEWAL_IMPLEMENTATION.md)** - Implementation details
- **[MASTER_GUIDE.md](MASTER_GUIDE.md)** - Complete system overview

---

## 🔒 Security Notes

✅ **Implemented**:
- Password hashing with bcrypt
- CSRF protection via CodeIgniter
- Stripe webhook signature verification
- Session-based authentication
- SQL injection prevention via ORM

⚠️ **For Production**:
- Use HTTPS only
- Set `CI_ENVIRONMENT=production`
- Use strong database passwords
- Regularly update dependencies: `composer update`
- Monitor logs for errors
- Implement rate limiting
- Add email verification

---

## 📦 Dependencies

Main dependencies:
- `codeigniter/framework` - Web framework
- `stripe/stripe-php` - Stripe SDK
- PHP built-in extensions: intl, mbstring, curl, json

See `composer.json` for complete list.

---

## 🤝 Contributing

Feel free to fork, submit issues, and create pull requests!

### How to Contribute
1. Fork the repository
2. Create feature branch (`git checkout -b feature/improvement`)
3. Commit changes (`git commit -am 'Add improvement'`)
4. Push to branch (`git push origin feature/improvement`)
5. Create Pull Request

---

## 📝 License

This project is released under the MIT License.

---

## ❓ FAQ

### Q: Can I use this with production Stripe account?
**A**: Yes! Just switch `STRIPE_SECRET_KEY` and `STRIPE_PUBLIC_KEY` to live keys. The code works identically.

### Q: What payment methods does this support?
**A**: Currently cards only. Stripe Checkout supports Apple Pay, Google Pay, and more - can be enabled in Stripe Dashboard.

### Q: Can I add more pricing plans?
**A**: Yes! Create prices in Stripe Dashboard, add environment variables, and update the `plans()` view and controller.

### Q: How do I handle failed renewals?
**A**: Stripe automatically retries failed payments. The `invoice.payment_failed` webhook sets subscription to `past_due`. You can email reminders to users.

### Q: Can users pause subscriptions?
**A**: Not in current version, but easy to add - create `pause_subscription()` endpoint and update Stripe subscription status.

### Q: How do I offer trial periods?
**A**: Create prices with trial days in Stripe Dashboard. Trial period set automatically.

---

## 📧 Support

For issues and questions:
1. Check troubleshooting section above
2. Review documentation files
3. Check logs in `writable/logs/`
4. Search GitHub issues
5. Create new GitHub issue

---

## 🎉 You're Ready!

You now have a complete, production-ready subscription system. Happy coding!
