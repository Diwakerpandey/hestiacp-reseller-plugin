#!/usr/bin/env python3

"""Patch Hestia web PHP for reseller login-as and edit customer users."""



import sys

from pathlib import Path



MARKER = "HESTIA_RESELLER_CUSTOMER_ACCESS"





def patch_file(path: Path, transform, label: str = "") -> bool:

    if not path.is_file():

        print(f"Skip (missing): {path}")

        return False

    text = path.read_text(encoding="utf-8")

    new_text = transform(text)

    tag = f" ({label})" if label else ""

    if new_text is None:

        print(f"No changes for: {path}{tag}")

        return False

    if new_text == text:

        print(f"Already patched: {path}{tag}")

        return True

    path.write_text(new_text, encoding="utf-8")

    print(f"Patched: {path}{tag}")

    return True





def patch_main(text: str):

    if f"/* {MARKER} */" in text:

        return text

    old = (

        'if (isset($_SESSION["look"]) && $_SESSION["look"] != "" '

        '&& $_SESSION["userContext"] === "admin") {'

    )

    new = (

        f"/* {MARKER} */\n"

        'if (isset($_SESSION["look"]) && $_SESSION["look"] != "" '

        '&& ($_SESSION["userContext"] === "admin" || $_SESSION["userContext"] === "reseller")) {'

    )

    if old not in text:

        return None

    return text.replace(old, new, 1)





def patch_login(text: str):

    if f"{MARKER}_LOGINAS" in text:

        return text



    block = f'''

\t// {MARKER}_LOGINAS — reseller may manage owned hosting users

\tif (($_SESSION["userContext"] ?? "") === "reseller" && !empty($_GET["loginas"])) {{

\t\tif (verify_csrf($_GET)) {{

\t\t\trequire_once $_SERVER["DOCUMENT_ROOT"] . "/inc/reseller_helpers.php";

\t\t\t$loginas = $_GET["loginas"];

\t\t\tif (!reseller_user_owns_child($_SESSION["user"], $loginas)) {{

\t\t\t\theader("Location: /list/reseller-user/");

\t\t\t\texit();

\t\t\t}}

\t\t\t$v_user = quoteshellarg($loginas);

\t\t\t$v_impersonator = quoteshellarg($_SESSION["user"]);

\t\t\texec(HESTIA_CMD . "v-list-user " . $v_user . " json", $output, $return_var);

\t\t\tif ($return_var == 0) {{

\t\t\t\t$data = json_decode(implode("", $output), true);

\t\t\t\treset($data);

\t\t\t\t$_SESSION["look"] = key($data);

\t\t\t\texec(

\t\t\t\t\tHESTIA_CMD .

\t\t\t\t\t\t"v-log-action " .

\t\t\t\t\t\t$v_impersonator .

\t\t\t\t\t\t" 'Info' 'Reseller' 'Logged in as hosting user (User: $v_user)'",

\t\t\t\t\t$output,

\t\t\t\t\t$return_var,

\t\t\t\t);

\t\t\t\tunset($_SESSION["_sf2_attributes"]);

\t\t\t\tunset($_SESSION["_sf2_meta"]);

\t\t\t\tif (!empty($_GET["edit_link"])) {{

\t\t\t\t\t$edit_link = urldecode($_GET["edit_link"]);

\t\t\t\t\theader("Location: " . $edit_link . "&token=" . $_SESSION["token"]);

\t\t\t\t\texit();

\t\t\t\t}}

\t\t\t\theader("Location: /list/web/");

\t\t\t\texit();

\t\t\t}}

\t\t}}

\t\texit();

\t}}



'''

    needle = "\t// User impersonation\n\t// Allow administrators"

    if needle not in text:

        return None

    text = text.replace(needle, block + needle, 1)



    old = '\tif ($_SESSION["userContext"] !== "admin" && !empty($_GET["loginas"])) {'

    new = (

        f'\tif ($_SESSION["userContext"] !== "admin" '

        f'&& ($_SESSION["userContext"] ?? "") !== "reseller" && !empty($_GET["loginas"])) {{'

    )

    if old not in text:

        return None

    return text.replace(old, new, 1)





def patch_edit_user(text: str):

    if f"Check user argument — {MARKER}" in text:

        return text

    old = """// Check user argument

if (empty($_GET["user"])) {

\theader("Location: /list/user/");

\texit();

}



// Edit as someone else?

if ($_SESSION["userContext"] === "admin" && !empty($_GET["user"])) {

\t$user = $_GET["user"];

\t$v_username = $_GET["user"];

} else {

\t$user = $_SESSION["user"];

\t$v_username = $_SESSION["user"];

}"""

    new = f"""// Check user argument — {MARKER}

if (empty($_GET["user"])) {{

\tif (($_SESSION["userContext"] ?? "") === "reseller") {{

\t\theader("Location: /list/reseller-account/");

\t\texit();

\t}}

\theader("Location: /list/user/");

\texit();

}}



// Edit as someone else?

if ($_SESSION["userContext"] === "admin" && !empty($_GET["user"])) {{

\t$user = $_GET["user"];

\t$v_username = $_GET["user"];

}} elseif (($_SESSION["userContext"] ?? "") === "reseller" && !empty($_GET["user"])) {{

\trequire_once $_SERVER["DOCUMENT_ROOT"] . "/inc/reseller_helpers.php";

\tif (!reseller_user_owns_child($_SESSION["user"], $_GET["user"])) {{

\t\theader("Location: /list/reseller-user/");

\t\texit();

\t}}

\t$user = $_GET["user"];

\t$v_username = $_GET["user"];

}} else {{

\t$user = $_SESSION["user"];

\t$v_username = $_SESSION["user"];

}}"""

    if old not in text:

        return None

    return text.replace(old, new, 1)





def patch_logout(text: str):

    if f"{MARKER}_LOGOUT_LOOK" in text:

        return text



    old_look = '\tunset($_SESSION["_sf2_meta"]);\n\theader("Location: /");\n} else {'

    new_look = f"""\tunset($_SESSION["_sf2_meta"]);

\t// {MARKER}_LOGOUT_LOOK

\tif (($_SESSION["userContext"] ?? "") === "reseller") {{

\t\theader("Location: /list/reseller-dashboard/");

\t}} else {{

\t\theader("Location: /");

\t}}

}} else {{"""

    if old_look in text:

        return text.replace(old_look, new_look, 1)



    # Hestia variants (comment style / spacing)

    old_look_alt = '\tunset($_SESSION["_sf2_meta"]);\r\n\theader("Location: /");\r\n} else {'

    if old_look_alt in text:

        new_look_crlf = new_look.replace("\n", "\r\n")

        return text.replace(old_look_alt, new_look_crlf, 1)



    return None





def patch_login_session_redirect(text: str):

    marker = f"{MARKER}_SESSION_REDIRECT"

    if marker in text:

        return text



    old = """\t// Redirect everyone to new dashboard page (impersonation still respects loginas flow)

\tif (empty($_GET["loginas"])) {

\t\theader("Location: /list/dashboard/");

\t\texit();

\t}"""

    new = f"""\t// Redirect everyone to new dashboard page (impersonation still respects loginas flow)

\t// {marker}

\tif (empty($_GET["loginas"])) {{

\t\tif (($_SESSION["userContext"] ?? "") === "reseller") {{

\t\t\theader("Location: /list/reseller-dashboard/");

\t\t}} else {{

\t\t\theader("Location: /list/dashboard/");

\t\t}}

\t\texit();

\t}}"""

    if old in text:

        return text.replace(old, new, 1)



    # Already has reseller redirect from apply-security-patches.sh (HESTIA_RESELLER_LOGIN_REDIRECT)

    if "HESTIA_RESELLER_LOGIN_REDIRECT" in text and "reseller-dashboard" in text:

        return text



    return None





def main():

    web = Path(sys.argv[1]) if len(sys.argv) > 1 else Path("/usr/local/hestia/web")

    patch_file(web / "inc/main.php", patch_main, "main-look")

    patch_file(web / "login/index.php", patch_login, "login-loginas")

    patch_file(web / "login/index.php", patch_login_session_redirect, "login-session-redirect")

    patch_file(web / "edit/user/index.php", patch_edit_user, "edit-user")

    patch_file(web / "logout/index.php", patch_logout, "logout-look")

    return 0





if __name__ == "__main__":

    sys.exit(main())

