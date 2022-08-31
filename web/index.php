<?php

declare(strict_types=1);
error_reporting(E_ALL ^ E_DEPRECATED);
set_time_limit(0);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/SeedConceal.php';

$sc = new SeedConcealWeb();
$sizes = $sc->config('sizes');
$default_size = $sc->config('default_size');
$default_hash_salt = $sc->config('default_hash_salt');
$default_hash_iteration = $sc->config('default_hash_iteration');
$default_split = $sc->config('default_split');
$languages = $sc->config('languages');
$random_language = $sc->config('random_language');
$default_language = $sc->config('default_language');

?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>Seed Conceal</title>
  <link rel="stylesheet" href="/style.css">
</head>

<body>
  <div class="sc-container sc-first">
    <div class="sc-inner">
      <div class="sc-heading">Generate</div>
      <form id="sc-form-generate" class="sc-form" method="post" action="/generate.php" autocomplete="off">
        <label for="generate-label">
          <strong>Label</strong>
          <input type="text" id="generate-label" name="label">
        </label>
        <label for="generate-passphrase">
          <strong>Passphrase</strong>
          <input type="text" id="generate-passphrase" name="passphrase">
          <small>&bull; Enter a passphrase to generate a <strong>deterministic</strong> wallet or leave blank to generate a <strong>random</strong> wallet</small>
        </label>
        <label for="generate-salt" id="label-generate-salt" style="display: none;">
          <strong>Salt</strong>
          <input type="text" id="generate-salt" name="salt" value="<?php echo $default_hash_salt; ?>">
          <small>&bull; Optional but strongly recommended</small>
        </label>
        <label for="generate-iteration" id="label-generate-iteration" style="display: none;">
          <strong>Iteration</strong>
          <input type="number" id="generate-iteration" name="iteration" value="<?php echo $default_hash_iteration; ?>" min="1">
        </label>
        <label for="generate-password" id="label-generate-password" style="display: none;">
          <strong>Password</strong>
          <input type="password" id="generate-password" name="password">
          <small>&bull; Optional but strongly recommended</small>
        </label>
        <label for="generate-size">
          <strong>Byte size</strong>
          <select id="generate-size" name="size">
            <?php foreach ($sizes as $size_id => $size_label) { ?>
              <option value="<?php echo $size_id; ?>" <?php if ($size_id === $default_size) echo 'selected="selected"'; ?>><?php echo $size_label; ?> words</option>
            <?php } ?>
          </select>
        </label>
        <p><button type="submit">Submit</button></p>
      </form>
    </div>
  </div>
  <div class="sc-container">
    <div class="sc-inner">
      <div class="sc-heading">Obscure</div>
      <form id="sc-form-obscure" class="sc-form" method="post" action="/obscure.php" autocomplete="off">
        <label for="obscure-label">
          <strong>Label</strong>
          <input type="text" id="obscure-label" name="label">
        </label>
        <label for="obscure-mnemonic">
          <strong>Seed phrase<sup>*</sup></strong>
          <input type="text" id="obscure-mnemonic" name="mnemonic">
        </label>
        <label for="obscure-password">
          <strong>Password</strong>
          <input type="password" id="obscure-password" name="password">
          <small>&bull; Optional but strongly recommended</small>
        </label>
        <label for="obscure-salt" id="label-obscure-salt" style="display: none;">
          <strong>Salt</strong>
          <input type="text" id="obscure-salt" name="salt" value="<?php echo $default_hash_salt; ?>">
          <small>&bull; Optional but strongly recommended</small>
        </label>
        <label for="obscure-iteration" id="label-obscure-iteration" style="display: none;">
          <strong>Iteration</strong>
          <input type="number" id="obscure-iteration" name="iteration" value="<?php echo $default_hash_iteration; ?>" min="1">
        </label>
        <label for="obscure-split">
          <strong>Split into</strong>
          <input type="number" id="obscure-split" name="split" value="<?php echo $default_split; ?>" min="1">
        </label>
        <label for="obscure-lang">
          <strong>Language output</strong>
          <select id="obscure-lang" name="lang">
            <option value="<?php echo $random_language; ?>">Random</option>
            <?php foreach ($languages as $lang_id => $lang_label) { ?>
              <option value="<?php echo $lang_id; ?>" <?php if ($lang_id === $default_language) echo 'selected="selected"'; ?>><?php echo $lang_label; ?></option>
            <?php } ?>
          </select>
        </label>
        <p><button type="submit">Submit</button></p>
      </form>
    </div>
  </div>
  <div class="sc-container">
    <div class="sc-inner">
      <div class="sc-heading">Reveal</div>
      <form id="sc-form-reveal" class="sc-form" method="post" action="/reveal.php" autocomplete="off">
        <label for="reveal-label">
          <strong>Label</strong>
          <input type="text" id="reveal-label" name="label">
        </label>
        <label for="reveal-mnemonic">
          <strong>Seed phrase(s)<sup>*</sup></strong>
          <textarea id="reveal-mnemonic" name="mnemonic" rows="10"></textarea>
          <small>&bull; Enter each seed phrase separated by newlines</small>
        </label>
        <label for="reveal-password">
          <strong>Password</strong>
          <input type="password" id="reveal-password" name="password">
        </label>
        <label for="reveal-salt" id="label-reveal-salt" style="display: none;">
          <strong>Salt</strong>
          <input type="text" id="reveal-salt" name="salt" value="<?php echo $default_hash_salt; ?>">
        </label>
        <label for="reveal-iteration" id="label-reveal-iteration" style="display: none;">
          <strong>Iteration</strong>
          <input type="number" id="reveal-iteration" name="iteration" value="<?php echo $default_hash_iteration; ?>" min="1">
        </label>
        <p><button type="submit">Submit</button></p>
      </form>
    </div>
  </div>
  <div class="sc-footer">
    <p><a href="https://github.com/rarioj/seedconceal">GitHub</a> &bull; Seed Conceal</p>
  </div>
  <script type="text/javascript">
    document.getElementById('generate-passphrase').onkeyup = function() {
      if (document.getElementById('generate-passphrase').value.trim().length < 1) {
        document.getElementById('label-generate-salt').style.display = 'none';
        document.getElementById('label-generate-iteration').style.display = 'none';
        document.getElementById('label-generate-password').style.display = 'none';
      } else {
        document.getElementById('label-generate-salt').style.display = 'block';
        document.getElementById('label-generate-iteration').style.display = 'block';
        document.getElementById('label-generate-password').style.display = 'block';
      }
    };
    document.getElementById('obscure-password').onkeyup = function() {
      if (document.getElementById('obscure-password').value.trim().length < 1) {
        document.getElementById('label-obscure-salt').style.display = 'none';
        document.getElementById('label-obscure-iteration').style.display = 'none';
      } else {
        document.getElementById('label-obscure-salt').style.display = 'block';
        document.getElementById('label-obscure-iteration').style.display = 'block';
      }
    };
    document.getElementById('reveal-password').onkeyup = function() {
      if (document.getElementById('reveal-password').value.trim().length < 1) {
        document.getElementById('label-reveal-salt').style.display = 'none';
        document.getElementById('label-reveal-iteration').style.display = 'none';
      } else {
        document.getElementById('label-reveal-salt').style.display = 'block';
        document.getElementById('label-reveal-iteration').style.display = 'block';
      }
    };
  </script>
</body>

</html>
