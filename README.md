# Platform.sh Analytics Tools

## Compatibility

The tools have been tested on macOS, but should run elsewhere too.

## Initial setup

### Install the Platform.sh CLI

In order to run these scripts, you must install the `platform` cli tool from
https://docs.platform.sh/gettingstarted/cli.html

Run `platform` and enter your credentials.

### Install GoAccess

You should also install `goaccess` from
https://goaccess.io/download

## The tools

The tools automatically download and process logs from Platform.sh.

### `platformPhpAnalyzer.php` 

Generates an HTML file with PHP analytics.

Execution: `php platformPhpAnalyzer.php`

### `platformGoAccess.php`

Generates an HTML file with HTTP analytics.

Execution: `php platformGoAccess.php`
