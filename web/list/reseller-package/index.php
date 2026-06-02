<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "RESELLER";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

if ($_SESSION["userContext"] !== "admin" || $_SESSION["look"] !== "") {
	header("Location: /list/user/");
	exit();
}

if (!empty($_POST["action"]) && $_POST["action"] === "delete") {
	verify_csrf($_POST);
	$pkg = trim($_POST["v_package"] ?? "");
	if ($pkg !== "") {
		exec(HESTIA_CMD . "v-delete-reseller-package " . quoteshellarg($pkg), $out, $rc);
		if ($rc === 0) {
			$_SESSION["request_msg"] = _("Reseller package deleted.");
			$_SESSION["request_ok"] = "ok";
		} else {
			$_SESSION["error_msg"] = !empty($out) ? htmlspecialchars(implode("\n", $out)) : _("Delete failed.");
		}
	}
	header("Location: /list/reseller-package/");
	exit;
}

$data = [];
exec(HESTIA_CMD . "v-list-reseller-packages json", $output, $return_var);
if ($return_var === 0 && !empty($output)) {
	$decoded = json_decode(implode("", $output), true);
	if (is_array($decoded)) {
		$data = $decoded;
	}
}
unset($output);

render_page($user, $TAB, "list_reseller_packages");
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
