# Wooclap Moodle plugin

Integrate [Wooclap](www.wooclap.com) events as a Moodle course activity.

## Manual installation

For end-users, it is better to install this plugin from the official Moodle store or from a .zip provided by Wooclap.

For development purposes, it can be useful to install it manually from source with the instructions below:

1. Install `node`
2. Run `npm install`
3. Run `npm run release`
4. This will generate a .zip file
5. Go to your Moodle instance "Plugin Installer" page: `https://your-moodle-instance/admin/tool/installaddon/index.php`
6. Drag and drop the .zip file onto the page
7. Follow the on-screen instructions

## Code Linter

Linting is done using PHP Code Sniffer (phpcs) with a specific moodle standard.

Some links:

- https://github.com/squizlabs/PHP_CodeSniffer
- https://docs.moodle.org/dev/Linting#PHP_.28PHP_CodeSniffer.29 (Linting article on Moodle doc)
- https://docs.moodle.org/dev/Setting_up_Sublime2 (specific article on setting up Sublime Text for Moodle development)

Steps to get linter working on Sublime Text on Windows

- Install PHP on your machine (https://www.php.net/downloads.php)
- Install Composer (PHP dependency manager) here : https://getcomposer.org/download/ (can be installed locally or globally)
- In this folder, run `composer install` (this uses the `composer.json` file to install PHP Code Sniffer at the correct version)
- Extract the content of `vendor_patch.zip` in the newly created `vendor\squizlabs\php_codesniffer\CodeSniffer\Standards`
- Using Sublime Package Control, install SublimeLinter and SublimeLinter-phpcs

## Code formatter

Code formatting is done using phpfmt in Sublime Text.

- Install PHP on your machine (https://www.php.net/downloads.php)
- Install this package https://packagecontrol.io/packages/phpfmt
- Use the following configuration

```
  {
    "indent_with_space": 4,
    "passes":
    [
      "ReindentSwitchBlocks"
    ],
    "version": 1
  }
```

### VSCode

VSCode extensions
-  [phpcs](https://marketplace.visualstudio.com/items?itemName=ikappas.phpcs)
-  [phpfmt](https://marketplace.visualstudio.com/items?itemName=kokororin.vscode-phpfmt)
