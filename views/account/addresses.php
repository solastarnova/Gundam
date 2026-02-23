<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
$addresses = $addresses ?? [];
?>
<div class="container my-5 pt-5">
    <div class="row">
        <div class="col-lg-3 col-md-4">
            <div class="sidebar">
                <h5 class="px-4 mb-4 text-dark fw-bold">我的帳戶</h5>
                <div class="nav flex-column">
                    <a href="<?= $url('account') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-person me-2"></i> 個人資料</a>
                    <a href="<?= $url('account/orders') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-bag me-2"></i> 訂單記錄</a>
                    <a href="<?= $url('wishlist') ?>" class="nav-link d-flex align-items-center"><i class="bi bi-heart me-2"></i> 喜愛清單</a>
                    <a href="#coupons" class="nav-link d-flex align-items-center"><i class="bi bi-ticket-perforated me-2"></i> 優惠券</a>
                    <a href="<?= $url('account/addresses') ?>" class="nav-link d-flex align-items-center active"><i class="bi bi-geo-alt me-2"></i> 預設地址</a>
                    <a href="#payment" class="nav-link d-flex align-items-center"><i class="bi bi-credit-card me-2"></i> 付款方式</a>
                    <a href="<?= $url('account/settings') ?>" class="nav-link d-flex align-items-center"> 修改密碼</a>
                    <a class="nav-link d-flex text-primary" href="<?= $url('logout') ?>"> 登出</a>
                </div>
            </div>
        </div>
        <div class="bg-white rounded shadow-sm col-lg-9 col-md-8">
            <div class="py-4 px-4">
                <div class="mb-4">
                    <h4 class="mb-2">管理收貨地址</h4>
                    <p class="page-subtitle text-muted mb-0">管理您的收貨地址，方便快速結帳</p>
                </div>

                <div id="addressesList">
                    <?php if (empty($addresses)): ?>
                        <div class="border rounded p-5 text-center">
                            <p class="text-muted mb-4">目前還沒有添加任何地址</p>
                            <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addressModal" onclick="openAddModal()">新增第一個地址</button>
                        </div>
                    <?php else: ?>
                        <div class="row g-3 mb-4">
                            <?php foreach ($addresses as $address): ?>
                                <div class="col-12">
                                    <div class="border rounded p-3 address-card <?= $address['is_default'] ? 'border-primary' : '' ?>"
                                         <?php if (!$address['is_default']): ?> onclick="setDefault(<?= (int)$address['id'] ?>)" style="cursor: pointer;" title="點擊設為預設地址"
                                         <?php else: ?> title="這是預設地址" <?php endif; ?>>
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <?php if ($address['is_default']): ?><span class="badge bg-danger">預設地址</span><?php endif; ?>
                                                <?php if (!empty($address['address_label'])): ?><h5 class="mb-0"><?= htmlspecialchars($address['address_label']) ?></h5><?php endif; ?>
                                            </div>
                                            <div onclick="event.stopPropagation();">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-dark" onclick="openEditModal(<?= (int)$address['id'] ?>)">編輯</button>
                                                    <button type="button" class="btn btn-outline-danger" onclick="deleteAddress(<?= (int)$address['id'] ?>)">刪除</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="address-details small">
                                            <p class="mb-1"><strong>收件人：</strong><?= htmlspecialchars($address['recipient_name']) ?></p>
                                            <p class="mb-1"><strong>電話：</strong><?= htmlspecialchars($address['phone']) ?></p>
                                            <p class="mb-1"><strong>地址類型：</strong><?= htmlspecialchars($address['address_type']) ?></p>
                                            <p class="mb-0"><strong>地址：</strong>
                                            <?php
                                            $unit = $address['unit'] ?? '';
                                            if ($unit && ctype_digit($unit) && strpos($unit, '室') === false) { $unit = $unit . '室'; }
                                            $addressParts = [ $address['region'], $address['district'], $address['village_estate'] ?: $address['street'], $address['building'], !empty($address['floor']) ? $address['floor'] . '樓' : '', $unit ];
                                            echo htmlspecialchars(implode(' ', array_filter($addressParts)));
                                            ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mb-4"><button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addressModal" onclick="openAddModal()">+ 新增地址</button></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 地址表單 Modal -->
<div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addressModalLabel">新增地址</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addressForm">
                    <input type="hidden" id="addressId" name="id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="addressLabel" class="form-label">地址標籤 <span class="text-muted">(可選)</span></label>
                            <input type="text" class="form-control" id="addressLabel" name="address_label" placeholder="例如：住宅、公司">
                        </div>
                        <div class="col-md-6">
                            <label for="addressType" class="form-label">地址類型</label>
                            <select class="form-select" id="addressType" name="address_type" required>
                                <option value="住宅">住宅</option>
                                <option value="商業">商業</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="recipientName" class="form-label">收件人姓名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="recipientName" name="recipient_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">聯絡電話 <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="col-md-4">
                            <label for="region" class="form-label">地區 <span class="text-danger">*</span></label>
                            <select class="form-select" id="region" name="region" required>
                                <option value="">請選擇</option>
                                <option value="香港島">香港島</option>
                                <option value="九龍">九龍</option>
                                <option value="新界">新界</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label for="district" class="form-label">區域 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="district" name="district" required placeholder="例如：中環、尖沙咀">
                        </div>
                        <div class="col-12">
                            <label class="form-label">地址詳細 <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                <div class="col-md-6"><input type="text" class="form-control" id="villageEstate" name="village_estate" placeholder="屋邨/屋苑名稱（選填）"></div>
                                <div class="col-md-6"><input type="text" class="form-control" id="street" name="street" placeholder="街道（含號碼，選填）"></div>
                            </div>
                            <small class="text-muted">屋邨/屋苑名稱和街道至少填寫一項</small>
                        </div>
                        <div class="col-md-6">
                            <label for="building" class="form-label">大廈/樓宇名稱 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="building" name="building" required>
                        </div>
                        <div class="col-md-3">
                            <label for="floor" class="form-label">樓層 <span class="text-muted">(可選)</span></label>
                            <input type="text" class="form-control" id="floor" name="floor">
                        </div>
                        <div class="col-md-3">
                            <label for="unit" class="form-label">單位號碼 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="unit" name="unit" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="isDefault" name="is_default" value="1">
                                <label class="form-check-label" for="isDefault">設為預設地址</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-dark" onclick="saveAddress()">儲存</button>
            </div>
        </div>
    </div>
</div>
