<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "RESELLER_DASH";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/reseller_helpers.php";

if (!reseller_session_is_reseller() || !empty($_SESSION["look"])) {
	if ($_SESSION["userContext"] === "admin" && $_SESSION["look"] === "") {
		header("Location: /list/reseller/");
		exit();
	}
	header("Location: /login/");
	exit();
}

$reseller_name = $_SESSION["user"];
$stats = [];
exec(HESTIA_CMD . "v-get-reseller-stats " . quoteshellarg($reseller_name) . " json", $st_out, $st_rc);
if ($st_rc === 0 && !empty($st_out)) {
	$stats = json_decode(implode("", $st_out), true) ?: [];
}
unset($st_out);

$user_count = 0;
exec(
	HESTIA_CMD . "v-list-reseller-users " . quoteshellarg($reseller_name) . " json",
	$u_out,
	$u_rc,
);
if ($u_rc === 0 && !empty($u_out)) {
	$user_count = count(json_decode(implode("", $u_out), true) ?: []);
}
unset($u_out);

$pkg_count = 0;
exec(
	HESTIA_CMD . "v-list-reseller-hosting-packages " . quoteshellarg($reseller_name) . " json",
	$p_out,
	$p_rc,
);
if ($p_rc === 0 && !empty($p_out)) {
	$pkg_count = count(json_decode(implode("", $p_out), true) ?: []);
}
unset($p_out);

render_page($user, $TAB, "list_reseller_dashboard");
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
