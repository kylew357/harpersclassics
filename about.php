<?php
require __DIR__ . '/includes/layout.php';

$s = site();
$paras = about_paragraphs();
$img = is_file(__DIR__ . '/assets/about.jpg') ? 'assets/about.jpg' : '';

page_header('About', 'about', ['description' => $paras[0]]);
?>
<section class="page-head">
  <div class="wrap">
    <p class="eyebrow dark"><?= $s['since'] ? 'Since ' . h($s['since']) : 'Our story' ?></p>
    <h1>About us</h1>
  </div>
</section>

<section class="wrap list-section">
  <div class="split about-split">
    <div class="prose">
<?php foreach ($paras as $p): ?>      <p><?= h($p) ?></p>
<?php endforeach; ?>
      <a class="btn btn-primary" href="inventory.php">See what we have</a>
    </div>
<?php if ($img): ?>
    <div class="about-photo"><img src="<?= h($img) ?>" alt=""></div>
<?php endif; ?>
  </div>
</section>
<?php page_footer(); ?>
