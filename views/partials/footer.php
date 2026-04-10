<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
?>
<footer class="footer-dark mt-auto">
    <div class="footer-pattern"></div>
    <div class="footer-inner py-5">
      <div class="row g-4">
        <div class="col-lg-4 col-md-6">
          <a href="<?= $url('') ?>" class="footer-brand d-inline-flex align-items-center mb-3">
            <img src="<?= $asset('images/logo.png') ?>" alt="Logo" width="40" height="34" class="me-2">
            <span class="footer-brand-text"><?= htmlspecialchars(__m('footer.brand_text'), ENT_QUOTES, 'UTF-8') ?></span>
          </a>
          <p class="footer-desc small text-secondary mb-4"><?= htmlspecialchars(__m('footer.desc'), ENT_QUOTES, 'UTF-8') ?></p>
          <p class="mb-2 text-secondary">wolaiwagandomigo@gmail.com</p>
          <p class="mb-4 text-secondary">WhatsApp: +852 8000 5000</p>
          <p class="footer-legal small text-secondary mb-0">Daito, Gilded City, IOI-Resistance Zone</p>
        </div>
        <div class="col-lg-2 col-md-6">
          <h6 class="footer-heading text-uppercase text-secondary mb-3"><?= htmlspecialchars(__m('footer.heading_corp'), ENT_QUOTES, 'UTF-8') ?></h6>
          <ul class="list-unstyled footer-links mb-0">
            <li class="mb-2"><a href="<?= $url('about') ?>"><?= htmlspecialchars(__m('footer.about'), ENT_QUOTES, 'UTF-8') ?></a></li>
            <li class="mb-2"><a href="<?= $url('faq') ?>"><?= htmlspecialchars(__m('footer.faq'), ENT_QUOTES, 'UTF-8') ?></a></li>
            <li><a href="<?= $url('faq') ?>#contact"><?= htmlspecialchars(__m('footer.contact'), ENT_QUOTES, 'UTF-8') ?></a></li>
          </ul>
        </div>
        <div class="col-lg-2 col-md-6">
          <h6 class="footer-heading text-uppercase text-secondary mb-3"><?= htmlspecialchars(__m('footer.heading_explore'), ENT_QUOTES, 'UTF-8') ?></h6>
          <ul class="list-unstyled footer-links mb-0">
            <li class="mb-2"><a href="<?= $url('') ?>"><?= htmlspecialchars(__m('footer.home'), ENT_QUOTES, 'UTF-8') ?></a></li>
            <li class="mb-2"><a href="<?= $url('products') ?>"><?= htmlspecialchars(__m('footer.more_products'), ENT_QUOTES, 'UTF-8') ?></a></li>
            <li><a href="<?= $url('faq') ?>"><?= htmlspecialchars(__m('footer.faq_link'), ENT_QUOTES, 'UTF-8') ?></a></li>
          </ul>
        </div>
        <div class="col-lg-2 col-md-6">
          <h6 class="footer-heading text-uppercase text-secondary mb-3"><?= htmlspecialchars(__m('footer.heading_legal'), ENT_QUOTES, 'UTF-8') ?></h6>
          <ul class="list-unstyled footer-links mb-0">
            <li class="mb-2"><a href="<?= $url('privacy') ?>"><?= htmlspecialchars(__m('footer.privacy'), ENT_QUOTES, 'UTF-8') ?></a></li>
            <li class="mb-2"><a href="<?= $url('terms') ?>"><?= htmlspecialchars(__m('footer.terms'), ENT_QUOTES, 'UTF-8') ?></a></li>
          </ul>
          <h6 class="footer-heading text-uppercase text-secondary mt-4 mb-2"><?= htmlspecialchars(__m('footer.heading_hours'), ENT_QUOTES, 'UTF-8') ?></h6>
          <p class="small text-secondary mb-0"><?= __m('footer.hours_body') ?></p>
        </div>
      </div>
    </div>
    <div class="footer-bottom text-center py-3">
      <?= htmlspecialchars(__m('footer.copyright', (int) date('Y')), ENT_QUOTES, 'UTF-8') ?>
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
