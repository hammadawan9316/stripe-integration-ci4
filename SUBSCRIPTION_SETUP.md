# Stripe Subscription Integration - CodeIgniter 4

A complete subscription system with Stripe integration for CodeIgniter 4. This application allows users to subscribe with monthly or yearly plans, and only subscribed users can access protected content.

## Features

- ✅ User registration and authentication
- ✅ Monthly and yearly subscription plans
- ✅ Stripe Checkout integration
- ✅ Webhook handling for subscription events
- ✅ Subscription status tracking
- ✅ Protected routes (only for subscribed users)
- ✅ Customer portal for managing subscriptions
- ✅ Automatic subscription renewal
- ✅ Payment failure handling

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB
- Composer
- Stripe Account
- XAMPP (or similar web server)

## Installation

### 1. Clone/Setup the Project

The project is already in your XAMPP htdocs directory at:
```
c:\xampp\htdocs\stripe-integration-ci4
```

### 2. Install Dependencies

Open terminal in the project directory and run:
```bash
composer install
```

### 3. Database Setup

1. Create a new MySQL database:
```sql
CREATE DATABASE stripe_subscription_db;
```

2. Copy the environment file:
```bash
copy env .env
```

3. Update `.env` file with your database credentials:
```
database.default.hostname = localhost
database.default.database = stripe_subscription_db
database.default.username = root
database.default.password = 
database.default.DBDriver = MySQLi
```

4. Run migrations to create tables:
```bash
php spark migrate
```

### 4. Stripe Configuration

#### Step 1: Get Stripe API Keys

1. Go to [Stripe Dashboard](https://dashboard.stripe.com/)
2. Navigate to **Developers > API Keys**
3. Copy your **Publishable key** and **Secret key**
4. Add them to `.env`:
```
STRIPE_PUBLISHABLE_KEY=pk_test_your_key_here
STRIPE_SECRET_KEY=sk_test_your_secret_key_here
```

#### Step 2: Create Products and Prices

1. In Stripe Dashboard, go to **Products**
2. Click **+ Add Product**
3. Create a product (e.g., "Premium Subscription")
4. Add two prices:
   - **Monthly**: $9.99/month (recurring)
   - **Yearly**: $99.99/year (recurring)
5. Copy the Price IDs and add to `.env`:
```
STRIPE_MONTHLY_PRICE_ID=price_xxxxxxxxxxxxx
STRIPE_YEARLY_PRICE_ID=price_xxxxxxxxxxxxx
```

#### Step 3: Setup Webhook

1. In Stripe Dashboard, go to **Developers > Webhooks**
2. Click **+ Add endpoint**
3. Set endpoint URL: `http://yourdomain.com/api/webhook`
   - For local testing with Stripe CLI: `http://localhost/stripe-integration-ci4/api/webhook`
4. Select events to listen:
   - `checkout.session.completed`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
5. Copy the **Signing secret** and add to `.env`:
```
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxx
```

### 5. Configure Base URL

Update the base URL in `.env`:
```
app.baseURL = 'http://localhost/stripe-integration-ci4/'
```

## Testing Webhooks Locally

For local development, use the Stripe CLI:

1. Install [Stripe CLI](https://stripe.com/docs/stripe-cli)
2. Login to Stripe:
```bash
stripe login
```
3. Forward webhooks to your local server:
```bash
stripe listen --forward-to localhost/stripe-integration-ci4/api/webhook
```
4. Use the webhook signing secret provided by the CLI in your `.env`

## Usage

### 1. Start Your Server

Start XAMPP and ensure Apache and MySQL are running.

### 2. Access the Application

Open browser and navigate to:
```
http://localhost/stripe-integration-ci4/
```

### 3. User Flow

1. **Register**: Go to `/register` to create an account
2. **Choose Plan**: Select monthly or yearly subscription plan
3. **Checkout**: Complete payment through Stripe Checkout
4. **Access Dashboard**: After successful payment, access protected content at `/dashboard`

### 4. Test Cards

Use Stripe test cards for testing:
- **Success**: 4242 4242 4242 4242
- **Decline**: 4000 0000 0000 0002
- Use any future expiry date and any CVC

## API Endpoints

### Subscription APIs

- `GET /api/subscription/plans` - Get available plans
- `POST /api/subscription/checkout` - Create checkout session
- `GET /api/subscription/status/{userId}` - Get user subscription status
- `POST /api/subscription/cancel` - Cancel subscription
- `POST /api/subscription/portal` - Create customer portal session

### Webhook

- `POST /api/webhook` - Handle Stripe webhooks

## Database Schema

### Users Table
- `id` - Primary key
- `email` - User email (unique)
- `password` - Hashed password
- `name` - User name
- `stripe_customer_id` - Stripe customer ID
- `created_at`, `updated_at` - Timestamps

### Subscriptions Table
- `id` - Primary key
- `user_id` - Foreign key to users
- `stripe_subscription_id` - Stripe subscription ID
- `stripe_price_id` - Stripe price ID
- `plan_type` - monthly or yearly
- `status` - active, canceled, past_due, incomplete, trialing
- `current_period_start`, `current_period_end` - Subscription period
- `canceled_at` - Cancellation timestamp
- `created_at`, `updated_at` - Timestamps

## File Structure

```
app/
├── Controllers/
│   ├── AuthController.php              # User authentication
│   ├── SubscriptionViewController.php  # Subscription views
│   └── Api/
│       ├── SubscriptionController.php  # Subscription API
│       └── WebhookController.php       # Webhook handler
├── Models/
│   ├── UserModel.php                   # User model
│   └── SubscriptionModel.php          # Subscription model
├── Filters/
│   └── SubscriptionFilter.php         # Subscription access filter
├── Views/
│   ├── auth/
│   │   ├── register.php               # Registration form
│   │   └── login.php                  # Login form
│   ├── subscription/
│   │   ├── plans.php                  # Subscription plans
│   │   ├── success.php                # Success page
│   │   └── cancel.php                 # Cancel page
│   └── dashboard.php                  # Protected dashboard
└── Database/
    └── Migrations/
        ├── CreateUsersTable.php
        └── CreateSubscriptionsTable.php
```

## Protected Routes

To protect a route with subscription requirement, add the `subscription` filter:

```php
$routes->get('protected-page', 'YourController::method', ['filter' => 'subscription']);
```

## Customer Portal

Users can manage their subscriptions (update payment method, cancel, etc.) through the Stripe Customer Portal. Click "Manage Subscription" button on the dashboard.

## Webhook Events Handled

- **checkout.session.completed**: Initial checkout completed
- **customer.subscription.created**: New subscription created
- **customer.subscription.updated**: Subscription updated (renewal, etc.)
- **customer.subscription.deleted**: Subscription canceled
- **invoice.payment_succeeded**: Payment successful
- **invoice.payment_failed**: Payment failed

## Security Notes

- Never commit your `.env` file with real API keys
- Always use HTTPS in production
- Verify webhook signatures to prevent fake events
- Use Stripe test mode during development
- Store sensitive data securely

## Troubleshooting

### Webhooks Not Working
- Check webhook URL is accessible from internet
- Verify webhook secret in `.env`
- Check logs at `writable/logs/`
- Use Stripe CLI for local testing

### Subscription Not Activating
- Check webhook events are being received
- Verify price IDs match in `.env`
- Check database subscription status
- Review Stripe Dashboard events

### Database Errors
- Ensure migrations have run successfully
- Check database credentials in `.env`
- Verify MySQL service is running

## Support

For Stripe-specific issues, refer to:
- [Stripe Documentation](https://stripe.com/docs)
- [Stripe API Reference](https://stripe.com/docs/api)

For CodeIgniter 4 issues, refer to:
- [CodeIgniter 4 Documentation](https://codeigniter.com/user_guide/)

## License

This project is open-source and available under the MIT License.
