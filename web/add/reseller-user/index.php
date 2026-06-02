<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "RESELLER_USER";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

$is_admin = $_SESSION["userContext"] === "admin" && $_SESSION["look"] === "";
$is_reseller = $_SESSION["userContext"] === "reseller";

if (!$is_admin && !$is_reseller) {
	header("Location: /list/web/");
	exit();
}

$reseller_name = $is_reseller ? $_SESSION["user"] : trim($_GET["reseller"] ?? "");
if ($is_admin && $reseller_name === "") {
	header("Location: /list/reseller/");
	exit();
}

$hosting_packages = [];
exec(
	HESTIA_CMD . "v-list-reseller-hosting-packages " . quoteshellarg($reseller_name) . " json",
	$hp_out,
	$hp_rc,
);
if ($hp_rc === 0 && !empty($hp_out)) {
	$hosting_packages = json_decode(implode("", $hp_out), true) ?: [];
}
unset($hp_out);

// Fallback: admin-assigned global package names (legacy)
$legacy_packages = [];
exec(HESTIA_CMD . "v-get-reseller-stats " . quoteshellarg($reseller_name) . " json", $st_out, $st_rc);
if ($st_rc === 0 && !empty($st_out)) {
	$st = json_decode(implode("", $st_out), true);
	$ap = $st["ALLOWED_PACKAGES"] ?? "";
	if ($ap !== "") {
		$legacy_packages = array_map("trim", explode(",", $ap));
	}
}
unset($st_out);

if (!empty($_POST["ok"])) {
	verify_csrf($_POST);
	$errors = [];
	foreach (["v_username", "v_password", "v_email", "v_package"] as $f) {
		if (empty($_POST[$f])) {
			$errors[] = $f;
		}
	}
	if (!empty($errors)) {
		$_SESSION["error_msg"] = _("All required fields must be filled.");
	} elseif (!validate_password($_POST["v_password"])) {
		$_SESSION["error_msg"] = _("Password does not match the minimum requirements.");
	} else {
		$v_password = tempnam("/tmp", "vst");
		file_put_contents($v_password, $_POST["v_password"] . "\n");
		$cmd = HESTIA_CMD . "v-add-reseller-user "
			. quoteshellarg($reseller_name) . " "
			. quoteshellarg($_POST["v_username"]) . " "
			. quoteshellarg($v_password) . " "
			. quoteshellarg($_POST["v_email"]) . " "
			. quoteshellarg($_POST["v_package"]) . " "
			. quoteshellarg($_POST["v_name"] ?? $_POST["v_username"]);
		exec($cmd, $output, $return_var);
		unlink($v_password);
		check_return_code($return_var, $output);
		if (empty($_SESSION["error_msg"])) {
			$_SESSION["request_msg"] = _("Hosting user created.");
			$_SESSION["request_ok"] = "ok";
			$back = "/list/reseller-user/";
			if ($is_admin) {
				$back .= "?reseller=" . urlencode($reseller_name);
			}
			header("Location: " . $back);
			exit();
		}
	}
}

$v_reseller = $reseller_name;
render_page($user, $TAB, "add_reseller_user");
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
