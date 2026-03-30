# PHP version (important)

This project uses **Laravel 6**, which is **not compatible with PHP 8.x**.

If you run **`php artisan serve`** and see **ArrayAccess / Collection fatal errors**, your shell is using **PHP 8 from PATH** — not a bug in your code.

## Why `php artisan serve` still fails

The command **`php`** is resolved from your **system PATH** (often PHP 8).  
This repo cannot change that unless you use one of the wrappers below or set Herd to PHP 7.4.

## Use PHP 7.4 (pick one)

### A) Git Bash — use project `php` script

From the project folder:

```bash
chmod +x ./php
./php artisan serve --host=127.0.0.1 --port=8000
```

### B) CMD / PowerShell — `php.bat` or helpers

```bat
php.bat artisan serve --host=127.0.0.1 --port=8000
```

Or:

```bat
serve-php74.bat
```

Or:

```bat
run-php74.bat artisan serve --host=127.0.0.1 --port=8000
```

### C) Laravel Herd (recommended for `learning.test`)

Start **Laravel Herd** (Windows), ensure this project is the active site (`learning.test` → `D:\learning`), and set PHP **7.4** for that host. Then open **`https://learning.test`** (no `artisan serve` needed). You can double‑click **`open-learning-test.bat`** in the project root to launch Herd and the browser.

Set **`APP_URL=https://learning.test`** in `.env` (see `.env.example`).

## Check which PHP runs

```bash
php -v          # often PHP 8 — wrong for this project
./php -v        # should show PHP 7.4.x
```
