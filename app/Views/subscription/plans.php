<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Your Plan</title>
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 50px;
        }
        
        .header h1 {
            font-size: 42px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .plans {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .plan-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            position: relative;
            transition: transform 0.3s;
        }
        
        .plan-card:hover {
            transform: translateY(-5px);
        }
        
        .plan-card.popular {
            border: 3px solid #667eea;
        }
        
        .popular-badge {
            position: absolute;
            top: -15px;
            right: 30px;
            background: #667eea;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .plan-name {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        
        .plan-price {
            font-size: 48px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .plan-price span {
            font-size: 18px;
            color: #666;
        }
        
        .plan-interval {
            color: #999;
            margin-bottom: 30px;
        }
        
        .plan-features {
            list-style: none;
            margin-bottom: 30px;
        }
        
        .plan-features li {
            padding: 12px 0;
            color: #666;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .plan-features li:before {
            content: "✓";
            color: #667eea;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .alert-error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background-color: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        .savings {
            background: #ffe;
            color: #f90;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Choose Your Plan</h1>
            <p>Subscribe to unlock premium content and features</p>
        </div>
        
        <?php if (session()->has('error')): ?>
            <div class="alert alert-error">
                <?= session('error') ?>
            </div>
        <?php endif; ?>
        
        <?php if (session()->has('success')): ?>
            <div class="alert alert-success">
                <?= session('success') ?>
            </div>
        <?php endif; ?>
        
        <div class="plans">
            <?php if (empty($plans)): ?>
                <div class="alert alert-error">
                    No subscription plans are available right now. Please try again later.
                </div>
            <?php else: ?>
                <?php foreach ($plans as $plan): ?>
                    <?php
                        $isYearly = $plan['plan_key'] === 'yearly';
                        $price = number_format((float)$plan['price'], 2);
                        $intervalShort = $plan['interval'] === 'year' ? 'yr' : $plan['interval'];
                        $intervalLabel = $plan['interval'] === 'year' ? 'annually' : 'monthly';
                        $buttonLabel = 'Subscribe ' . $plan['name'];
                    ?>
                    <div class="plan-card<?= $isYearly ? ' popular' : '' ?>">
                        <?php if ($isYearly): ?>
                            <div class="popular-badge">Most Popular</div>
                        <?php endif; ?>
                        <div class="plan-name"><?= esc($plan['name']) ?></div>
                        <div class="plan-price">$<?= esc($price) ?><span>/<?= esc($intervalShort) ?></span></div>
                        <div class="plan-interval">Billed <?= esc($intervalLabel) ?></div>
                        <?php if ($isYearly): ?>
                            <div class="savings">Best value</div>
                        <?php endif; ?>
                        
                        <ul class="plan-features">
                            <li>Full access to all content</li>
                            <li>Unlimited downloads</li>
                            <li>Priority support</li>
                            <li>Cancel anytime</li>
                        </ul>
                        
                        <button class="btn subscribe-btn" data-plan="<?= esc($plan['plan_key']) ?>" data-label="<?= esc($buttonLabel) ?>">
                            <?= esc($buttonLabel) ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        const stripe = Stripe('<?= env('STRIPE_PUBLISHABLE_KEY') ?>');
        const userId = <?= session()->get('user_id') ?? 'null' ?>;
        
        if (!userId) {
            alert('Please login first');
            window.location.href = '<?= base_url('login') ?>';
        }
        
        document.querySelectorAll('.subscribe-btn').forEach(button => {
            button.addEventListener('click', async () => {
                const planType = button.getAttribute('data-plan');
                const buttonLabel = button.getAttribute('data-label');
                button.disabled = true;
                button.textContent = 'Processing...';
                
                try {
                    const response = await fetch('<?= base_url('api/subscription/checkout') ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            user_id: userId,
                            plan_type: planType
                        })
                    });
                    
                    const data = await response.json();
                    
                    console.log('Response:', data); // Debug log
                    
                    // Check if status is 200 (success) and we have session_url
                    if (response.ok && data.data && data.data.session_url) {
                        // Redirect to Stripe Checkout
                        console.log('Redirecting to:', data.data.session_url);
                        window.location.href = data.data.session_url;
                    } else {
                        console.error('Checkout failed:', data);
                        alert(data.message || 'Error creating checkout session');
                        button.disabled = false;
                        button.textContent = buttonLabel;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    button.disabled = false;
                    button.textContent = buttonLabel;
                }
            });
        });
    </script>
</body>
</html>
