<?php
$url = $url ?? fn($p = '') => $p;
$heading = (string) ($error_heading ?? '404');
$body = (string) ($error_body ?? '');
$backLabel = (string) ($back_home_label ?? 'Home');
?>
<div class="container py-5">
    <h1 class="mb-3"><?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="mb-4"><?= htmlspecialchars($body, ENT_QUOTES, 'UTF-8') ?></p>
    <a href="<?= $url('') ?>" class="btn btn-primary"><?= htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8') ?></a>
</div>

