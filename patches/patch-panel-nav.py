#!/usr/bin/env python3
"""Patch Hestia panel.php navigation for reseller plugin (default + custom theme)."""

import re
import sys
from pathlib import Path

BEGIN = "HESTIA_RESELLER_PLUGIN_NAV_BEGIN"
END = "HESTIA_RESELLER_PLUGIN_NAV_END"
ADMIN_LINK = "HESTIA_RESELLER_ADMIN_NAV"


def patch_custom_theme(text: str) -> tuple[str, bool]:
    if "<!-- Desktop menu" not in text:
        return text, False

    pattern = re.compile(
        r"(<!-- Desktop menu \(text-only\) -->.*?<!-- Mobile menu \(text-only\) -->.*?</ul>\s*\n\t\t</div>\s*\n\t</nav>)",
        re.DOTALL,
    )
    match = pattern.search(text)
    if not match:
        return text, False

    block = match.group(1)
    replacement = (
        f"<?php /* {BEGIN} */ if (($_SESSION['userContext'] ?? '') === 'reseller' && ($_SESSION['look'] ?? '') === '') {{ ?>\n"
        f"<?php include $_SERVER['DOCUMENT_ROOT'] . '/inc/panel_reseller_nav.php'; ?>\n"
        f"<?php }} else {{ /* {END} */ ?>\n"
        f"{block}\n"
        f"<?php }} /* {END} */ ?>\n"
    )
    text = text[: match.start(1)] + replacement + text[match.end(1) :]

    if ADMIN_LINK not in text and 'href="/list/user/"' in text:
        insert = (
            f"\t\t\t\t<?php /* {ADMIN_LINK} */ if ($_SESSION[\"userContext\"] == \"admin\" && $_SESSION[\"look\"] === \"\") {{ ?>\n"
            '\t\t\t\t<li class="da-nav__item"><a class="da-nav__link <?= ($TAB === \'RESELLER\' ? \'active\' : \') ?>" href="/list/reseller/"><span class="da-nav__label"><?= _("Resellers") ?></span></a></li>\n'
            f"\t\t\t\t<?php }} ?>\n"
        )
        idx = text.find('href="/list/user/"')
        line_end = text.find("\n", idx)
        if line_end != -1:
            text = text[: line_end + 1] + insert + text[line_end + 1 :]

    return text, True


def patch_default_theme(text: str) -> tuple[str, bool]:
    if 'class="main-menu-list"' not in text:
        return text, False

    pattern = re.compile(
        r"(<ul x-cloak x-show=\"open\" class=\"main-menu-list\">)(.*?)(\n\t\t\t</ul>\s*\n\t\t</div>\s*\n\t</nav>)",
        re.DOTALL,
    )
    match = pattern.search(text)
    if not match:
        return text, False

    inner = match.group(2)
    replacement = (
        f"{match.group(1)}\n"
        f"\t\t\t\t<?php /* {BEGIN} */ if (($_SESSION['userContext'] ?? '') === 'reseller' && ($_SESSION['look'] ?? '') === '') {{ ?>\n"
        f"\t\t\t\t<?php include $_SERVER['DOCUMENT_ROOT'] . '/inc/panel_reseller_nav_default.php'; ?>\n"
        f"\t\t\t\t<?php }} else {{ /* {END} */ ?>\n"
        f"{inner}"
        f"\t\t\t\t<?php }} /* {END} */ ?>\n"
        f"{match.group(3)}"
    )
    text = text[: match.start()] + replacement + text[match.end() :]

    text = insert_default_admin_link(text)
    return text, True


def insert_default_admin_link(text: str) -> str:
    if ADMIN_LINK in text:
        return text
    admin_block = (
        f"\t\t\t\t<!-- Resellers tab ({ADMIN_LINK}) -->\n"
        "\t\t\t\t<?php if ($_SESSION['userContext'] == 'admin' && $_SESSION['look'] === '') { ?>\n"
        "\t\t\t\t\t<li class=\"main-menu-item\">\n"
        "\t\t\t\t\t\t<a class=\"main-menu-item-link <?php if (in_array($TAB, ['RESELLER'])) {\n"
        "\t\t\t\t\t\t\techo 'active';\n"
        "\t\t\t\t\t\t} ?>\" href=\"/list/reseller/\">\n"
        "\t\t\t\t\t\t\t<p class=\"main-menu-item-label\"><?= _('RESELLER') ?><i class=\"fas fa-user-tag\"></i></p>\n"
        "\t\t\t\t\t\t\t<ul class=\"main-menu-stats\">\n"
        "\t\t\t\t\t\t\t\t<li><?= _('Reseller accounts') ?></li>\n"
        "\t\t\t\t\t\t\t</ul>\n"
        "\t\t\t\t\t\t</a>\n"
        "\t\t\t\t\t</li>\n"
        "\t\t\t\t<?php } ?>\n\n"
    )
    web_marker = "<!-- Web tab -->"
    if web_marker in text:
        return text.replace(web_marker, admin_block + "\t\t\t\t" + web_marker, 1)
    return text


def patch_panel(panel_path: str) -> int:
    path = Path(panel_path)
    if not path.is_file():
        print(f"Skip (not found): {panel_path}")
        return 0

    text = path.read_text(encoding="utf-8")

    if BEGIN in text:
        updated = insert_default_admin_link(text)
        if updated != text:
            path.write_text(updated, encoding="utf-8")
            print(f"Added admin reseller link: {panel_path}")
        else:
            print(f"Already patched: {panel_path}")
        return 0

    if 'class="main-menu-list"' in text:
        text, ok = patch_default_theme(text)
        if ok:
            path.write_text(text, encoding="utf-8")
            print(f"Patched (default theme): {panel_path}")
            return 0
        print(f"Default theme nav pattern not found in {panel_path}")
        return 1

    if "<!-- Desktop menu" in text:
        text, ok = patch_custom_theme(text)
        if ok:
            path.write_text(text, encoding="utf-8")
            print(f"Patched (custom theme): {panel_path}")
            return 0
        print(f"Custom theme nav pattern not found in {panel_path}")
        return 1

    print(f"Unknown panel layout in {panel_path}")
    return 1


def main() -> int:
    if len(sys.argv) < 2:
        print("Usage: patch-panel-nav.py /path/to/panel.php [...]")
        return 1
    rc = 0
    for arg in sys.argv[1:]:
        if patch_panel(arg) != 0:
            rc = 1
    return rc


if __name__ == "__main__":
    sys.exit(main())
