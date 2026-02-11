<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar h1 {
            font-size: 24px;
        }
        
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .welcome-card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .welcome-card h2 {
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .welcome-card p {
            color: #666;
            font-size: 18px;
        }
        
        .subscription-info {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .subscription-info h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 20px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-label {
            color: #999;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #333;
            font-size: 18px;
            font-weight: 600;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .content-section {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .content-section h3 {
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
        }
        
        .content-section p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 15px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.2s;
            margin-top: 20px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h1>Premium Dashboard</h1>
            <div class="navbar-right">
                <div class="user-info">
                    <span><?= esc(session()->get('user_name')) ?></span>
                </div>
                <a href="<?= base_url('logout') ?>" class="logout-btn">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <?php if (session()->has('success')): ?>
            <div class="alert alert-success">
                <?= session('success') ?>
            </div>
        <?php endif; ?>
        
        <div class="welcome-card">
            <h2>Welcome, <?= esc(session()->get('user_name')) ?>! 🎉</h2>
            <p>You have full access to all premium content and features.</p>
        </div>
        
        <?php if ($subscription): ?>
        <div class="subscription-info">
            <h3>Your Subscription</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span class="status-badge status-active">
                            <?= ucfirst(esc($subscription['status'])) ?>
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Plan</div>
                    <div class="info-value"><?= ucfirst(esc($subscription['plan_type'])) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Current Period End</div>
                    <div class="info-value">
                        <?= date('M d, Y', strtotime($subscription['current_period_end'])) ?>
                    </div>
                </div>
            </div>
            <button class="btn btn-secondary" id="manageSubscription">Manage Subscription</button>
        </div>
        <?php endif; ?>
        
        <div class="content-section">
            <h3>Premium Content</h3>
            <p>Congratulations! You now have access to all our premium features and content.</p>
            <p>This is protected content that only subscribers can view. You can now:</p>
            <ul style="color: #666; line-height: 2; margin-left: 20px;">
                <li>Access all premium articles and tutorials</li>
                <li>Download exclusive resources</li>
                <li>Join premium community discussions</li>
                <li>Get priority support</li>
                <li>Access advanced features</li>
            </ul>
            <p>Thank you for being a valued subscriber! We're constantly adding new content and features.</p>
        </div>
    </div>
    
    <script>
        const userId = <?= session()->get('user_id') ?? 'null' ?>;
        
        document.getElementById('manageSubscription')?.addEventListener('click', async () => {
            try {
                const response = await fetch('<?= base_url('api/subscription/portal') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ user_id: userId })
                });
                
                const data = await response.json();
                
                if (data.status === 'success' && data.data.url) {
                    window.location.href = data.data.url;
                } else {
                    alert(data.message || 'Error opening portal');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            }
        });
    </script>
</body>
</html>
