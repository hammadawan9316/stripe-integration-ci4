<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stripe Subscription Starter</title>
    <meta name="description" content="A ready-to-ship Stripe subscription starter for CodeIgniter 4.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style {csp-style-nonce}>
        :root {
            --ink: #1a1f2b;
            --ink-soft: #3d4454;
            --sand: #f6f1ea;
            --sun: #ffb347;
            --copper: #d56a2e;
            --teal: #2c7a7b;
            --mint: #c8f3e1;
            --white: #ffffff;
            --shadow: 0 24px 60px rgba(26, 31, 43, 0.18);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Space Grotesk", "Segoe UI", sans-serif;
            color: var(--ink);
            background: radial-gradient(circle at top left, #fff2d7 0%, #f6f1ea 42%, #eaf3f4 100%);
            min-height: 100vh;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .shell {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 24px 80px;
        }

        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 56px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--sun), var(--copper));
            box-shadow: 0 10px 25px rgba(213, 106, 46, 0.3);
            position: relative;
        }

        .brand-mark::after {
            content: "";
            position: absolute;
            width: 14px;
            height: 14px;
            background: var(--white);
            border-radius: 50%;
            top: 8px;
            left: 8px;
            box-shadow: 14px 14px 0 0 rgba(255, 255, 255, 0.8);
        }

        .nav-links {
            display: flex;
            gap: 18px;
            font-weight: 500;
        }

        .nav-links a {
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(26, 31, 43, 0.06);
        }

        .hero {
            display: grid;
            gap: 40px;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            align-items: center;
            margin-bottom: 80px;
        }

        .hero h1 {
            font-family: "Fraunces", serif;
            font-size: clamp(2.6rem, 3.5vw, 3.9rem);
            line-height: 1.05;
            margin-bottom: 18px;
        }

        .hero p {
            font-size: 1.1rem;
            color: var(--ink-soft);
            margin-bottom: 26px;
            max-width: 520px;
        }

        .cta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn {
            border: none;
            padding: 14px 22px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--sun), var(--copper));
            color: var(--ink);
            box-shadow: 0 12px 28px rgba(213, 106, 46, 0.28);
        }

        .btn-ghost {
            background: rgba(26, 31, 43, 0.08);
            color: var(--ink);
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .hero-card {
            background: var(--white);
            border-radius: 24px;
            padding: 28px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .hero-card::before {
            content: "";
            position: absolute;
            inset: -40% 40% auto auto;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, var(--mint), transparent 70%);
            opacity: 0.9;
        }

        .hero-card h3 {
            font-size: 1.2rem;
            margin-bottom: 18px;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 14px;
        }

        .stat {
            background: var(--sand);
            border-radius: 16px;
            padding: 14px;
        }

        .stat strong {
            display: block;
            font-size: 1.4rem;
        }

        .section {
            margin-bottom: 80px;
        }

        .section h2 {
            font-family: "Fraunces", serif;
            font-size: clamp(2rem, 3vw, 2.6rem);
            margin-bottom: 18px;
        }

        .section p {
            color: var(--ink-soft);
            max-width: 620px;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 18px;
            margin-top: 28px;
        }

        .feature {
            padding: 20px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(26, 31, 43, 0.08);
        }

        .feature span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            margin-bottom: 12px;
            background: var(--mint);
            color: var(--teal);
            font-weight: 700;
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-top: 24px;
        }

        .step {
            padding: 18px;
            border-radius: 18px;
            background: var(--white);
            box-shadow: 0 12px 28px rgba(26, 31, 43, 0.12);
        }

        .step h4 {
            margin-bottom: 8px;
        }

        .callout {
            display: grid;
            gap: 12px;
            align-items: center;
            grid-template-columns: 1.2fr 0.8fr;
            background: linear-gradient(120deg, #1f2a44, #2c7a7b);
            color: var(--white);
            padding: 28px;
            border-radius: 22px;
        }

        .callout p {
            color: rgba(255, 255, 255, 0.8);
        }

        .callout .btn {
            justify-content: center;
            background: var(--sun);
        }

        footer {
            text-align: center;
            color: var(--ink-soft);
            font-size: 0.95rem;
            margin-top: 60px;
        }

        @media (max-width: 768px) {
            .nav {
                flex-direction: column;
                align-items: flex-start;
            }

            .callout {
                grid-template-columns: 1fr;
            }
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate {
            animation: fadeUp 0.6s ease forwards;
        }
    </style>
</head>
<body>
    <div class="shell">
        <nav class="nav">
            <div class="brand">
                <div class="brand-mark"></div>
                Stripe Starter
            </div>
            <div class="nav-links">
                <a href="<?= base_url('subscription/plans') ?>">Plans</a>
                <a href="<?= base_url('login') ?>">Login</a>
                <a href="<?= base_url('register') ?>">Register</a>
            </div>
        </nav>

        <section class="hero">
            <div class="animate">
                <h1>Launch subscriptions fast with CodeIgniter + Stripe.</h1>
                <p>Everything you need to ship a paid membership flow: plans, checkout, webhooks, renewals, and access control. Plug it into any project and go live today.</p>
                <div class="cta-row">
                    <a class="btn btn-primary" href="<?= base_url('subscription/plans') ?>">View Plans</a>
                    <a class="btn btn-ghost" href="<?= base_url('register') ?>">Create Account</a>
                </div>
            </div>
            <div class="hero-card animate">
                <h3>Live system status</h3>
                <div class="stat-grid">
                    <div class="stat">
                        <strong>2</strong>
                        Plans ready
                    </div>
                    <div class="stat">
                        <strong>6</strong>
                        Webhook events
                    </div>
                    <div class="stat">
                        <strong>3</strong>
                        Renewal APIs
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <h2>Built for production, simple enough for demos.</h2>
            <p>Use the full Stripe subscription lifecycle without rebuilding the basics. Works in test mode and production with the same flow.</p>
            <div class="feature-grid">
                <div class="feature">
                    <span>01</span>
                    <h4>Checkout + Billing</h4>
                    <p>Hosted Stripe checkout with monthly and yearly plans, ready to customize.</p>
                </div>
                <div class="feature">
                    <span>02</span>
                    <h4>Webhook Sync</h4>
                    <p>Stay in sync with Stripe events so your database never drifts.</p>
                </div>
                <div class="feature">
                    <span>03</span>
                    <h4>Access Control</h4>
                    <p>Protect routes with a subscription filter and auto-renew logic.</p>
                </div>
                <div class="feature">
                    <span>04</span>
                    <h4>Plan Management</h4>
                    <p>Switch plans, cancel, and handle expiration gracefully.</p>
                </div>
            </div>
        </section>

        <section class="section">
            <h2>How it works in three steps.</h2>
            <div class="steps">
                <div class="step">
                    <h4>1. Configure keys</h4>
                    <p>Add Stripe keys and price IDs in your .env file and seed plans.</p>
                </div>
                <div class="step">
                    <h4>2. Collect subscriptions</h4>
                    <p>Users pick a plan, checkout via Stripe, and get instant access.</p>
                </div>
                <div class="step">
                    <h4>3. Renew automatically</h4>
                    <p>Webhooks and cron checks keep subscription periods current.</p>
                </div>
            </div>
        </section>

        <section class="section callout">
            <div>
                <h2>Ready to plug in and ship?</h2>
                <p>Use this starter as your subscription foundation. You can add trials, coupons, and more in minutes.</p>
            </div>
            <a class="btn" href="<?= base_url('subscription/plans') ?>">View Plans</a>
        </section>

        <footer>
            <p>&copy; <?= date('Y') ?> Stripe Subscription Starter. Built with CodeIgniter 4.</p>
        </footer>
    </div>

    <script {csp-script-nonce}>
        const animated = document.querySelectorAll('.animate');
        animated.forEach((node, index) => {
            node.style.animationDelay = `${index * 0.12}s`;
        });
    </script>
</body>
</html>
