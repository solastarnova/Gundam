<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
?>
<footer class="footer-dark mt-auto">
    <div class="footer-pattern"></div>
    <div class="footer-inner py-5">
      <div class="row g-4">
        <!-- 左欄：品牌 + 聯絡 -->
        <div class="col-lg-4 col-md-6">
          <a href="<?= $url('') ?>" class="footer-brand d-inline-flex align-items-center mb-3">
            <img src="<?= $asset('images/logo.png') ?>" alt="Logo" width="40" height="34" class="me-2">
            <span class="footer-brand-text">Gundam 模型商城</span>
          </a>
          <p class="footer-desc small text-secondary mb-4">專營高達模型、手辦與周邊，為您搜羅最新與經典款式。</p>
          <p class="mb-2 text-secondary">wolaiwagandomigo@gmail.com</p>
          <p class="mb-4 text-secondary">WhatsApp: +852 8000 5000</p>
          <p class="footer-legal small text-secondary mb-0">Daito, Gilded City, IOI-Resistance Zone</p>
        </div>
        <!-- 企業 -->
        <div class="col-lg-2 col-md-6">
          <h6 class="footer-heading text-uppercase text-secondary mb-3">企業</h6>
          <ul class="list-unstyled footer-links mb-0">
            <li class="mb-2"><a href="<?= $url('about') ?>">關於我們</a></li>
            <li class="mb-2"><a href="<?= $url('faq') ?>">常見問題</a></li>
            <li><a href="<?= $url('faq') ?>#contact">聯絡我們</a></li>
          </ul>
        </div>
        <!-- 探索 -->
        <div class="col-lg-2 col-md-6">
          <h6 class="footer-heading text-uppercase text-secondary mb-3">探索</h6>
          <ul class="list-unstyled footer-links mb-0">
            <li class="mb-2"><a href="<?= $url('') ?>">首頁</a></li>
            <li class="mb-2"><a href="<?= $url('products') ?>">更多產品</a></li>
            <li><a href="<?= $url('faq') ?>">FAQ</a></li>
          </ul>
        </div>
        <!-- 更多資訊 -->
        <div class="col-lg-2 col-md-6">
          <h6 class="footer-heading text-uppercase text-secondary mb-3">更多資訊</h6>
          <ul class="list-unstyled footer-links mb-0">
            <li class="mb-2"><a href="<?= $url('privacy') ?>">隱私條款</a></li>
            <li class="mb-2"><a href="<?= $url('terms') ?>">服務條款</a></li>
          </ul>
          <h6 class="footer-heading text-uppercase text-secondary mt-4 mb-2">營業時間</h6>
          <p class="small text-secondary mb-0">Mon–Fri: 8am–9pm<br>Sat–Sun: 8am–1am</p>
        </div>
      </div>
    </div>
    <div class="footer-bottom text-center py-3">
      © 2025 Gundam 模型商城. All rights reserved.
    </div>
</footer>
<script>
async function updateCartBadge() {
  var badge = document.getElementById('cart-count');
  if (!badge) return;
  if (!window.isLoggedIn) { badge.textContent = '0'; badge.style.display = 'none'; return; }
  try {
    var base = window.APP_BASE || '';
    var res = await fetch(base + 'api/cart/count');
    var data = await res.json();
    var total = (data && typeof data.count === 'number') ? data.count : 0;
    badge.textContent = total;
    badge.style.display = total > 0 ? 'block' : 'none';
  } catch (e) {
    badge.textContent = '0';
    badge.style.display = 'none';
  }
}
document.querySelectorAll('.sidebar .nav-link').forEach(function(link) {
  link.addEventListener('click', function() {
    document.querySelectorAll('.sidebar .nav-link').forEach(function(l) { l.classList.remove('active'); });
    this.classList.add('active');
  });
});
window.addEventListener('load', updateCartBadge);
</script>
