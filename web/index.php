<?php

declare(strict_types=1);
error_reporting(E_ALL ^ E_DEPRECATED);

$config = require_once __DIR__ . '/../config.php';

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
      <form class="sc-form" method="post" action="/generate.php">
        <label for="label">
          <strong>Label</strong> <input type="text" id="label" name="label">
        </label>
        <label for="lang">
          <strong>Mnemonic size<sup>*</sup></strong>
          <select id="size" name="size">
            <?php foreach ($config['sizes'] as $size_id => $size_label) { ?>
              <option value="<?php echo $size_id; ?>"><?php echo $size_label; ?> words</option>
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
      <form class="sc-form" method="post" action="/obscure.php">
        <label for="label">
          <strong>Label</strong> <input type="text" id="label" name="label">
        </label>
        <label for="mnemonic">
          <strong>Valid mnemonic<sup>*</sup></strong> <textarea id="mnemonic" name="mnemonic" rows="5"></textarea>
        </label>
        <label for="password">
          <strong>Password<sup>*</sup></strong> <input type="password" id="password" name="password">
        </label>
        <label for="split">
          <strong>Split into<sup>*</sup></strong>
          <select id="split" name="split">
            <?php foreach ($config['splits'] as $split_id => $split_label) { ?>
              <option value="<?php echo $split_id; ?>"><?php echo $split_label; ?></option>
            <?php } ?>
          </select>
        </label>
        <label for="lang">
          <strong>Language output<sup>*</sup></strong>
          <select id="lang" name="lang">
            <option value="-">Random</option>
            <?php foreach ($config['languages'] as $lang_id => $lang_label) { ?>
              <option value="<?php echo $lang_id; ?>" <?php if ($lang_id === 'en') echo 'selected="selected"'; ?>><?php echo $lang_label; ?></option>
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
      <form class="sc-form" method="post" action="/reveal.php">
        <label for="label">
          <strong>Label</strong> <input type="text" id="label" name="label">
        </label>
        <label for="mnemonic">
          <strong>Obscured mnemonic<sup>*</sup></strong> <textarea id="mnemonic" name="mnemonic" rows="15"></textarea>
        </label>
        <label for="password">
          <strong>Password<sup>*</sup></strong> <input type="password" id="password" name="password">
        </label>
        <p><button type="submit">Submit</button></p>
      </form>
    </div>
  </div>
  <div class="sc-footer">
    <p><a href="https://github.com/rarioj/seedconceal">GitHub</a> &bull; Seed Conceal</p>
  </div>
</body>

</html>
