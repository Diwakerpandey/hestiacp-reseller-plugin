# HestiaCP Reseller Hosting Plugin - Public Install Guide

This guide is for public users installing the plugin on a HestiaCP server.

Plugin version: see `VERSION`
Repository: [github.com/Diwakerpandey/hestiacp-reseller-plugin](https://github.com/Diwakerpandey/hestiacp-reseller-plugin)

Installation is very easy with this guide. If you want our team to install it for you, we offer installation support for a **$10 fee**.

## Requirements

- HestiaCP installed at `/usr/local/hestia`
- Root or `sudo` access
- `python3` installed

## Fresh install

### 1) Create plugins directory

```bash
sudo mkdir -p /usr/local/hestia/plugins
sudo chmod 755 /usr/local/hestia/plugins
```

### 2) Clone the plugin

```bash
cd /usr/local/hestia/plugins
sudo git clone https://github.com/Diwakerpandey/hestiacp-reseller-plugin.git
cd hestiacp-reseller-plugin
```

### 3) Run installer

```bash
sudo bash install.sh
```

The installer applies all required patches automatically.

### 4) Open panel URLs

- Admin:
  - `https://YOUR_SERVER:8083/list/reseller/`
  - `https://YOUR_SERVER:8083/list/reseller-package/`
- Reseller:
  - `https://YOUR_SERVER:8083/list/reseller-dashboard/`
  - `https://YOUR_SERVER:8083/list/reseller-user/`
  - `https://YOUR_SERVER:8083/list/reseller-account/`

## Update after changes

```bash
cd /usr/local/hestia/plugins/hestiacp-reseller-plugin
sudo bash install.sh
```

## Re-apply patches after Hestia update

```bash
sudo bash /usr/local/hestia/plugins/reseller-hosting/patches/apply-security-patches.sh
```

## Quick verification

```bash
sudo v-list-resellers json
sudo v-list-reseller-packages json
grep HESTIA_RESELLER_LOCKDOWN /usr/local/hestia/web/inc/main.php
grep HESTIA_RESELLER_LOGIN_REDIRECT /usr/local/hestia/web/login/index.php
```

## Troubleshooting

### Internal Server Error on login page

If Hestia shows **Internal Server Error** at `/login/` after install, check PHP syntax first:

```bash
php -l /usr/local/hestia/web/login/index.php
php -l /usr/local/hestia/web/inc/main.php
tail -n 50 /var/log/hestia/nginx-error.log
tail -n 50 /var/log/hestia/nginx-error.log.1
```

Common log message:

```text
PHP Parse error: syntax error, unexpected token "\" in /usr/local/hestia/web/login/index.php on line 153
```

This means invalid escaped quotes were injected into `login/index.php` (for example `$_SESSION[\"userContext\"]`).

**Fix (recommended):** pull latest plugin code and re-run installer (newer versions auto-repair this):

```bash
cd /usr/local/hestia/plugins/hestiacp-reseller-plugin
git pull origin main
sudo bash install.sh
php -l /usr/local/hestia/web/login/index.php
sudo systemctl restart hestia
```

**Fix (manual, if needed):**

```bash
cp /usr/local/hestia/web/login/index.php /usr/local/hestia/web/login/index.php.bak-fix
sed -i 's/\\"/"/g' /usr/local/hestia/web/login/index.php
php -l /usr/local/hestia/web/login/index.php
sudo systemctl restart hestia
```

**If still broken, restore backup and reinstall:**

```bash
ls -lt /usr/local/hestia/web/login/index.php.bak-reseller-* | head -1
# copy the newest backup over login/index.php, then:
cd /usr/local/hestia/plugins/hestiacp-reseller-plugin
git pull origin main
sudo bash install.sh
```

### Uninstall says "Permission denied" on install.sh

Run uninstall through bash explicitly:

```bash
cd /usr/local/hestia/plugins/hestiacp-reseller-plugin
sudo bash install.sh --uninstall
```

Or make scripts executable first:

```bash
chmod +x install.sh uninstall.sh
sudo bash uninstall.sh
```

## Uninstall

```bash
cd /usr/local/hestia/plugins/hestiacp-reseller-plugin
sudo bash install.sh --uninstall
```

## Notes

- Internal architecture/data-layout documentation is intentionally separated from this public install guide.
- After install, plugin mirror path is `/usr/local/hestia/plugins/reseller-hosting/`.
