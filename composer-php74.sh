#!/bin/bash
# Run Composer with PHP 7.4 (for Laravel 6 compatibility)
cd "$(dirname "$0")"
"/c/Users/Bacancy/.config/herd/bin/php74/php.exe" "/c/ProgramData/ComposerSetup/bin/composer.phar" "$@"
