<?php
$url = $url ?? fn($p = '') => $p;
$account_nav_active = 'orders';
?>
<div class="container account-page my-5 pt-5">
    <div class="row account-layout">
        <?php include __DIR__ . '/../partials/account_sidebar.php'; ?>
        <div class="col-lg-9 col-md-8">
            <div class="account-main-card account-main-padding">
                <div class="mb-4">
                    <h4 class="mb-4"><?= htmlspecialchars(__m('account.orders.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                    <p class="page-subtitle"><?= htmlspecialchars(__m('account.orders.subtitle'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="row mb-4 g-3">
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card bg-success bg-opacity-10 border border-success border-opacity-25">
                            <div class="stat-card-value text-success" id="totalTransactions">0</div>
                            <div class="stat-card-label"><?= htmlspecialchars(__m('account.orders.stat_total'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card bg-primary bg-opacity-10 border border-primary border-opacity-25">
                            <div class="stat-card-value text-primary" id="totalAmount">0</div>
                            <div class="stat-card-label"><?= htmlspecialchars(__m('account.orders.stat_amount'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card bg-info bg-opacity-10 border border-info border-opacity-25">
                            <div class="stat-card-value text-info" id="successCount">0</div>
                            <div class="stat-card-label"><?= htmlspecialchars(__m('account.orders.stat_success'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card bg-warning bg-opacity-10 border border-warning border-opacity-25">
                            <div class="stat-card-value text-warning" id="pendingCount">0</div>
                            <div class="stat-card-label"><?= htmlspecialchars(__m('account.orders.stat_pending'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                </div>
                <div class="filter-section mb-4">
                    <div class="row g-3">
                        <div class="col-lg-3 col-md-6">
                            <label for="filterStatus" class="filter-label"><?= htmlspecialchars(__m('account.orders.filter_status'), ENT_QUOTES, 'UTF-8') ?></label>
                            <select class="form-select" id="filterStatus" onchange="filterTransactions()">
                                <option value=""><?= htmlspecialchars(__m('account.orders.filter_all'), ENT_QUOTES, 'UTF-8') ?></option>
                                <option value="completed"><?= htmlspecialchars(__m('account.orders.status_completed'), ENT_QUOTES, 'UTF-8') ?></option>
                                <option value="pending"><?= htmlspecialchars(__m('account.orders.status_pending'), ENT_QUOTES, 'UTF-8') ?></option>
                                <option value="cancelled"><?= htmlspecialchars(__m('account.orders.status_cancelled'), ENT_QUOTES, 'UTF-8') ?></option>
                                <option value="paid"><?= htmlspecialchars(__m('account.orders.status_paid'), ENT_QUOTES, 'UTF-8') ?></option>
                                <option value="shipped"><?= htmlspecialchars(__m('account.orders.status_shipped'), ENT_QUOTES, 'UTF-8') ?></option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label for="searchTransaction" class="filter-label"><?= htmlspecialchars(__m('account.orders.search_label'), ENT_QUOTES, 'UTF-8') ?></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="searchTransaction" placeholder="<?= htmlspecialchars(__m('account.orders.search_placeholder'), ENT_QUOTES, 'UTF-8') ?>" onkeyup="filterTransactions()">
                                <span class="input-group-text bg-white border-start-0"><i class="bi bi-search"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card account-card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover transaction-table mb-0">
                                <thead>
                                    <tr>
                                        <th><?= htmlspecialchars(__m('account.orders.th_txn_id'), ENT_QUOTES, 'UTF-8') ?></th>
                                        <th><?= htmlspecialchars(__m('account.orders.th_date'), ENT_QUOTES, 'UTF-8') ?></th>
                                        <th><?= htmlspecialchars(__m('account.orders.th_desc'), ENT_QUOTES, 'UTF-8') ?></th>
                                        <th><?= htmlspecialchars(__m('account.orders.th_amount'), ENT_QUOTES, 'UTF-8') ?></th>
                                        <th><?= htmlspecialchars(__m('account.orders.th_pay_status'), ENT_QUOTES, 'UTF-8') ?></th>
                                        <th><?= htmlspecialchars(__m('account.orders.th_action'), ENT_QUOTES, 'UTF-8') ?></th>
                                    </tr>
                                </thead>
                                <tbody id="transactionTableBody"></tbody>
                            </table>
                        </div>
                        <div id="emptyState" class="empty-state-table">
                            <div class="empty-state-icon">📋</div>
                            <div class="empty-state-text"><?= htmlspecialchars(__m('account.orders.empty'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.ORDERS_PAGE = <?= json_encode([
    'statusPending' => __m('account.orders.status_pending'),
    'statusPaid' => __m('account.orders.status_paid'),
    'statusShipped' => __m('account.orders.status_shipped'),
    'statusCompleted' => __m('account.orders.status_completed'),
    'statusCancelled' => __m('account.orders.status_cancelled'),
    'itemsTpl' => __m('account.orders.items_count_tpl'),
    'viewDetail' => __m('account.orders.view_detail'),
    'loadErr' => __m('account.orders.console_load_error'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
(function() {
    const base = window.APP_BASE || '';
    const O = window.ORDERS_PAGE || {};
    const formatMoney = (typeof window.formatMoney === 'function')
        ? window.formatMoney
        : function(v) {
            const cfg = window.APP_CURRENCY || {};
            const amount = Number(v || 0);
            try {
                if (cfg.code) {
                    return new Intl.NumberFormat(cfg.locale || 'zh-HK', {
                        style: 'currency',
                        currency: cfg.code,
                        minimumFractionDigits: Number.isInteger(cfg.decimals) ? cfg.decimals : 2,
                        maximumFractionDigits: Number.isInteger(cfg.decimals) ? cfg.decimals : 2
                    }).format(amount);
                }
            } catch (e) {}
            return (cfg.symbol || '') + amount.toFixed(Number.isInteger(cfg.decimals) ? cfg.decimals : 2);
        };
    const statusClass = { pending: 'text-warning', paid: 'text-info', shipped: 'text-primary', completed: 'text-success', cancelled: 'text-danger' };
    const statusText = {
        pending: O.statusPending || '',
        paid: O.statusPaid || '',
        shipped: O.statusShipped || '',
        completed: O.statusCompleted || '',
        cancelled: O.statusCancelled || ''
    };
    const initialOrders = <?= json_encode($orders ?? []) ?>;
    let allOrders = [];

    function filterTransactions() {
        const status = (document.getElementById('filterStatus') || {}).value || '';
        const searchId = ((document.getElementById('searchTransaction') || {}).value || '').trim().toLowerCase();
        const tbody = document.getElementById('transactionTableBody');
        const emptyState = document.getElementById('emptyState');
        let shown = 0;
        allOrders.forEach(function(order) {
            const matchStatus = !status || order.status === status;
            const matchId = !searchId || (order.order_number || '').toLowerCase().indexOf(searchId) >= 0;
            const show = matchStatus && matchId;
            const row = tbody.querySelector('[data-order-id="' + order.id + '"]');
            if (row) {
                row.style.display = show ? '' : 'none';
                if (show) shown++;
            }
        });
        emptyState.style.display = 'none';
    }

    async function loadTransactions() {
        try {
            if (initialOrders && initialOrders.length > 0) {
                allOrders = initialOrders;
            } else {
                const response = await fetch(base + 'api/orders');
                const result = await response.json();
                allOrders = (result && result.success && Array.isArray(result.orders)) ? result.orders : [];
            }
            const tbody = document.getElementById('transactionTableBody');
            const emptyState = document.getElementById('emptyState');

            if (allOrders.length === 0) {
                tbody.innerHTML = '';
                tbody.style.display = 'none';
                emptyState.style.display = 'block';
                document.getElementById('totalTransactions').textContent = '0';
                document.getElementById('totalAmount').textContent = formatMoney(0);
                document.getElementById('successCount').textContent = '0';
                document.getElementById('pendingCount').textContent = '0';
                return;
            }

            tbody.style.display = '';
            emptyState.style.display = 'none';
            let totalAmount = 0, successCount = 0, pendingCount = 0;
            tbody.innerHTML = '';

            allOrders.forEach(function(order) {
                totalAmount += parseFloat(order.total_amount) || 0;
                if (order.status === 'completed' || order.status === 'shipped' || order.status === 'paid') successCount++;
                if (order.status === 'pending') pendingCount++;
                const sc = statusClass[order.status] || 'text-muted';
                const st = statusText[order.status] || order.status;
                const row = document.createElement('tr');
                row.setAttribute('data-order-id', order.id);
                const desc = (O.itemsTpl || '').replace(/\{\{n\}\}/g, String(order.item_count || 0));
                row.innerHTML = '<td>' + (order.order_number || '') + '</td><td>' + (order.created_at ? new Date(order.created_at).toLocaleDateString() : '') + '</td><td>' + desc + '</td><td class="fw-bold">' + formatMoney(parseFloat(order.total_amount) || 0) + '</td><td><span class="' + sc + '">' + st + '</span></td><td><a href="' + base + 'account/order/' + order.id + '" class="btn btn-sm btn-outline-info">' + (O.viewDetail || '') + '</a></td>';
                tbody.appendChild(row);
            });

            document.getElementById('totalTransactions').textContent = allOrders.length;
            document.getElementById('totalAmount').textContent = formatMoney(totalAmount);
            document.getElementById('successCount').textContent = successCount;
            document.getElementById('pendingCount').textContent = pendingCount;
            window.filterTransactions = filterTransactions;
        } catch (e) {
            console.error((O.loadErr || '') + '', e);
        }
    }
    document.addEventListener('DOMContentLoaded', loadTransactions);
})();
</script>
