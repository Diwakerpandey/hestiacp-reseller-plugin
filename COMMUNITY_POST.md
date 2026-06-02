# HestiaCP Reseller Hosting Plugin — Community Overview

**Version:** 1.3.9  
**License:** Same as your Hestia deployment / plugin bundle  
**Target:** HestiaCP servers where you want DirectAdmin/cPanel-style **reseller hosting** without replacing the panel.

---

## What problem does this solve?

HestiaCP ships with two panel roles: **admin** and **user**. There is no built-in **reseller** layer — someone who sells hosting to their own customers, with limits on how many accounts they can create and how much disk/bandwidth they can use in total.

This plugin adds:

- **Reseller accounts** — log into the panel, manage customers, but do not consume hosting resources on the reseller login itself.
- **Reseller plans** — what you (the server admin) sell to resellers: max users, pooled disk/bandwidth, optional allowed system packages.
- **Hosting users** — normal Hestia end-user accounts owned by a reseller, with ownership tracked in metadata.
- **Optional customer plans** — resellers can define their own hosting packages (limits are synced to real Hestia user packages under the hood).

It is designed for hosts, agencies, and anyone who outgrows “one admin, many users” but wants to stay on Hestia.

---

## How it works (mental model)

Everything is still **real Hestia users** on disk. The plugin adds structure and UI on top.

```
                    ┌─────────────────┐
                    │  Server Admin   │
                    └────────┬────────┘
                             │
         ┌───────────────────┼───────────────────┐
         ▼                   ▼                   ▼
  Reseller plans      Reseller accounts    System user packages
  (*.pkg limits)      (login + metadata)   (default, business, …)
         │                   │
         └─────────┬─────────┘
                   ▼
         ┌─────────────────┐
         │     Reseller      │  ← panel login, ROLE=reseller
         └────────┬────────┘
                  │
    ┌─────────────┼─────────────┐
    ▼             ▼             ▼
 Hosting      Custom plans   "Manage" =
 users        (optional)      impersonate
 (customers)                  customer panel
```

### Three layers of “packages”

| Layer | Who defines it | Example | Purpose |
|-------|----------------|---------|---------|
| **System user package** | Admin in Hestia **Packages** | `default`, `business` | What a hosting account actually gets (domains, disk, mail, …) |
| **Reseller plan** | Admin in **Reseller Packages** | `starter`, `bronze` | Limits for the reseller: max customers, pooled disk/BW, `ALLOWED_PACKAGES` |
| **Reseller hosting package** | Reseller in **Packages** (optional) | `starter`, `pro` | Friendly name; creates `rs_<reseller>_<name>.pkg` for customers |

### Where data lives

| What | Location |
|------|----------|
| Reseller login (Hestia user) | `/usr/local/hestia/data/users/<reseller>/` |
| Reseller limits & child list | `/usr/local/hestia/data/resellers/<reseller>/` (`reseller.conf`, `users.list`) |
| Customer account | `/usr/local/hestia/data/users/<customer>/` with `RESELLER_OWNER='<reseller>'` |
| Plans sold to resellers | `/usr/local/hestia/data/reseller-packages/*.pkg` |
| Reseller-created plan meta | `/usr/local/hestia/data/resellers/<reseller>/hosting-packages/*.meta` |

Customers still have domains, mail, and databases under the normal Hestia paths. Admins still see them in the global user list; the plugin adds ownership and a separate reseller UI.

---

## Install (quick)

**Requirements:** Hestia at `/usr/local/hestia`, root/sudo, `python3` for patches.

```bash
sudo mkdir -p /usr/local/hestia/plugins
cd /usr/local/hestia/plugins
sudo git clone https://github.com/Diwakerpandey/hestiacp-reseller-plugin.git
cd hestiacp-reseller-plugin
sudo bash install.sh
```

Install copies CLI tools, web pages, `func/reseller.sh`, sample plans, and applies **security patches** to core Hestia web files (route lockdown, login redirect, safe customer “login as”, etc.). Patches are idempotent and create timestamped `.bak-reseller-*` backups.

**Optional:** [hestia-theme-plugin](../hestia-theme-plugin/) for DirectAdmin-style navigation (synced by `install.sh` if present).

**After Hestia upgrades**, re-run:

```bash
sudo bash /usr/local/hestia/plugins/reseller-hosting/install.sh
```

Full details: [INSTALL.md](INSTALL.md)

---

## Admin workflow

1. **Create system packages** (if needed) — Hestia **Packages** → e.g. `default`, `business`. These are what customers receive unless the reseller defines custom plans.

2. **Create reseller plans** — `/list/reseller-package/` or CLI:

   ```bash
   cat > /tmp/bronze.pkg <<'EOF'
   MAX_USERS='10'
   MAX_DISK='20480'
   MAX_BANDWIDTH='204800'
   ALLOWED_PACKAGES='default,business'
   EOF
   sudo v-add-reseller-package /tmp/bronze.pkg bronze
   ```

3. **Create a reseller** — `/add/reseller/` or:

   ```bash
   echo 'StrongPass123!' | sudo tee /tmp/pw.txt
   sudo v-add-reseller myreseller /tmp/pw.txt reseller@example.com bronze "My Reseller Inc"
   sudo rm /tmp/pw.txt
   ```

4. **Hand off credentials** — reseller logs in at `https://your-server:8083/` and lands on the reseller dashboard.

| Admin URL | Purpose |
|-----------|---------|
| `/list/reseller/` | Manage resellers |
| `/list/reseller-package/` | Reseller plans |
| `/list/reseller-user/?reseller=name` | View one reseller’s customers |

---

## Reseller workflow

After login, resellers see a focused panel (not the full admin UI).

| URL | Purpose |
|-----|---------|
| `/list/reseller-dashboard/` | Overview & shortcuts |
| `/list/reseller-user/` | Hosting users (customers) |
| `/list/reseller-hosting-package/` | Create/manage plans sold to customers |
| `/list/reseller-account/` | Own password, email, name |
| `/add/reseller-user/` | Add a customer |

### Managing a customer

On **Hosting Users**, each row supports:

| Action | What it does |
|--------|----------------|
| **Edit** | Password, email, contact name, **hosting package** |
| **Manage** | Impersonate the customer → add domains, mail, DB, etc. |
| **Delete** | Remove the hosting account |

Use **Log out** in the top bar to return from “Manage” mode to the reseller panel.

Resellers **cannot** access server admin pages, other resellers’ customers, or promote users to admin (hardened in patches + `reseller_security.sh`).

---

## Security (summary)

- **Route lockdown** — resellers only reach allowed URLs (+ customer panel while impersonating).
- **Ownership checks** — Edit/Manage/Delete only for users listed under that reseller (`v-list-reseller-users` / `RESELLER_OWNER`).
- **Manage** — forced through plugin routes; core `?loginas=` blocked for resellers.
- **Role changes** — cannot set `ROLE=admin` via config unless `HESTIA_ALLOW_ADMIN_ROLE=yes` (automation only).

Verify patches after install: see [INSTALL.md — Verify installation](INSTALL.md#verify-installation).

---

## CLI (automation / billing)

All commands live in `/usr/local/hestia/bin/` after install.

| Command | Description |
|---------|-------------|
| `v-list-reseller-packages [json]` | List reseller plans |
| `v-add-reseller-package <file> <name>` | Add reseller plan |
| `v-add-reseller <user> <pass\|file> <email> <plan> [name]` | Create reseller |
| `v-delete-reseller <user> [DELETE_CHILDREN]` | Remove reseller |
| `v-list-reseller-users <reseller> [json]` | List customers |
| `v-add-reseller-user <reseller> <user> <pass> <email> <pkg> [name]` | Create customer |
| `v-delete-reseller-user <reseller> <user>` | Delete customer |
| `v-change-reseller-user-package <reseller> <user> <pkg>` | Change customer package |
| `v-list-reseller-hosting-packages <reseller> [json]` | Reseller’s custom plans |
| `v-add-reseller-hosting-package <reseller> <file> <name>` | Add custom plan |
| `v-get-reseller-stats <reseller> [json]` | Usage vs limits |
| `v-verify-reseller-child` / `v-repair-reseller-child` | Fix ownership metadata |

**WHMCS / Blesta / custom:** call the same CLI from provisioning modules (passwords via `/tmp/` files, Hestia convention). No built-in billing UI.

---

## What you get in the box

```
reseller-hosting-plugin/
├── install.sh / uninstall.sh
├── INSTALL.md          ← server install, patches, troubleshooting
├── README.md           ← developer reference
├── func/reseller.sh    ← shared bash logic
├── bin/v-*             ← CLI
├── web/                ← panel controllers + templates
├── patches/            ← core Hestia UI/security patches
└── data/               ← reseller-system.pkg, sample plans
```

---

## Known limitations (honest list)

- Pooled disk/bandwidth is enforced when **creating** users and via plan limits; not re-checked on every single domain action (v1 behavior).
- Child users still appear in the **admin** global user list (with `RESELLER_OWNER`).
- No built-in invoicing — integrate billing externally.
- Core Hestia files are **patched**; re-apply after major Hestia updates.
- Downgrading a customer’s package can fail if current usage exceeds the new plan (standard Hestia `v-change-user-package` behavior).

---

## Uninstall

```bash
sudo bash uninstall.sh
```

Removes plugin files and patches from the install script list; **does not** delete existing resellers or `/usr/local/hestia/data/resellers/`. Clean those manually if required.

---

## Feedback & contribution

If you try this on a lab server:

1. Note Hestia version and plugin `VERSION`.
2. Report issues with steps, URL, and whether `install.sh` / patch verification passed.
3. Suggestions welcome: billing hooks, suspension workflows, DNS templates, etc.

**Docs in this repo:** [README.md](README.md) · [INSTALL.md](INSTALL.md)

---

## Copy-paste title ideas for forum posts

- *Reseller hosting for HestiaCP — plugin overview (v1.3.9)*
- *How we added cPanel/DirectAdmin-style resellers to Hestia*
- *[Guide] HestiaCP reseller accounts, plans, and customer management*

---

*You can post this file as-is or trim the tables for Discord/forum character limits. For GitHub, use the README + INSTALL links as the canonical technical reference.*
