<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "RESELLER_PKG";

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

if (!empty($_POST["action"]) && $_POST["action"] === "delete") {
	verify_csrf($_POST);
	$pkg = trim($_POST["v_package"] ?? "");
	if ($pkg !== "") {
		exec(
			HESTIA_CMD . "v-delete-reseller-hosting-package "
				. quoteshellarg($reseller_name) . " "
				. quoteshellarg($pkg),
			$out,
			$rc,
		);
		if ($rc === 0) {
			$_SESSION["request_msg"] = _("Package deleted.");
			$_SESSION["request_ok"] = "ok";
		} else {
			$_SESSION["error_msg"] = !empty($out) ? htmlspecialchars(implode("\n", $out)) : _("Delete failed.");
		}
	}
	$back = "/list/reseller-hosting-package/";
	if ($is_admin) {
		$back .= "?reseller=" . urlencode($reseller_name);
	}
	header("Location: " . $back);
	exit;
}

$data = [];
exec(
	HESTIA_CMD . "v-list-reseller-hosting-packages " . quoteshellarg($reseller_name) . " json",
	$output,
	$return_var,
);
if ($return_var === 0 && !empty($output)) {
	$data = json_decode(implode("", $output), true) ?: [];
}
unset($output);

$v_reseller = $reseller_name;
render_page($user, $TAB, "list_reseller_hosting_packages");
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
