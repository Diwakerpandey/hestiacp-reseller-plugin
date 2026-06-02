#!/usr/bin/env python3
"""Allow render_page() to load plugin templates from templates-custom/pages/."""

import sys
from pathlib import Path

MARKER = "HESTIA_RESELLER_TEMPLATE_OVERRIDE"
OLD = '\tinclude $__template_dir . "pages/" . $page . ".php";'
NEW = f"""\t// {MARKER}
\t$__page_file = dirname(__DIR__) . "/templates-custom/pages/" . $page . ".php";
\tif (!is_file($__page_file)) {{
\t\t$__page_file = $__template_dir . "pages/" . $page . ".php";
\t}}
\tif (preg_match('/^(list_|add_|edit_)reseller/', $page)) {{
\t\trequire_once dirname(__FILE__) . "/reseller_page_styles.php";
\t\treseller_print_inline_styles();
\t}}
\tinclude $__page_file;"""


STYLES_MARKER = "HESTIA_RESELLER_PAGE_STYLES"


def patch_styles_hook(text: str) -> str | None:
    if STYLES_MARKER in text or "reseller_page_styles.php" in text:
        return text
    needle = "\tinclude $__page_file;"
    if needle not in text:
        return None
    insert = f"""\tif (preg_match('/^(list_|add_|edit_)reseller/', $page)) {{
\t\t/* {STYLES_MARKER} */
\t\trequire_once dirname(__FILE__) . "/reseller_page_styles.php";
\t\treseller_print_inline_styles();
\t}}
\tinclude $__page_file;"""
    return text.replace(needle, insert, 1)


def patch_main(main_path: str) -> int:
    path = Path(main_path)
    if not path.is_file():
        print(f"Skip (not found): {main_path}")
        return 0

    text = path.read_text(encoding="utf-8")

    if MARKER not in text:
        if OLD not in text:
            print(f"render_page include line not found in {main_path}")
            return 1
        text = text.replace(OLD, NEW, 1)
        path.write_text(text, encoding="utf-8")
        print(f"Patched render_page: {main_path}")
        return 0

    updated = patch_styles_hook(text)
    if updated and updated != text:
        path.write_text(updated, encoding="utf-8")
        print(f"Patched page styles hook: {main_path}")
        return 0

    print(f"Already patched: {main_path}")
    return 0


def main() -> int:
    if len(sys.argv) < 2:
        print("Usage: patch-render-page.py /path/to/main.php")
        return 1
    rc = 0
    for arg in sys.argv[1:]:
        if patch_main(arg) != 0:
            rc = 1
    return rc


if __name__ == "__main__":
    sys.exit(main())
