#!/usr/bin/env python3
"""Redirect reseller logins to /list/reseller-dashboard/."""

import re
import sys
from pathlib import Path

MARKER = "HESTIA_RESELLER_LOGIN_REDIRECT"


def patch_login(text: str) -> str | None:
    if MARKER in text:
        return text

    text2 = re.sub(
        r"(if \(\$_SESSION\[\"userContext\"\] === \"admin\" && !isset\(\$_SESSION\[\"look\"\]\)\) \{\s*"
        r"header\(\"Location: /list/user/\"\);\s*"
        r"exit\(\);\s*"
        r"\})\s*"
        r"(// Obtain account properties)",
        r"\1\n\n\t\t/* " + MARKER + " — reseller panel home */\n"
        r"\t\tif (($_SESSION['userContext'] ?? '') === 'reseller' && ($_SESSION['look'] ?? '') === '') {\n"
        r"\t\t\theader(\"Location: /list/reseller-dashboard/\");\n"
        r"\t\t\texit();\n"
        r"\t\t}\n\n\t\t\2",
        text,
        count=1,
        flags=re.MULTILINE,
    )
    if text2 == text:
        return None
    text = text2

    text2 = re.sub(
        r"(if \(\$_SESSION\[\"userContext\"\] === \"admin\"\) \{\s*"
        r"header\(\"Location: /list/user/\"\);\s*"
        r"\} else \{\s*)"
        r"(if \(\$data\[\$user\]\[\"WEB_DOMAINS\"\] != \"0\"\))",
        r"\1/* " + MARKER + " */\n"
        r"\t\t\t\t\t\tif (($_SESSION['userContext'] ?? '') === 'reseller') {\n"
        r"\t\t\t\t\t\t\theader(\"Location: /list/reseller-dashboard/\");\n"
        r"\t\t\t\t\t\t\texit();\n"
        r"\t\t\t\t\t\t}\n"
        r"\t\t\t\t\t\t\2",
        text,
        count=1,
        flags=re.MULTILINE,
    )
    if text2 == text:
        return None
    return text2


def patch_index(text: str) -> str | None:
    marker = f"{MARKER}_INDEX"
    if marker in text:
        return text

    old = 'header("Location: /" . (isset($_SESSION["user"]) ? "list/user" : "login") . "/");'
    new = f"""/* {marker} */
if (isset($_SESSION["user"])) {{
\tif (($_SESSION["userContext"] ?? "") === "reseller") {{
\t\theader("Location: /list/reseller-dashboard/");
\t}} else {{
\t\theader("Location: /list/user/");
\t}}
}} else {{
\theader("Location: /login/");
}}"""
    if old not in text:
        return None
    return text.replace(old, new, 1)


def patch_main_home(text: str) -> str | None:
    marker = f"{MARKER}_HOME"
    if marker in text:
        return text

    old = """\t// Set home location URLs
\tif ($_SESSION["userContext"] === "admin" && empty($_SESSION["look"])) {
\t\t// Display users list for administrators unless they are impersonating a user account
\t\t$home_url = "/list/user/";
\t} else {"""

    new = f"""\t// Set home location URLs
\tif ($_SESSION["userContext"] === "admin" && empty($_SESSION["look"])) {{
\t\t// Display users list for administrators unless they are impersonating a user account
\t\t$home_url = "/list/user/";
\t}} elseif (($_SESSION["userContext"] ?? "") === "reseller" && empty($_SESSION["look"])) {{
\t\t/* {marker} */
\t\t$home_url = "/list/reseller-dashboard/";
\t}} else {{"""

    if old not in text:
        return None
    return text.replace(old, new, 1)


def main() -> int:
    web = Path(sys.argv[1]) if len(sys.argv) > 1 else Path("/usr/local/hestia/web")
    rc = 0

    login = web / "login/index.php"
    if login.is_file():
        text = login.read_text(encoding="utf-8")
        updated = patch_login(text)
        if updated is None and MARKER not in text:
            print(f"login: pattern not found ({login})")
            rc = 1
        elif updated and updated != text:
            login.write_text(updated, encoding="utf-8")
            print(f"Patched: {login}")

    for path, fn in [(web / "index.php", patch_index), (web / "inc/main.php", patch_main_home)]:
        if not path.is_file():
            continue
        text = path.read_text(encoding="utf-8")
        updated = fn(text)
        if updated and updated != text:
            path.write_text(updated, encoding="utf-8")
            print(f"Patched: {path}")

    return rc


if __name__ == "__main__":
    sys.exit(main())
