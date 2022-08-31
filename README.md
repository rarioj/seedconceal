# SeedConceal

SeedConceal is a simple set of tools to generate a wallet seed phrase (random or deterministic), obscure an existing seed phrase, and reveal obscured seed phrases.

## Requirements

- [PHP 7.4+ or 8+](https://www.php.net/)
- [Composer 2+](https://getcomposer.org/)

## Usage

```
git clone https://github.com/rarioj/seedconceal.git
cd seedconceal
composer install
```

### Disable your internet connection before generating, obscuring, or revealing seed phrases.

By creating an air-gapped system, you can guarantee that your operating system will not interact with anything on the internet. You can reenable the internet once your seed phrases are securely stored.

#### Web Mode

![SeedConceal Web Mode](app/images/seedconceal-web.png)

```
cd web
php -S localhost:9000
```

Open a web browser and then go to `http://localhost:9000/`. You can adjust the port number to suit your need.

On the web version, clicking on the generated seed phrases or the QR codes will allow you to capture the seed phrase card and save it as an image.

#### CLI Mode

![SeedConceal CLI Mode](app/images/seedconceal-cli.png)

```
php cli/generate.php
php cli/obscure.php
php cli/reveal.php
```

Follow the interactive prompt for each tool.

## Tools

### Generate

This tool generates a valid seed phrase for a wallet using a deterministic or random approach. Only use this tool if you plan to create a new wallet or divulge a deterministic wallet seed phrase by entering all the required parameters.

When generating a new wallet, leaving the passphrase field blank will let you use PHP's secure [random_bytes()](https://www.php.net/manual/en/function.random-bytes.php) function.

With a deterministic approach, please understand the risk of generating this type of wallet. See the [Speed Optimizations in Bitcoin Key Recovery Attacks](https://eprint.iacr.org/2016/103.pdf) paper. Unlike traditional Brain wallet which hashes a passphrase to generate a **private key**, SeedConceal will hash, XOR, salt, and iterate passphrase to generate **entropy**. Hence, this deterministic wallet generation will produce a seed phrase as well. The intent is to conceal your seed phrase by not remembering 12 or 24 words mnemonic, but by remembering your private passphrase, password, and salt.

Ensure the passphrase used is unique, private, and never exposed on the internet. Using a password will reduce the attack vector by [XOR-ing](https://www.php.net/manual/en/function.gmp-xor.php) it with a hashed passphrase. The [hash salt and the number of iterations](https://www.php.net/manual/en/function.hash-pbkdf2.php) are essential when revealing the original mnemonic.

### Obscure

This tool can obscure your existing wallet mnemonic by securing it with a password, splitting it into multiple seed phrases, and translating it to other languages. Use this tool if you plan to conceal an existing seed phrase.

- **Securing with password:** Using a password will add additional security measures by [XOR-ing](https://www.php.net/manual/en/function.gmp-xor.php) private key and the hashed password. The [hash salt and the number of iterations](https://www.php.net/manual/en/function.hash-pbkdf2.php) are critical when revealing the original seed phrase.

- **Splitting seed phrase:** You can split an existing mnemonic into multiple seed phrases. The order of the generated seed phrases does not matter as long as you have all the mnemonic pieces. PHP's secure [random_bytes()](https://www.php.net/manual/en/function.random-bytes.php) function is used to produce the combined [XOR-ed](https://www.php.net/manual/en/function.gmp-xor.php) values. The same seed phrase will generate a different set of split mnemonics every time.

- **Translating seed phrases:** You can translate obscured seed phrases into different languages. A standard set of [alternative languages besides English](https://github.com/bitcoin/bips/blob/master/bip-0039/bip-0039-wordlists.md) is available. See [BIP39](#bip39) section for more information.

### Reveal

This tool does the opposite of obscure. It reveals translated, split, and password-protected seed phrases. All parameters used when obscuring mnemonic are required when revealing the original seed phrase (which includes hash salt, number of iterations, and password).

## Examples

### Example 1: Generating a deterministic seed phrase

Parameters:

- Passphrase: `I love Bitcoin`
- Salt: `satoshi nakamoto`
- Iteration: `10000`
- Password: `ObscureMe!`
- Byte size: `32` (24 words mnemonic)

Generated seed phrase:

```
garden arrest fossil illness bunker foot olive tray grunt cushion original replace general spy happy render scene easy field oven tonight poverty divide economy
```

### Example 2: Obscuring an existing seed phrase

Parameters:

- Seed phrase: `garden arrest fossil illness bunker foot olive tray grunt cushion original replace general spy happy render scene easy field oven tonight poverty divide economy`
- Password: `ObscureMe!`
- Salt: `genesis block`
- Iteration: `25000`
- Split into: `4`
- Language: `random`

Generated seed phrases:

```
mueble línea huerta parar tenso picar ciprés centro uno babor casco imitar posible anemia hueso paquete brillo tijera sartén pelar farol vehículo alga funda

phone country grant cute fine once neither plunge subway envelope firm electric refuse satoshi armor virtual enable absent rookie fun practice name eyebrow between

こふん せつりつ ねんかん はんしゃ ざつおん はいけん ほこる おうべい にっき ぴったり じどう しみん はんぼうき しゃれい せんとう やめる えがく うやまう すわる ごがつ うなぎ ねむい たぶん くいず

眉 屋 仗 凱 冊 它 妹 番 闢 預 讀 針 況 泛 和 告 剝 取 三 選 障 口 趨 恢
```

## BIP39

[BIP39](https://github.com/bitcoin/bips/blob/master/bip-0039.mediawiki) describes the implementation of a mnemonic code or mnemonic sentence, a group of easy-to-remember words, for the generation of deterministic wallets.

You can add custom wordlists as a plain text file in the `app/bip39` directory and then register as a new language in the `app/config.php` file. Please ensure to **back up your custom wordlists file** if you do so. Custom wordlists must follow the rules and recommendations described [on this page](https://github.com/bitcoin/bips/blob/master/bip-0039.mediawiki).

[Wordlists files](https://github.com/bitcoin/bips/blob/master/bip-0039/bip-0039-wordlists.md) available:

- [Czech](https://github.com/bitcoin/bips/blob/master/bip-0039/czech.txt) - `bip39/cs.txt`
- [English](https://github.com/bitcoin/bips/blob/master/bip-0039/english.txt) - `bip39/en.txt` **(default)**
- [Spanish](https://github.com/bitcoin/bips/blob/master/bip-0039/spanish.txt) - `bip39/es.txt`
- [French](https://github.com/bitcoin/bips/blob/master/bip-0039/french.txt) - `bip39/fr.txt`
- [Italian](https://github.com/bitcoin/bips/blob/master/bip-0039/italian.txt) - `bip39/it.txt`
- [Japanese](https://github.com/bitcoin/bips/blob/master/bip-0039/japanese.txt) - `bip39/ja.txt`
- [Korean](https://github.com/bitcoin/bips/blob/master/bip-0039/korean.txt) - `bip39/ko.txt`
- [Portuguese](https://github.com/bitcoin/bips/blob/master/bip-0039/portuguese.txt) - `bip39/pt.txt`
- [Chinese (Simplified)](https://github.com/bitcoin/bips/blob/master/bip-0039/chinese_simplified.txt) - `bip39/zh_cn.txt`
- [Chinese (Traditional)](https://github.com/bitcoin/bips/blob/master/bip-0039/chinese_traditional.txt) - `bip39/zh_tw.txt`

## Libraries

- [bitwasp/bitcoin](https://github.com/Bit-Wasp/bitcoin-php): Bitcoin library for PHP.
- [html2canvas](https://html2canvas.hertzen.com/): Capture a container in a document as an image for the web version.
- [milon/barcode](https://github.com/milon/barcode): Generate QR code for the web version.

###### Check out my [Starname](https://app.starname.me/profile/*rarioj) profile and buy me a ☕ or 🍺.
