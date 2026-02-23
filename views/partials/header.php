<?php
$url = $url ?? fn($p = '') => $p;
$asset = $asset ?? fn($p) => $p;
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="<?= $url('') ?>">
      <img src="<?= $asset('images/logo.png') ?>" alt="Logo" width="40" height="34" class="d-inline-block align-text-top me-2">
      <span class="fw-semibold">Gundam</span>
    </a>
    <form class="d-none d-lg-flex w-100 p-0 m-0 navbar-search-form" action="<?= $url('search') ?>" method="GET">
      <div class="input-group">
        <input class="form-control border-primary" name="search" type="search" placeholder="搜尋喜愛產品或諮詢" aria-label="Search">
        <button class="btn btn-primary" type="submit"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg></button>
      </div>
    </form>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <form class="d-lg-none mb-3 px-3" action="<?= $url('search') ?>" method="GET">
        <div class="input-group">
          <input class="form-control" name="search" type="search" placeholder="搜尋喜愛產品或諮詢" aria-label="Search">
          <button class="btn btn-outline-primary" type="submit">搜尋</button>
        </div>
      </form>
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
        <li class="nav-item">
          <a class="nav-link position-relative" href="<?= $url('cart') ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-cart3" viewBox="0 0 16 16"><path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.472L4.415 11H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.14 5l.5 2.5H12.36l-.8-4H3.94l-.8 4zM3 8.5l.5 2.5h8.22l.6-3H3.5zM13.5 15a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm-10 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/></svg>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary cart-badge" id="cart-count">0</span>
          </a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="<?= isset($_SESSION['email']) ? $url('account') : '#' ?>" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <?= isset($_SESSION['email']) ? '<span class="me-1">' . htmlspecialchars($_SESSION['user_name'] ?? '用戶') . '</span>' : '帳戶' ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
            <?php if (isset($_SESSION['email'])): ?>
            <li><a class="dropdown-item" href="<?= $url('account') ?>">我的帳號</a></li>
            <li><a class="dropdown-item" href="<?= $url('account/settings') ?>">重置密碼</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-primary" href="<?= $url('logout') ?>">登出</a></li>
            <?php else: ?>
            <li><a class="dropdown-item" href="<?= $url('login') ?>">登入</a></li>
            <li><a class="dropdown-item" href="<?= $url('register') ?>">註冊</a></li>
            <?php endif; ?>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
