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
	$target = trim($_POST["v_username"] ?? "");
	if ($target !== "") {
		$cmd = HESTIA_CMD . "v-delete-reseller " . quoteshellarg($target);
		exec($cmd, $out, $rc);
		if ($rc === 0) {
			$_SESSION["request_msg"] = _("Reseller deleted.");
			$_SESSION["request_ok"] = "ok";
		} else {
			$_SESSION["error_msg"] = !empty($out) ? htmlspecialchars(implode("\n", $out)) : _("Delete failed.");
		}
	}
	header("Location: /list/reseller/");
	exit;
}

$data = [];
exec(HESTIA_CMD . "v-list-resellers json", $output, $return_var);
if ($return_var === 0 && !empty($output)) {
	$decoded = json_decode(implode("", $output), true);
	if (is_array($decoded)) {
		$data = $decoded;
	}
}
unset($output);

render_page($user, $TAB, "list_resellers");
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
