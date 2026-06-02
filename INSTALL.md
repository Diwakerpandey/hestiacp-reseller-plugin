# Reseller Hosting Plugin — Install & Patches Guide

Step-by-step reference for installing, updating, and patching **HestiaCP Reseller Hosting** on a server.

**Plugin version:** see `VERSION` (current: 1.3.9)  
**Installed copy on server:** `/usr/local/hestia/plugins/reseller-hosting/`  
**Hestia root:** `/usr/local/hestia`

---

## Table of contents

1. [Requirements](#requirements)
2. [Where data is stored](#where-data-is-stored)
3. [Fresh install](#fresh-install)
4. [Update after code changes](#update-after-code-changes)
5. [What gets installed](#what-gets-installed)
6. [Patches overview](#patches-overview)
7. [Apply patches only](#apply-patches-only)
8. [Verify installation](#verify-installation)
9. [After Hestia upgrade](#after-hestia-upgrade)
10. [Troubleshooting](#troubleshooting)
11. [Uninstall](#uninstall)

---

## Requirements

- HestiaCP installed at `/usr/local/hestia`
- Root or `sudo` on the server
- `python3` (for customer-access patches)
- Optional: **hestia-theme-plugin** for DirectAdmin-style nav (synced by `install.sh` if present)

---

## Where data is stored

All paths below use Hestia root **`/usr/local/hestia`** unless noted.

### Overview

| What | Type | Main location |
|------|------|----------------|
| **Reseller login account** | Normal Hestia system user | `/usr/local/hestia/data/users/<reseller>/` |
| **Reseller limits & usage** | Plugin metadata | `/usr/local/hestia/data/resellers/<reseller>/` |
| **Hosting customer (end user)** | Normal Hestia user | `/usr/local/hestia/data/users/<customer>/` |
| **Plans sold to resellers** | Reseller packages | `/usr/local/hestia/data/reseller-packages/*.pkg` |
| **Plans resellers sell to customers** | Per-reseller + global Hestia packages | See below |

Resellers and hosting customers are **real Hestia users** (domains, mail, databases under `data/users/`). The plugin adds extra files to track ownership and limits.

---

### Reseller account (panel login)

| Item | Path / field |
|------|----------------|
| User home (Hestia standard) | `/usr/local/hestia/data/users/<reseller>/` |
| Password, email, theme, etc. | `/usr/local/hestia/data/users/<reseller>/user.conf` |
| Role | `ROLE='reseller'` in `user.conf` (set by `v-add-reseller`) |
| Panel package | `PACKAGE='reseller-system'` in `user.conf` |
| System package definition | `/usr/local/hestia/data/packages/reseller-system.pkg` |
| Plugin metadata directory | `/usr/local/hestia/data/resellers/<reseller>/` |

**`reseller.conf`** — limits from the reseller plan (created at `v-add-reseller`):

```text
/usr/local/hestia/data/resellers/<reseller>/reseller.conf
```

Example fields: `RESELLER_PACKAGE`, `MAX_USERS`, `MAX_DISK`, `MAX_BANDWIDTH`, `ALLOWED_PACKAGES`, `R_USERS`, `R_DISK`, `R_BANDWIDTH`, `DATE`.

**`users.list`** — one hosting username per line (children owned by this reseller):

```text
/usr/local/hestia/data/resellers/<reseller>/users.list
```

**Custom hosting packages** (optional, reseller-created plans):

```text
/usr/local/hestia/data/resellers/<reseller>/hosting-packages/<name>.meta
```

Each `.meta` file points to a global Hestia package named `rs_<reseller>_<name>` under `/usr/local/hestia/data/packages/`.

---

### Hosting customer (reseller’s end user)

| Item | Path / field |
|------|----------------|
| User home (Hestia standard) | `/usr/local/hestia/data/users/<customer>/` |
| Account settings | `/usr/local/hestia/data/users/<customer>/user.conf` |
| Role | `ROLE='user'` (normal hosting account) |
| Assigned package | `PACKAGE='...'` in `user.conf` (e.g. `default` or `rs_reseller_plan`) |
| **Ownership link** | `RESELLER_OWNER='<reseller>'` in `user.conf` |
| Listed under reseller | Line in `/usr/local/hestia/data/resellers/<reseller>/users.list` |
| Web/mail/dns data | `/usr/local/hestia/data/users/<customer>/web/`, `mail/`, `dns/`, etc. |

`RESELLER_OWNER` and `users.list` are both used; **Edit / Manage** checks the same list as the Hosting Users table (`v-list-reseller-users`).

Repair ownership if needed:

```bash
sudo v-repair-reseller-child <reseller> <customer>
# or
sudo v-verify-reseller-child <reseller> <customer>
```

---

### Reseller plans (what you sell to resellers)

Plans define how many customers and how much disk/bandwidth a **reseller** may use:

```text
/usr/local/hestia/data/reseller-packages/<plan>.pkg
```

Example: `starter.pkg`, `business.pkg`. Fields: `MAX_USERS`, `MAX_DISK`, `MAX_BANDWIDTH`, `ALLOWED_PACKAGES`.

Sample files from the plugin repo are copied on first install (existing files are not overwritten).

---

### Admin hosting packages (what customers receive)

Standard Hestia user packages (created in **Packages** in the admin UI):

```text
/usr/local/hestia/data/packages/<package>.pkg
```

Reseller-created plans are synced here as `rs_<reseller>_<planname>.pkg`.

---

### Plugin / panel files (not account data)

| Purpose | Path |
|---------|------|
| Plugin copy after install | `/usr/local/hestia/plugins/reseller-hosting/` |
| Web UI controllers | `/usr/local/hestia/web/list/reseller-*`, `add/reseller-*` |
| Page templates | `/usr/local/hestia/web/templates-custom/pages/list_reseller_*.php` |
| PHP helpers | `/usr/local/hestia/web/inc/reseller_*.php` |
| CLI commands | `/usr/local/hestia/bin/v-add-reseller`, `v-list-reseller-users`, … |
| Shared shell functions | `/usr/local/hestia/func/reseller.sh` |

---

### Quick inspection commands

```bash
RESELLER=myreseller
CUSTOMER=client1

# Reseller panel user
sudo grep -E "^ROLE=|^PACKAGE=|^CONTACT=" /usr/local/hestia/data/users/$RESELLER/user.conf

# Reseller limits
sudo cat /usr/local/hestia/data/resellers/$RESELLER/reseller.conf

# Children list
sudo cat /usr/local/hestia/data/resellers/$RESELLER/users.list

# Customer ownership
sudo grep RESELLER_OWNER /usr/local/hestia/data/users/$CUSTOMER/user.conf

# JSON summary (CLI)
sudo v-list-resellers json
sudo v-list-reseller-users $RESELLER json
sudo v-get-reseller-stats $RESELLER json
```

---

## Fresh install

### 1. Create the plugins directory

Hestia does not always ship with a `plugins` folder. Create it first:

```bash
sudo mkdir -p /usr/local/hestia/plugins
sudo chmod 755 /usr/local/hestia/plugins
```

### 2. Get the plugin into `plugins/`

**Option A — Git clone (recommended):**

```bash
cd /usr/local/hestia/plugins
sudo git clone https://github.com/Diwakerpandey/hestiacp-reseller-plugin.git
cd hestiacp-reseller-plugin
```

Repository: [github.com/Diwakerpandey/hestiacp-reseller-plugin](https://github.com/Diwakerpandey/hestiacp-reseller-plugin/)

**Option B — Upload / copy:**

Upload this repository to:

```text
/usr/local/hestia/plugins/hestiacp-reseller-plugin/
```

Then:

```bash
cd /usr/local/hestia/plugins/hestiacp-reseller-plugin
```

### 3. Run the installer

```bash
sudo bash install.sh
```

`install.sh` installs CLI commands, web UI, sample plans, and **automatically** runs all security/UI patches at the end.

Optional (same as step 3; patches already run by default):

```bash
sudo bash install.sh --apply-patches
```

After install, a copy of the plugin is also stored at:

```text
/usr/local/hestia/plugins/reseller-hosting/
```

### 4. Confirm in the browser

| Role | URL |
|------|-----|
| Admin | `https://YOUR_SERVER:8083/list/reseller/` |
| Admin | `https://YOUR_SERVER:8083/list/reseller-package/` |
| Reseller | `https://YOUR_SERVER:8083/list/reseller-dashboard/` |
| Reseller | `https://YOUR_SERVER:8083/list/reseller-user/` |
| Reseller | `https://YOUR_SERVER:8083/list/reseller-account/` — edit own password, email, name |

### 5. Create first reseller (CLI example)

```bash
# Reseller plan (once)
cat > /tmp/starter.pkg <<'EOF'
MAX_USERS='25'
MAX_DISK='51200'
MAX_BANDWIDTH='512000'
ALLOWED_PACKAGES='default'
EOF
sudo v-add-reseller-package /tmp/starter.pkg starter

# Reseller account
echo 'StrongPassword!' | sudo tee /tmp/pw.txt
sudo v-add-reseller myreseller /tmp/pw.txt admin@example.com starter "My Reseller"
sudo rm /tmp/pw.txt
```

---

## Update after code changes

When you pull a new plugin version from git or copy updated files:

```bash
cd /usr/local/hestia/plugins/hestiacp-reseller-plugin
sudo bash install.sh
```

This refreshes:

- `/usr/local/hestia/bin/v-*` commands  
- `/usr/local/hestia/web/list|add/reseller-*` controllers  
- `/usr/local/hestia/web/templates-custom/pages/*`  
- `/usr/local/hestia/web/inc/reseller_*.php`  
- `/usr/local/hestia/func/reseller.sh`  
- Plugin mirror at `/usr/local/hestia/plugins/reseller-hosting/`  
- All patches (idempotent)

Hard-refresh the panel in the browser (**Ctrl+F5**) after web file updates.

---

## What gets installed

| Component | Server path |
|-----------|-------------|
| CLI commands | `/usr/local/hestia/bin/v-add-reseller`, `v-list-reseller-users`, … |
| Shared functions | `/usr/local/hestia/func/reseller.sh`, `reseller_security.sh` |
| Web controllers | `/usr/local/hestia/web/list/reseller-user/`, `add/reseller-user/`, … |
| Page templates | `/usr/local/hestia/web/templates-custom/pages/list_reseller_*.php`, … |
| PHP includes | `/usr/local/hestia/web/inc/reseller_lockdown.php`, `reseller_helpers.php`, `panel_reseller_nav.php` |
| Reseller panel package | `/usr/local/hestia/data/packages/reseller-system.pkg` |
| Sample reseller plans | `/usr/local/hestia/data/reseller-packages/*.pkg` |
| Plugin backup | `/usr/local/hestia/plugins/reseller-hosting/` |

---

## Patches overview

Patches modify **core Hestia web files**. Backups are created as `*.bak-reseller-YYYYMMDDHHMMSS` when a file is changed.

### A. Security patches — `patches/apply-security-patches.sh`

Run automatically by `install.sh`, or manually:

```bash
sudo bash /usr/local/hestia/plugins/reseller-hosting/patches/apply-security-patches.sh
```

| Patch | File(s) | Marker / purpose |
|-------|---------|------------------|
| Route lockdown | `web/inc/main.php` | `HESTIA_RESELLER_LOCKDOWN` — limits reseller URLs |
| Login redirect | `web/login/index.php` | `HESTIA_RESELLER_LOGIN_REDIRECT` — resellers → dashboard |
| Role guard (overlay) | `bin/v-change-user-role` | `HESTIA_RESELLER_ROLE_GUARD` |
| Config guard (overlay) | `bin/v-change-user-config-value` | blocks direct `ROLE=` changes |
| Panel nav | `templates-custom/includes/panel.php` | `HESTIA_RESELLER_PLUGIN_NAV_*` |
| Customer access | see B below | |

Also copies `reseller_lockdown.php`, `reseller_helpers.php`, `panel_reseller_nav.php` into `web/inc/`.

### B. Customer access — `patches/patch-reseller-customer-access.py`

Invoked by `apply-security-patches.sh`:

```bash
sudo python3 /usr/local/hestia/plugins/reseller-hosting/patches/patch-reseller-customer-access.py /usr/local/hestia/web
```

| Patch | File | Marker |
|-------|------|--------|
| Impersonation `$user` | `web/inc/main.php` | `HESTIA_RESELLER_CUSTOMER_ACCESS` |
| Reseller login-as | `web/login/index.php` | `HESTIA_RESELLER_CUSTOMER_ACCESS_LOGINAS` |
| Session redirect | `web/login/index.php` | `HESTIA_RESELLER_SESSION_REDIRECT` |
| Edit child user | `web/edit/user/index.php` | `HESTIA_RESELLER_CUSTOMER_ACCESS` |
| Logout after Manage | `web/logout/index.php` | `HESTIA_RESELLER_CUSTOMER_ACCESS_LOGOUT_LOOK` |

Expected output (example):

```text
Already patched: .../main.php (main-look)
Already patched: .../login/index.php (login-loginas)
Patched: .../logout/index.php (logout-look)
```

### C. UI patches — `patches/apply-ui-patches.sh`

Alias for security patches (same script):

```bash
sudo bash patches/apply-ui-patches.sh
```

### D. Theme integration (install.sh)

If `hestia-theme-plugin` is next to this repo, `install.sh` copies:

- `templates-custom/includes/panel.php` — Resellers nav + reseller menu  
- `templates-custom/pages/list_dashboard.php` — Resellers card  

---

## Apply patches only

Use when web/CLI files are already installed but patches were lost (e.g. after Hestia update):

```bash
cd /usr/local/hestia/plugins/hestiacp-reseller-plugin

# Full security + customer access + nav
sudo bash patches/apply-security-patches.sh

# Customer-access only (if security script already ran)
sudo python3 patches/patch-reseller-customer-access.py /usr/local/hestia/web
```

Re-apply hardened binaries (if `apt upgrade` overwrote them):

```bash
sudo cp patches/overlay-bin/v-change-user-role /usr/local/hestia/bin/
sudo cp patches/overlay-bin/v-change-user-config-value /usr/local/hestia/bin/
sudo chmod 755 /usr/local/hestia/bin/v-change-user-role /usr/local/hestia/bin/v-change-user-config-value
```

---

## Verify installation

### Plugin version

```bash
cat /usr/local/hestia/plugins/reseller-hosting/VERSION
```

### CLI

```bash
sudo v-list-resellers json
sudo v-list-reseller-packages json
```

### Web routes

```bash
ls -la /usr/local/hestia/web/list/reseller-user/index.php
ls -la /usr/local/hestia/web/list/reseller-dashboard/index.php
```

### Patches applied

```bash
grep HESTIA_RESELLER_LOCKDOWN /usr/local/hestia/web/inc/main.php
grep HESTIA_RESELLER_CUSTOMER_ACCESS /usr/local/hestia/web/inc/main.php
grep HESTIA_RESELLER_LOGIN_REDIRECT /usr/local/hestia/web/login/index.php
grep HESTIA_RESELLER_CUSTOMER_ACCESS_LOGOUT_LOOK /usr/local/hestia/web/logout/index.php
grep reseller_lockdown /usr/local/hestia/web/inc/main.php
```

### Reseller child ownership (debug)

```bash
RESELLER=myreseller
CHILD=client1
sudo v-list-reseller-users "$RESELLER" json
sudo v-verify-reseller-child "$RESELLER" "$CHILD"
# Repair link if user shows in list but Edit/Manage fails:
sudo v-repair-reseller-child "$RESELLER" "$CHILD"
```

---

## After Hestia upgrade

Hestia updates may overwrite `web/inc/main.php`, `login/index.php`, `logout/index.php`, and `bin/v-change-user-*`.

**Re-run after every `apt upgrade` / Hestia update:**

```bash
cd /usr/local/hestia/plugins/hestiacp-reseller-plugin
sudo bash install.sh
```

Or at minimum:

```bash
sudo bash patches/apply-security-patches.sh
```

Then run the [verify](#verify-installation) greps again.

---

## Troubleshooting

### “Invalid hosting user” on Edit / Manage

User appears in the list but actions fail:

```bash
sudo v-repair-reseller-child RESELLER_USERNAME CHILD_USERNAME
sudo cp /usr/local/hestia/plugins/reseller-hosting/web/inc/reseller_helpers.php \
  /usr/local/hestia/web/inc/
sudo cp /usr/local/hestia/plugins/reseller-hosting/web/list/reseller-user/index.php \
  /usr/local/hestia/web/list/reseller-user/
```

### 404 on Edit / Manage

Controllers missing — re-run `sudo bash install.sh`.

### Reseller cannot log out

Ensure logout patch is present:

```bash
grep HESTIA_RESELLER_CUSTOMER_ACCESS_LOGOUT_LOOK /usr/local/hestia/web/logout/index.php
```

If missing, re-run:

```bash
sudo python3 /usr/local/hestia/plugins/reseller-hosting/patches/patch-reseller-customer-access.py /usr/local/hestia/web
```

### Patch script says “Already patched” but grep finds nothing

Fixed in plugin **v1.3.5+** (`patch-reseller-customer-access.py` uses per-patch markers). Update the plugin and re-run the Python patch script.

### Restore a patched core file

```bash
ls -la /usr/local/hestia/web/inc/main.php.bak-reseller-*
sudo cp /usr/local/hestia/web/inc/main.php.bak-reseller-XXXXXXXX /usr/local/hestia/web/inc/main.php
```

---

## Uninstall

```bash
cd /usr/local/hestia/plugins/hestiacp-reseller-plugin
sudo bash install.sh --uninstall
# or
sudo bash uninstall.sh
```

Does **not** delete:

- Reseller accounts in `/usr/local/hestia/data/users/`  
- Data under `/usr/local/hestia/data/resellers/`  

Remove those manually if required.

To remove patch hooks from `main.php`, restore from `main.php.bak-reseller-*` or edit out lines containing `HESTIA_RESELLER_LOCKDOWN`.

---

## Quick command cheat sheet

```bash
# Full install + patches
sudo bash install.sh

# Patches only
sudo bash patches/apply-security-patches.sh

# Customer-access patches only
sudo python3 patches/patch-reseller-customer-access.py /usr/local/hestia/web

# Verify
grep HESTIA_RESELLER_LOCKDOWN /usr/local/hestia/web/inc/main.php
grep HESTIA_RESELLER_CUSTOMER_ACCESS_LOGOUT_LOOK /usr/local/hestia/web/logout/index.php
```

---

## Publish to GitHub

Repository: [github.com/Diwakerpandey/hestiacp-reseller-plugin](https://github.com/Diwakerpandey/hestiacp-reseller-plugin)

On the server (or your dev machine), from the plugin source folder:

```bash
cd /usr/local/hestia/plugins/reseller-hosting-plugin

git init
git add .
git status   # confirm bin/, web/, patches/, install.sh, README.md, etc. are listed (not only screenshots)

git commit -m "Release HestiaCP reseller hosting plugin v$(cat VERSION)"

git branch -M main
git remote add origin https://github.com/Diwakerpandey/hestiacp-reseller-plugin.git
git push -u origin main
```

If the remote repo already has commits (e.g. only screenshots) and you want to replace them with the full plugin:

```bash
git push -u origin main --force
```

Use `--force` only when you intend to overwrite the remote history.

**Before pushing:** do not commit passwords, `.env` files, or `*.bak-reseller-*` patch backups (see `.gitignore`).

**After publishing:** tag a release on GitHub (Releases → Create release → tag `v1.3.9`).

---

## See also

- `README.md` — architecture, CLI reference, WHMCS notes  
- `VERSION` — release number  
