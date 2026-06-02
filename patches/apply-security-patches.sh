#!/bin/bash
# Apply reseller security hardening (idempotent).
# Usage: sudo bash apply-security-patches.sh

set -e

HESTIA="${HESTIA:-/usr/local/hestia}"
WEB="$HESTIA/web"
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BIN_DIR="$HESTIA/bin"
INC_DIR="$WEB/inc"

MARKER_LOCKDOWN="HESTIA_RESELLER_LOCKDOWN"
MARKER_NAV_BEGIN="HESTIA_RESELLER_PLUGIN_NAV_BEGIN"
MARKER_NAV_END="HESTIA_RESELLER_PLUGIN_NAV_END"
MARKER_LOGIN="HESTIA_RESELLER_LOGIN_REDIRECT"
MARKER_ROLE_EXEC="HESTIA_ALLOW_ADMIN_ROLE"

echo "==> Installing security includes..."
cp "$PLUGIN_DIR/web/inc/reseller_lockdown.php" "$INC_DIR/reseller_lockdown.php"
cp "$PLUGIN_DIR/web/inc/reseller_helpers.php" "$INC_DIR/reseller_helpers.php"
cp "$PLUGIN_DIR/web/inc/panel_reseller_nav.php" "$INC_DIR/panel_reseller_nav.php"
cp "$PLUGIN_DIR/web/inc/panel_reseller_nav_default.php" "$INC_DIR/panel_reseller_nav_default.php"
cp "$PLUGIN_DIR/web/inc/reseller_page_styles.php" "$INC_DIR/reseller_page_styles.php"
chmod 644 "$INC_DIR/reseller_lockdown.php" "$INC_DIR/reseller_helpers.php" "$INC_DIR/panel_reseller_nav.php" "$INC_DIR/panel_reseller_nav_default.php" "$INC_DIR/reseller_page_styles.php"
chown root:root "$INC_DIR/reseller_lockdown.php" "$INC_DIR/reseller_helpers.php" "$INC_DIR/panel_reseller_nav.php" "$INC_DIR/panel_reseller_nav_default.php" "$INC_DIR/reseller_page_styles.php"

if [ -f "$PLUGIN_DIR/bin/v-verify-reseller-child" ]; then
	cp "$PLUGIN_DIR/bin/v-verify-reseller-child" "$BIN_DIR/v-verify-reseller-child"
	sed -i 's/\r$//' "$BIN_DIR/v-verify-reseller-child"
	chmod 755 "$BIN_DIR/v-verify-reseller-child"
	chown root:root "$BIN_DIR/v-verify-reseller-child"
	echo "  Installed v-verify-reseller-child"
fi

echo "==> Installing func/reseller_security.sh..."
cp "$PLUGIN_DIR/func/reseller_security.sh" "$HESTIA/func/reseller_security.sh"
chmod 644 "$HESTIA/func/reseller_security.sh"
chown root:root "$HESTIA/func/reseller_security.sh"

echo "==> Hardening v-change-user-role and v-change-user-config-value..."
for cmd in v-change-user-role v-change-user-config-value; do
	dst="$BIN_DIR/$cmd"
	src="$PLUGIN_DIR/patches/overlay-bin/$cmd"
	if [ ! -f "$src" ]; then
		echo "Missing overlay $src"
		exit 1
	fi
	if [ -f "$dst" ] && ! grep -q "HESTIA_RESELLER_ROLE_GUARD" "$dst" 2>/dev/null; then
		cp "$dst" "${dst}.bak-reseller-$(date +%Y%m%d%H%M%S)"
		echo "  Backed up original $cmd"
	fi
	cp "$src" "$dst"
	sed -i 's/\r$//' "$dst"
	chmod 755 "$dst"
	chown root:root "$dst"
	echo "  Installed hardened $cmd"
done

echo "==> Patching main.php (route lockdown)..."
MAIN="$WEB/inc/main.php"
if [ ! -f "$MAIN" ]; then
	echo "  main.php not found, skipping"
else
	if grep -q "$MARKER_LOCKDOWN" "$MAIN"; then
		echo "  main.php lockdown already applied"
	else
		cp "$MAIN" "${MAIN}.bak-reseller-$(date +%Y%m%d%H%M%S)"
		# shellcheck disable=SC2016
		sed -i "/require_once dirname(__FILE__) . \"\/i18n.php\";/a\\
require_once dirname(__FILE__) . '/reseller_lockdown.php';\\
reseller_lockdown_enforce(); // $MARKER_LOCKDOWN" "$MAIN"
		echo "  main.php lockdown applied"
	fi
fi

echo "==> Patching login redirect for resellers..."
LOGIN_PATCH="$PLUGIN_DIR/patches/patch-login-reseller-redirect.py"
chmod +x "$LOGIN_PATCH" 2>/dev/null || true
if [ -f "$LOGIN_PATCH" ]; then
	python3 "$LOGIN_PATCH" "$WEB" || echo "  WARNING: login reseller redirect patch failed"
else
	echo "  Missing $LOGIN_PATCH"
fi

echo "==> Patching edit/user (admin role change requires HESTIA_ALLOW_ADMIN_ROLE)..."
EDIT_USER="$WEB/edit/user/index.php"
if [ -f "$EDIT_USER" ] && ! grep -q "$MARKER_ROLE_EXEC" "$EDIT_USER"; then
	if grep -q 'v-change-user-role' "$EDIT_USER"; then
		cp "$EDIT_USER" "${EDIT_USER}.bak-reseller-$(date +%Y%m%d%H%M%S)"
		python3 <<PY
from pathlib import Path
p = Path("$EDIT_USER")
text = p.read_text(encoding="utf-8")
old = 'HESTIA_CMD . "v-change-user-role "'
new = '/* $MARKER_ROLE_EXEC */ "/usr/bin/sudo /usr/bin/env HESTIA_ALLOW_ADMIN_ROLE=yes " . HESTIA_DIR_BIN . "v-change-user-role "'
if old not in text:
    raise SystemExit("v-change-user-role pattern not found")
p.write_text(text.replace(old, new, 1), encoding="utf-8")
print("  edit/user role exec hardened")
PY
	fi
else
	echo "  edit/user patch skipped or already applied"
fi

patch_panel_file() {
	local PANEL="$1"
	[ -f "$PANEL" ] || return 0
	if grep -q "$MARKER_NAV_BEGIN" "$PANEL"; then
		echo "  Nav lockdown already in $PANEL"
		return 0
	fi
	if ! grep -qE 'main-menu-list|Desktop menu' "$PANEL"; then
		echo "  Could not find nav block in $PANEL"
		return 0
	fi
	cp "$PANEL" "${PANEL}.bak-reseller-$(date +%Y%m%d%H%M%S)"
	PATCH_PY="$PLUGIN_DIR/patches/patch-panel-nav.py"
	chmod +x "$PATCH_PY" 2>/dev/null || true
	if ! python3 "$PATCH_PY" "$PANEL"; then
		echo "  ERROR: panel nav patch failed for $PANEL"
		echo "  Restore from ${PANEL}.bak-reseller-* if needed"
		return 1
	fi
}

echo "==> Patching panel navigation..."
patch_panel_file "$WEB/templates/includes/panel.php"
patch_panel_file "$WEB/templates-custom/includes/panel.php"
patch_panel_file "$HESTIA/plugins/hestia-theme/payload/templates-custom/includes/panel.php" 2>/dev/null || true

echo "==> Patching render_page for templates-custom pages..."
RENDER_PATCH="$PLUGIN_DIR/patches/patch-render-page.py"
chmod +x "$RENDER_PATCH" 2>/dev/null || true
if [ -f "$WEB/inc/main.php" ]; then
	if ! grep -q "HESTIA_RESELLER_TEMPLATE_OVERRIDE" "$WEB/inc/main.php"; then
		cp "$WEB/inc/main.php" "${WEB}/inc/main.php.bak-reseller-$(date +%Y%m%d%H%M%S)"
	fi
	python3 "$RENDER_PATCH" "$WEB/inc/main.php" || echo "  WARNING: render_page patch failed"
fi

echo "==> Patching login / main / edit/user / logout for reseller customer access..."
PATCH_ACCESS="$PLUGIN_DIR/patches/patch-reseller-customer-access.py"
chmod +x "$PATCH_ACCESS" 2>/dev/null || true
python3 "$PATCH_ACCESS" "$WEB"

echo ""
echo "Security patches applied."
