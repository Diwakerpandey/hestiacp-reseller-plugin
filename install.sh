#!/bin/bash
# HestiaCP Reseller Hosting Plugin — Installer
# Usage: sudo bash install.sh [--uninstall] [--apply-patches]

set -e

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
HESTIA="${HESTIA:-/usr/local/hestia}"
BIN_DIR="$HESTIA/bin"
WEB_ROOT="$HESTIA/web"
PLUGIN_INSTALL="$HESTIA/plugins/reseller-hosting"
VERSION="$(tr -d '\r\n' < "$PLUGIN_DIR/VERSION" 2>/dev/null || echo "unknown")"

UNINSTALL=false
APPLY_PATCHES=false
for arg in "$@"; do
	case "$arg" in
		--uninstall) UNINSTALL=true ;;
		--apply-patches) APPLY_PATCHES=true ;;
	esac
done

install_bin() {
	local name="$1"
	local src="$PLUGIN_DIR/bin/$name"
	local dst="$BIN_DIR/$name"
	[ -f "$src" ] || { echo "Missing $src"; exit 1; }
	cp "$src" "$dst"
	sed -i 's/\r$//' "$dst"
	chmod 755 "$dst"
	chown root:root "$dst"
	echo "  Installed $name"
}

install_web_dir() {
	local rel="$1"
	mkdir -p "$WEB_ROOT/$rel"
	cp "$PLUGIN_DIR/web/$rel/index.php" "$WEB_ROOT/$rel/index.php"
	sed -i 's/\r$//' "$WEB_ROOT/$rel/index.php"
	chmod 644 "$WEB_ROOT/$rel/index.php"
	chown root:root "$WEB_ROOT/$rel/index.php"
	echo "  Installed /$rel/"
}

if [ "$(id -u)" -ne 0 ]; then
	echo "Error: run as root (sudo bash install.sh)"
	exit 1
fi

if [ ! -d "$HESTIA" ]; then
	echo "Error: HestiaCP not found at $HESTIA"
	exit 1
fi

# Ensure Hestia plugins directory exists (not created by default on all installs)
HESTIA_PLUGINS_DIR="$HESTIA/plugins"
if [ ! -d "$HESTIA_PLUGINS_DIR" ]; then
	mkdir -p "$HESTIA_PLUGINS_DIR"
	chmod 755 "$HESTIA_PLUGINS_DIR"
	echo "Created $HESTIA_PLUGINS_DIR"
fi

if $UNINSTALL; then
	echo "Uninstalling Reseller Hosting plugin..."
	for cmd in v-add-reseller-package v-delete-reseller-package v-list-reseller-packages \
		v-add-reseller v-delete-reseller v-list-resellers \
		v-add-reseller-user v-delete-reseller-user v-list-reseller-users v-get-reseller-stats \
		v-add-reseller-hosting-package v-delete-reseller-hosting-package v-list-reseller-hosting-packages \
		v-change-reseller-user-package \
		v-verify-reseller-child v-repair-reseller-child; do
		rm -f "$BIN_DIR/$cmd"
	done
	rm -f "$HESTIA/func/reseller.sh" "$HESTIA/func/reseller_security.sh"
	rm -f "$WEB_ROOT/inc/reseller_lockdown.php" "$WEB_ROOT/inc/reseller_helpers.php" "$WEB_ROOT/inc/panel_reseller_nav.php" "$WEB_ROOT/inc/panel_reseller_nav_default.php" "$WEB_ROOT/inc/reseller_page_styles.php"
	rm -rf "$WEB_ROOT/list/reseller" "$WEB_ROOT/list/reseller-package" "$WEB_ROOT/list/reseller-user"
	rm -rf "$WEB_ROOT/list/reseller-dashboard" "$WEB_ROOT/list/reseller-account" "$WEB_ROOT/list/reseller-hosting-package"
	rm -rf "$WEB_ROOT/list/reseller-loginas" "$WEB_ROOT/list/reseller-edit-user"
	rm -rf "$WEB_ROOT/add/reseller" "$WEB_ROOT/add/reseller-user" "$WEB_ROOT/add/reseller-package"
	rm -rf "$WEB_ROOT/add/reseller-hosting-package"
	for tpl in list_resellers list_reseller_packages list_reseller_users list_reseller_dashboard \
		list_reseller_hosting_packages edit_reseller_hosting_user edit_reseller_account add_reseller add_reseller_user add_reseller_package add_reseller_hosting_package; do
		rm -f "$WEB_ROOT/templates-custom/pages/${tpl}.php"
	done
	rm -rf "$PLUGIN_INSTALL"
	echo "Done. Note: reseller accounts and data in $HESTIA/data/resellers are not removed."
	echo "Optional: remove packages reseller-system.pkg and data/reseller-packages manually."
	exit 0
fi

echo ""
echo "=========================================="
echo "  Reseller Hosting Plugin v$VERSION"
echo "=========================================="
echo ""

echo "[1/6] Installing func/reseller.sh and reseller_security.sh..."
cp "$PLUGIN_DIR/func/reseller.sh" "$HESTIA/func/reseller.sh"
cp "$PLUGIN_DIR/func/reseller_security.sh" "$HESTIA/func/reseller_security.sh"
sed -i 's/\r$//' "$HESTIA/func/reseller.sh" "$HESTIA/func/reseller_security.sh"
chmod 644 "$HESTIA/func/reseller.sh" "$HESTIA/func/reseller_security.sh"
chown root:root "$HESTIA/func/reseller.sh" "$HESTIA/func/reseller_security.sh"

mkdir -p "$WEB_ROOT/inc"
cp "$PLUGIN_DIR/web/inc/reseller_lockdown.php" "$WEB_ROOT/inc/reseller_lockdown.php"
cp "$PLUGIN_DIR/web/inc/reseller_helpers.php" "$WEB_ROOT/inc/reseller_helpers.php"
cp "$PLUGIN_DIR/web/inc/panel_reseller_nav.php" "$WEB_ROOT/inc/panel_reseller_nav.php"
cp "$PLUGIN_DIR/web/inc/panel_reseller_nav_default.php" "$WEB_ROOT/inc/panel_reseller_nav_default.php"
cp "$PLUGIN_DIR/web/inc/reseller_page_styles.php" "$WEB_ROOT/inc/reseller_page_styles.php"
chmod 644 "$WEB_ROOT/inc/reseller_lockdown.php" "$WEB_ROOT/inc/reseller_helpers.php" "$WEB_ROOT/inc/panel_reseller_nav.php" "$WEB_ROOT/inc/panel_reseller_nav_default.php" "$WEB_ROOT/inc/reseller_page_styles.php"
chown root:root "$WEB_ROOT/inc/reseller_lockdown.php" "$WEB_ROOT/inc/reseller_helpers.php" "$WEB_ROOT/inc/panel_reseller_nav.php" "$WEB_ROOT/inc/panel_reseller_nav_default.php" "$WEB_ROOT/inc/reseller_page_styles.php"

echo "[2/6] Installing CLI commands..."
for cmd in v-add-reseller-package v-delete-reseller-package v-list-reseller-packages \
	v-add-reseller v-delete-reseller v-list-resellers \
	v-add-reseller-user v-delete-reseller-user v-list-reseller-users v-get-reseller-stats \
	v-add-reseller-hosting-package v-delete-reseller-hosting-package v-list-reseller-hosting-packages \
	v-change-reseller-user-package \
	v-verify-reseller-child v-repair-reseller-child; do
	install_bin "$cmd"
done

echo "[3/6] Installing system package for reseller panel accounts..."
mkdir -p "$HESTIA/data/packages"
cp "$PLUGIN_DIR/data/packages/reseller-system.pkg" "$HESTIA/data/packages/reseller-system.pkg"
chmod 644 "$HESTIA/data/packages/reseller-system.pkg"

echo "[4/6] Installing default reseller plans..."
mkdir -p "$HESTIA/data/reseller-packages"
if [ -d "$PLUGIN_DIR/data/reseller-packages" ]; then
	for f in "$PLUGIN_DIR/data/reseller-packages"/*.pkg; do
		[ -f "$f" ] || continue
		base=$(basename "$f")
		if [ ! -f "$HESTIA/data/reseller-packages/$base" ]; then
			cp "$f" "$HESTIA/data/reseller-packages/$base"
			chmod 644 "$HESTIA/data/reseller-packages/$base"
			echo "  Installed reseller plan: $base"
		fi
	done
fi
mkdir -p "$HESTIA/data/resellers"

echo "[5/6] Installing web UI..."
mkdir -p "$WEB_ROOT/templates-custom/pages"
install_web_dir "list/reseller"
install_web_dir "list/reseller-package"
install_web_dir "list/reseller-user"
install_web_dir "list/reseller-dashboard"
install_web_dir "list/reseller-account"
install_web_dir "list/reseller-hosting-package"
install_web_dir "list/reseller-loginas"
install_web_dir "list/reseller-edit-user"
install_web_dir "add/reseller"
install_web_dir "add/reseller-user"
install_web_dir "add/reseller-package"
install_web_dir "add/reseller-hosting-package"
for tpl in list_resellers.php list_reseller_packages.php list_reseller_users.php list_reseller_dashboard.php \
	list_reseller_hosting_packages.php edit_reseller_hosting_user.php edit_reseller_account.php add_reseller.php add_reseller_user.php add_reseller_package.php add_reseller_hosting_package.php; do
	cp "$PLUGIN_DIR/web/templates-custom/pages/$tpl" "$WEB_ROOT/templates-custom/pages/$tpl"
	sed -i 's/\r$//' "$WEB_ROOT/templates-custom/pages/$tpl"
	chmod 644 "$WEB_ROOT/templates-custom/pages/$tpl"
	chown root:root "$WEB_ROOT/templates-custom/pages/$tpl"
done
echo "  Templates installed"

echo "==> Syncing theme integration (panel + dashboard)..."
THEME_PAYLOAD="$PLUGIN_DIR/../hestia-theme-plugin/payload/templates-custom"
if [ -f "$THEME_PAYLOAD/includes/panel.php" ]; then
	mkdir -p "$WEB_ROOT/templates-custom/includes"
	cp "$THEME_PAYLOAD/includes/panel.php" "$WEB_ROOT/templates-custom/includes/panel.php"
	chmod 644 "$WEB_ROOT/templates-custom/includes/panel.php"
	echo "  Updated templates-custom/includes/panel.php (Resellers nav + reseller menu)"
fi
if [ -f "$THEME_PAYLOAD/pages/list_dashboard.php" ]; then
	cp "$THEME_PAYLOAD/pages/list_dashboard.php" "$WEB_ROOT/templates-custom/pages/list_dashboard.php"
	chmod 644 "$WEB_ROOT/templates-custom/pages/list_dashboard.php"
	echo "  Updated templates-custom/pages/list_dashboard.php (Resellers card)"
fi

echo "[6/7] Storing plugin copy at $PLUGIN_INSTALL..."
mkdir -p "$PLUGIN_INSTALL"
rsync -a --delete \
	--exclude '.git' \
	"$PLUGIN_DIR/" "$PLUGIN_INSTALL/" 2>/dev/null || cp -a "$PLUGIN_DIR/." "$PLUGIN_INSTALL/"

echo "[7/7] Applying UI + security patches (default theme nav, templates)..."
if [ -f "$PLUGIN_DIR/patches/apply-security-patches.sh" ]; then
	bash "$PLUGIN_DIR/patches/apply-security-patches.sh"
fi

echo ""
echo "=========================================="
echo "  Installation complete"
echo "=========================================="
echo ""
echo "  Admin URLs:"
echo "    /list/reseller/           Resellers"
echo "    /list/reseller-package/   Reseller plans"
echo ""
	echo "  Reseller URLs (after login):"
	echo "    /list/reseller-dashboard/   Reseller home"
	echo "    /list/reseller-hosting-package/  Customer plans"
	echo "    /list/reseller-user/        Hosting customers"
echo ""
echo "  CLI examples:"
echo "    v-add-reseller-package /tmp/plan.pkg bronze"
echo "    v-add-reseller reseller1 /tmp/pass.txt mail@x.com starter"
echo "    v-add-reseller-user reseller1 client1 /tmp/p.txt mail@c.com default"
echo ""
	echo "  Patches applied automatically (nav, templates, role guards)."
	echo "  Re-run after Hestia update: sudo bash $PLUGIN_INSTALL/patches/apply-security-patches.sh"
echo ""
