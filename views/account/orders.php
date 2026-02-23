<?php
$url = $url ?? fn($p = '') => $p;
?>
<div class="container my-5 pt-5">
    <div class="row">
        <div class="col-lg-3 col-md-4">
            <div class="sidebar">
                <h5 class="px-4 mb-4 text-dark fw-bold">我的帳戶</h5>
                <div class="nav flex-column">
                    <a href="<?= $url('account') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-person me-2"></i> 個人資料</a>
                    <a href="<?= $url('account/orders') ?>" class="nav-link d-flex align-items-center active"><i class="bi bi-bag me-2"></i> 訂單記錄</a>
                    <a href="<?= $url('wishlist') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-heart me-2"></i> 喜愛清單</a>
                    <a href="#coupons" class="nav-link d-flex align-items-center"><i class="bi bi-ticket-perforated me-2"></i> 優惠券</a>
                    <a href="<?= $url('account/addresses') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-geo-alt me-2"></i> 預設地址</a>
                    <a href="#payment" class="nav-link d-flex align-items-center"><i class="bi bi-credit-card me-2"></i> 付款方式</a>
                    <a class="nav-link d-flex" href="<?= $url('account/settings') ?>"> 修改密碼</a>
                    <a class="nav-link d-flex text-primary" href="<?= $url('logout') ?>"> 登出</a>
                </div>
            </div>
        </div>
        <div class="bg-white rounded shadow-sm col-lg-9 col-md-8">
            <div class="py-3">
                <div class="mb-4">
                    <h4 class="mb-4">訂單記錄</h4>
                    <p class="page-subtitle">查看您的所有交易歷史和支付狀態</p>
                </div>
                <div class="row mb-4 g-3">
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card bg-success bg-opacity-10 border border-success border-opacity-25">
                            <div class="stat-card-value text-success" id="totalTransactions">0</div>
                            <div class="stat-card-label">總交易筆數</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card bg-primary bg-opacity-10 border border-primary border-opacity-25">
                            <div class="stat-card-value text-primary" id="totalAmount">HK$0</div>
                            <div class="stat-card-label">總交易金額</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card bg-info bg-opacity-10 border border-info border-opacity-25">
                            <div class="stat-card-value text-info" id="successCount">0</div>
                            <div class="stat-card-label">成功交易</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card bg-warning bg-opacity-10 border border-warning border-opacity-25">
                            <div class="stat-card-value text-warning" id="pendingCount">0</div>
                            <div class="stat-card-label">待處理</div>
                        </div>
                    </div>
                </div>
                <div class="filter-section mb-4">
                    <div class="row g-3">
                        <div class="col-lg-3 col-md-6">
                            <label for="filterStatus" class="filter-label">支付狀態</label>
                            <select class="form-select" id="filterStatus" onchange="filterTransactions()">
                                <option value="">全部狀態</option>
                                <option value="completed">已完成</option>
                                <option value="pending">待處理</option>
                                <option value="cancelled">已取消</option>
                                <option value="paid">已付款</option>
                                <option value="shipped">已發貨</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label for="searchTransaction" class="filter-label">搜尋交易 ID</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="searchTransaction" placeholder="輸入交易 ID" onkeyup="filterTransactions()">
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
                                        <th>交易 ID</th>
                                        <th>日期</th>
                                        <th>說明</th>
                                        <th>金額</th>
                                        <th>支付狀態</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody id="transactionTableBody"></tbody>
                            </table>
                        </div>
                        <div id="emptyState" class="empty-state-table">
                            <div class="empty-state-icon">📋</div>
                            <div class="empty-state-text">暫無交易記錄</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const base = window.APP_BASE || '';
    const statusClass = { pending: 'text-warning', paid: 'text-info', shipped: 'text-primary', completed: 'text-success', cancelled: 'text-danger' };
    const statusText = { pending: '待處理', paid: '已付款', shipped: '已發貨', completed: '已完成', cancelled: '已取消' };
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
                const orders = await response.json();
                allOrders = Array.isArray(orders) ? orders : [];
            }
            const tbody = document.getElementById('transactionTableBody');
            const emptyState = document.getElementById('emptyState');

            if (allOrders.length === 0) {
                tbody.innerHTML = '';
                tbody.style.display = 'none';
                emptyState.style.display = 'block';
                document.getElementById('totalTransactions').textContent = '0';
                document.getElementById('totalAmount').textContent = 'HK$0';
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
                row.innerHTML = '<td>' + (order.order_number || '') + '</td><td>' + (order.created_at ? new Date(order.created_at).toLocaleDateString() : '') + '</td><td>' + (order.item_count || 0) + ' 件商品</td><td class="fw-bold">HK$' + (parseFloat(order.total_amount) || 0).toFixed(2) + '</td><td><span class="' + sc + '">' + st + '</span></td><td><a href="' + base + 'account/order/' + order.id + '" class="btn btn-sm btn-outline-info">查看詳情</a></td>';
                tbody.appendChild(row);
            });

            document.getElementById('totalTransactions').textContent = allOrders.length;
            document.getElementById('totalAmount').textContent = 'HK$' + totalAmount.toFixed(2);
            document.getElementById('successCount').textContent = successCount;
            document.getElementById('pendingCount').textContent = pendingCount;
            window.filterTransactions = filterTransactions;
        } catch (e) {
            console.error('載入交易記錄時出錯:', e);
        }
    }
    document.addEventListener('DOMContentLoaded', loadTransactions);
})();
</script>
