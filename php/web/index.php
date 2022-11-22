<?php

declare(strict_types=1);
error_reporting(E_ALL ^ E_DEPRECATED);
set_time_limit(0);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../SeedConceal.php';

$sc = new SeedConcealWeb();
$sizes = $sc->config('sizes');
$default_label = $sc->config('default_label');
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
  <title>Seed Conceal</title>
  <link rel="stylesheet" href="/mini.css">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta charset="utf-8">
</head>

<body>
  <header class="sticky" style="height: auto;">
    <h1><span class="icon-home"></span> Seed Conceal</h1>
  </header>
  <div class="container">
    <div class="row">
      <div class="col-sm-4">
        <div class="section">
          <h2>Generate</h2>
        </div>
        <form method="post" action="/generate.php" autocomplete="off">
          <div class="input-group vertical">
            <label for="generate-label"><strong>Label</strong></label>
            <input type="text" id="generate-label" name="label" value="<?php echo $default_label; ?>">
          </div>
          <div class="input-group vertical">
            <label for="generate-passphrase"><strong>Passphrase</strong><br /><small>Enter a passphrase to generate a <strong>deterministic</strong> wallet or leave blank to generate a <strong>random</strong> wallet</small></label>
            <input type="text" id="generate-passphrase" name="passphrase">
          </div>
          <div class="input-group vertical hidden" id="label-generate-salt">
            <label for="generate-salt"><strong>Salt</strong><br /><small>Optional but strongly recommended</small></label>
            <input type="text" id="generate-salt" name="salt" value="<?php echo $default_hash_salt; ?>">
          </div>
          <div class="input-group vertical hidden" id="label-generate-iteration">
            <label for="generate-iteration"><strong>Iteration</strong></label>
            <input type="number" id="generate-iteration" name="iteration" value="<?php echo $default_hash_iteration; ?>" min="1">
          </div>
          <div class="input-group vertical hidden" id="label-generate-password">
            <label for="generate-password"><strong>Password</strong><br /><small>Optional but strongly recommended</small></label>
            <input type="password" id="generate-password" name="password">
          </div>
          <div class="input-group vertical" id="label-generate-size">
            <label for="generate-size"><strong>Byte size</strong></label>
            <select id="generate-size" name="size">
              <?php foreach ($sizes as $size_id => $size_label) { ?>
                <option value="<?php echo $size_id; ?>" <?php if ($size_id === $default_size) echo 'selected="selected"'; ?>><?php echo $size_label; ?> words</option>
              <?php } ?>
            </select>
          </div>
          <div class="input-group fluid">
            <button class="inverse shadowed" type="submit">Submit</button>
          </div>
        </form>
      </div>
      <div class="col-sm-4">
        <div class="section">
          <h2>Obscure</h2>
        </div>
        <form method="post" action="/obscure.php" autocomplete="off">
          <div class="input-group vertical">
            <label for="obscure-label"><strong>Label</strong></label>
            <input type="text" id="obscure-label" name="label" value="<?php echo $default_label; ?>">
          </div>
          <div class="input-group vertical">
            <label for="obscure-mnemonic"><strong>Seed phrase<sup>*</sup></strong></label>
            <input type="text" id="obscure-mnemonic" name="mnemonic">
          </div>
          <div class="input-group vertical">
            <label for="obscure-password"><strong>Password</strong><br /><small>Optional but strongly recommended</small></label>
            <input type="password" id="obscure-password" name="password">
          </div>
          <div class="input-group vertical hidden" id="label-obscure-salt">
            <label for="obscure-salt"><strong>Salt</strong><br /><small>Optional but strongly recommended</small></label>
            <input type="text" id="obscure-salt" name="salt" value="<?php echo $default_hash_salt; ?>">
          </div>
          <div class="input-group vertical hidden" id="label-obscure-iteration">
            <label for="obscure-iteration"><strong>Iteration</strong></label>
            <input type="number" id="obscure-iteration" name="iteration" value="<?php echo $default_hash_iteration; ?>" min="1">
          </div>
          <div class="input-group vertical" for="obscure-split">
            <label for="obscure-split"><strong>Split into</strong></label>
            <input type="number" id="obscure-split" name="split" value="<?php echo $default_split; ?>" min="1">
          </div>
          <div class="input-group vertical">
            <label for="obscure-lang"><strong>Language output</strong></label>
            <select id="obscure-lang" name="lang">
              <option value="<?php echo $random_language; ?>">Random</option>
              <?php foreach ($languages as $lang_id => $lang_label) { ?>
                <option value="<?php echo $lang_id; ?>" <?php if ($lang_id === $default_language) echo 'selected="selected"'; ?>><?php echo $lang_label; ?></option>
              <?php } ?>
            </select>
          </div>
          <div class="input-group fluid">
            <button class="inverse shadowed" type="submit">Submit</button>
          </div>
        </form>
      </div>
      <div class="col-sm-4">
        <div class="section">
          <h2>Reveal</h2>
        </div>
        <form method="post" action="/reveal.php" autocomplete="off">
          <div class="input-group vertical">
            <label for="reveal-label"><strong>Label</strong></label>
            <input type="text" id="reveal-label" name="label" value="<?php echo $default_label; ?>">
          </div>
          <div class="input-group vertical">
            <label for="reveal-mnemonic"><strong>Seed phrase(s)<sup>*</sup></strong><br /><small>Enter each seed phrase separated by newlines</small></label>
            <textarea id="reveal-mnemonic" name="mnemonic" rows="5"></textarea>
          </div>
          <div class="input-group vertical">
            <label for="reveal-password"><strong>Password</strong></label>
            <input type="password" id="reveal-password" name="password">
          </div>
          <div class="input-group vertical hidden" id="label-reveal-salt">
            <label for="reveal-salt"><strong>Salt</strong></label>
            <input type="text" id="reveal-salt" name="salt" value="<?php echo $default_hash_salt; ?>">
          </div>
          <div class="input-group vertical hidden" id="label-reveal-iteration">
            <label for="reveal-iteration"><strong>Iteration</strong></label>
            <input type="number" id="reveal-iteration" name="iteration" value="<?php echo $default_hash_iteration; ?>" min="1">
          </div>
          <div class="input-group fluid">
            <button class="inverse shadowed" type="submit">Submit</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script type="text/javascript">
    document.getElementById('generate-passphrase').onkeyup = function() {
      if (document.getElementById('generate-passphrase').value.trim().length < 1) {
        document.getElementById('label-generate-salt').classList.add('hidden');
        document.getElementById('label-generate-password').classList.add('hidden');
        document.getElementById('label-generate-size').classList.remove('hidden');
        document.getElementById('generate-salt').value = '';
        document.getElementById('label-generate-iteration').classList.add('hidden');
      } else {
        document.getElementById('label-generate-salt').classList.remove('hidden');
        document.getElementById('label-generate-password').classList.remove('hidden');
        document.getElementById('label-generate-size').classList.add('hidden');
      }
    };
    document.getElementById('generate-salt').onkeyup = function() {
      if (document.getElementById('generate-salt').value.trim().length < 1) {
        document.getElementById('label-generate-iteration').classList.add('hidden');
      } else {
        document.getElementById('label-generate-iteration').classList.remove('hidden');
      }
    };
    document.getElementById('obscure-password').onkeyup = function() {
      if (document.getElementById('obscure-password').value.trim().length < 1) {
        document.getElementById('label-obscure-salt').classList.add('hidden');
        document.getElementById('obscure-salt').value = '';
        document.getElementById('label-obscure-iteration').classList.add('hidden');
      } else {
        document.getElementById('label-obscure-salt').classList.remove('hidden');
      }
    };
    document.getElementById('obscure-salt').onkeyup = function() {
      if (document.getElementById('obscure-salt').value.trim().length < 1) {
        document.getElementById('label-obscure-iteration').classList.add('hidden');
      } else {
        document.getElementById('label-obscure-iteration').classList.remove('hidden');
      }
    };
    document.getElementById('reveal-password').onkeyup = function() {
      if (document.getElementById('reveal-password').value.trim().length < 1) {
        document.getElementById('label-reveal-salt').classList.add('hidden');
        document.getElementById('reveal-salt').value = '';
        document.getElementById('label-reveal-iteration').classList.add('hidden');
      } else {
        document.getElementById('label-reveal-salt').classList.remove('hidden');
      }
    };
    document.getElementById('reveal-salt').onkeyup = function() {
      if (document.getElementById('reveal-salt').value.trim().length < 1) {
        document.getElementById('label-reveal-iteration').classList.add('hidden');
      } else {
        document.getElementById('label-reveal-iteration').classList.remove('hidden');
      }
    };
  </script>
</body>

</html>