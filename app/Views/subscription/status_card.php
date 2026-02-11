<div class="subscription-status-card">
    <?php if ($subscription): ?>
        <?php 
            $periodEnd = strtotime($subscription['current_period_end']);
            $daysRemaining = ceil(($periodEnd - time()) / 86400);
            $isExpired = $daysRemaining < 0;
            $planName = ucfirst($subscription['plan_type']) . ' Plan';
            $planPrice = $subscription['plan_type'] === 'monthly' ? '$20' : '$200';
        ?>
        
        <div class="card <?= $isExpired ? 'border-danger' : 'border-success' ?>">
            <div class="card-header <?= $isExpired ? 'bg-danger' : 'bg-success' ?> text-white">
                <h5 class="mb-0">
                    <i class="fas <?= $isExpired ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
                    Subscription Status
                </h5>
            </div>
            
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Plan Type:</strong>
                        <p><?= $planName ?> - <?= $planPrice ?>/<?= strtolower($subscription['plan_type'] === 'monthly' ? 'month' : 'year') ?></p>
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong>
                        <p>
                            <span class="badge <?= $isExpired ? 'bg-danger' : 'bg-success' ?>">
                                <?= ucfirst($subscription['status']) ?>
                            </span>
                        </p>
                    </div>
                </div>
                
                <?php if (!$isExpired): ?>
                    <div class="alert alert-info mb-3">
                        <strong>Your subscription renews in <?= $daysRemaining ?> days</strong><br>
                        Next billing date: <strong><?= date('F j, Y', $periodEnd) ?></strong>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger mb-3">
                        <strong>Your subscription expired <?= abs($daysRemaining) ?> days ago</strong><br>
                        Expired on: <strong><?= date('F j, Y', $periodEnd) ?></strong>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <strong>Period Start:</strong>
                        <p><?= date('M d, Y H:i', strtotime($subscription['current_period_start'])) ?></p>
                    </div>
                    <div class="col-md-6">
                        <strong>Period End:</strong>
                        <p><?= date('M d, Y H:i', strtotime($subscription['current_period_end'])) ?></p>
                    </div>
                </div>
                
                <?php if ($isExpired): ?>
                    <div class="mt-3">
                        <a href="<?= base_url('subscription/plans') ?>" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i> Renew Subscription
                        </a>
                    </div>
                <?php else: ?>
                    <div class="mt-3">
                        <div class="btn-group" role="group">
                            <a href="<?= base_url('subscription/plans') ?>" class="btn btn-outline-primary">
                                <i class="fas fa-exchange-alt"></i> Change Plan
                            </a>
                            <form action="<?= base_url('subscription/cancel') ?>" method="POST" style="display: inline;">
                                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to cancel?')">
                                    <i class="fas fa-times"></i> Cancel Subscription
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning" role="alert">
            <h4 class="alert-heading">No Active Subscription</h4>
            <p>You don't have an active subscription. Subscribe now to access premium features.</p>
            <hr>
            <a href="<?= base_url('subscription/plans') ?>" class="btn btn-primary">
                <i class="fas fa-star"></i> Subscribe Now
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
.subscription-status-card {
    margin-bottom: 20px;
}

.subscription-status-card .card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border-radius: 0.5rem;
}

.subscription-status-card .card-header {
    border-radius: 0.5rem 0.5rem 0 0;
    padding: 1.25rem;
}

.subscription-status-card .card-body {
    padding: 1.5rem;
}

.subscription-status-card strong {
    font-weight: 600;
    color: #333;
}

.subscription-status-card p {
    margin-bottom: 0.5rem;
    color: #666;
}

.subscription-status-card .badge {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
}

.subscription-status-card .btn-group {
    gap: 0.5rem;
}

.subscription-status-card .btn {
    margin-right: 0.25rem;
}
</style>
