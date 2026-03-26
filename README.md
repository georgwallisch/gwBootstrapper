# gwBootStrapper

Lightweight PHP Asset Loader for Bootstrap, jQuery and more.

## Features

- Bootstrap 5 ready
- JSON-based version configs
- Central asset management
- Easy updates via Git
- Inline JS variables

## Installation

```bash
$ git clone https://github.com/georgwallisch/gwBootStrapper.git
```

## Usage

```php
require_once 'src/AssetLoader.php';

$loader = new AssetLoader('config/default.json');

$loader->add('bootstrap')->add('jquery');

echo $loader->renderHead('My App');

// your HTML

echo $loader->renderFoot();

```

## Updating Bootstrap

Add new version file:

`config/versions/bootstrap-5.4.json`

Change default.json:

```json
"default_versions": {
  "bootstrap": "5.4"
}

```

Deploy:

```bash
git pull
```

## Adding new libraries

Extend version JSON:

```json
"tablesorter": {
  "js": "/js/tablesorter/jquery.tablesorter.min.js"
}
```