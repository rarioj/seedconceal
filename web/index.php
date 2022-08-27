<?php

declare(strict_types=1);
error_reporting(E_ALL ^ E_DEPRECATED);
set_time_limit(0);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/SeedConceal.php';

$sc = new SeedConcealWeb();
$sizes = $sc->getConfig('sizes');
$default_size = $sc->getConfig('default_size');
$default_hash_salt = $sc->getConfig('default_hash_salt');
$default_hash_iteration = $sc->getConfig('default_hash_iteration');
$default_split = $sc->getConfig('default_split');
$languages = $sc->getConfig('languages');
$random_language = $sc->getConfig('random_language');
$default_language = $sc->getConfig('default_language');

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
      <form id="sc-form-generate" class="sc-form" method="post" action="/generate.php">
        <label for="label">
          <strong>Label</strong>
          <input type="text" id="label" name="label">
        </label>
        <label for="passphrase">
          <strong>Passphrase</strong> - <small>Enter a passphrase to generate a deterministic wallet. Leave blank to generate a random wallet.</small>
          <textarea id="passphrase" name="passphrase" rows="5"></textarea>
        </label>
        <label for="salt" id="sc-label-salt">
          <strong>Salt</strong>
          <input type="text" id="salt" name="salt" value="<?php echo $default_hash_salt; ?>">
        </label>
        <label for="iteration" id="sc-label-iteration">
          <strong>Iteration</strong>
          <input type="number" id="iteration" name="iteration" value="<?php echo $default_hash_iteration; ?>">
        </label>
        <label for="password" id="sc-label-password">
          <strong>Password</strong> - <small>Optional but strongly recommended.</small>
          <input type="password" id="password" name="password">
        </label>
        <label for="lang">
          <strong>Byte size</strong>
          <select id="size" name="size">
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
      <form id="sc-form-obscure" class="sc-form" method="post" action="/obscure.php">
        <label for="label">
          <strong>Label</strong> <input type="text" id="label" name="label">
        </label>
        <label for="mnemonic">
          <strong>Seed phrase<sup>*</sup></strong>
          <textarea id="mnemonic" name="mnemonic" rows="5"></textarea>
        </label>
        <label for="password">
          <strong>Password</strong> - <small>Optional but strongly recommended.</small>
          <input type="password" id="password" name="password">
        </label>
        <label for="salt" id="sc-label-salt">
          <strong>Salt</strong>
          <input type="text" id="salt" name="salt" value="<?php echo $default_hash_salt; ?>">
        </label>
        <label for="iteration" id="sc-label-iteration">
          <strong>Iteration</strong>
          <input type="number" id="iteration" name="iteration" value="<?php echo $default_hash_iteration; ?>">
        </label>
        <label for="split">
          <strong>Split into</strong>
          <input type="number" id="split" name="split" value="<?php echo $default_split; ?>">
        </label>
        <label for="lang">
          <strong>Language output</strong>
          <select id="lang" name="lang">
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
      <form id="sc-form-reveal" class="sc-form" method="post" action="/reveal.php">
        <label for="label">
          <strong>Label</strong> <input type="text" id="label" name="label">
        </label>
        <label for="mnemonic">
          <strong>Seed phrase(s)<sup>*</sup></strong> - <small>Enter the seed phrases separated by newlines.</small>
          <textarea id="mnemonic" name="mnemonic" rows="10"></textarea>
        </label>
        <label for="password">
          <strong>Password</strong>
          <input type="password" id="password" name="password">
        </label>
        <label for="salt" id="sc-label-salt">
          <strong>Salt</strong>
          <input type="text" id="salt" name="salt" value="<?php echo $default_hash_salt; ?>">
        </label>
        <label for="iteration" id="sc-label-iteration">
          <strong>Iteration</strong>
          <input type="number" id="iteration" name="iteration" value="<?php echo $default_hash_iteration; ?>">
        </label>
        <p><button type="submit">Submit</button></p>
      </form>
    </div>
  </div>
  <div class="sc-footer">
    <p><a href="https://github.com/rarioj/seedconceal">GitHub</a> &bull; Seed Conceal</p>
  </div>
  <script type="text/javascript" src="/jquery.min.js"></script>
  <script type="text/javascript">
    jQuery(document).ready(function($) {
      $('#sc-form-generate #passphrase').on('keyup', function(e) {
        if ($('#sc-form-generate #passphrase').val().trim().length < 1) {
          $('#sc-form-generate #sc-label-salt').hide();
          $('#sc-form-generate #sc-label-iteration').hide();
          $('#sc-form-generate #sc-label-password').hide();
        } else {
          $('#sc-form-generate #sc-label-salt').show();
          $('#sc-form-generate #sc-label-iteration').show();
          $('#sc-form-generate #sc-label-password').show();
        }
      }).keyup();
      $('#sc-form-obscure #password').on('keyup', function(e) {
        if ($('#sc-form-obscure #password').val().trim().length < 1) {
          $('#sc-form-obscure #sc-label-salt').hide();
          $('#sc-form-obscure #sc-label-iteration').hide();
        } else {
          $('#sc-form-obscure #sc-label-salt').show();
          $('#sc-form-obscure #sc-label-iteration').show();
        }
      }).keyup();
      $('#sc-form-reveal #password').on('keyup', function(e) {
        if ($('#sc-form-reveal #password').val().trim().length < 1) {
          $('#sc-form-reveal #sc-label-salt').hide();
          $('#sc-form-reveal #sc-label-iteration').hide();
        } else {
          $('#sc-form-reveal #sc-label-salt').show();
          $('#sc-form-reveal #sc-label-iteration').show();
        }
      }).keyup();
    });
  </script>
</body>

</html>
