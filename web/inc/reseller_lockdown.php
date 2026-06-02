<?php
/**
 * HestiaCP Reseller Hosting — panel route lockdown for ROLE=reseller.
 * Included from main.php when plugin security patches are applied.
 */

require_once __DIR__ . "/reseller_helpers.php";

/**
 * When main.php is not patched for reseller+look, fix $user / $user_plain for this request.
 */
function reseller_sync_impersonation_user(): void {
	if (!reseller_is_managing_customer()) {
		return;
	}
	global $user, $user_plain;
	$look = $_SESSION["look"];
	if (function_exists("Hestiacp\\quoteshellarg\\quoteshellarg")) {
		$user = \Hestiacp\quoteshellarg\quoteshellarg($look);
	} else {
		$user = escapeshellarg($look);
	}
	$user_plain = htmlentities($look);
}

function reseller_lockdown_admin_paths(): array {
	return [
		"/list/server/",
		"/list/user/",
		"/list/package/",
		"/list/ip/",
		"/list/firewall/",
		"/list/updates/",
		"/list/reseller/",
		"/list/reseller-package/",
		"/list/reseller-dashboard/",
		"/list/reseller-user/",
		"/list/reseller-hosting-package/",
		"/add/reseller",
		"/add/package/",
		"/add/ip/",
		"/add/firewall/",
		"/bulk/",
		"/update/hestia/",
		"/list/mail-monitor/",
		"/list/resource-monitor/",
		"/list/cgroup-manager/",
		"/scan/malware/",
		"/copy/package/",
	];
}

function reseller_lockdown_enforce_customer_session(): void {
	$reseller = $_SESSION["user"] ?? "";
	$look = $_SESSION["look"] ?? "";

	if (!reseller_user_owns_child($reseller, $look)) {
		unset($_SESSION["look"]);
		header("Location: /list/reseller-dashboard/");
		exit();
	}

	$path = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH);
	$path = $path ?: "/";
	if (substr($path, -1) !== "/") {
		$path .= "/";
	}

	if ($path === "/login/" || $path === "/logout/") {
		return;
	}

	foreach (reseller_lockdown_admin_paths() as $prefix) {
		if (strpos($path, $prefix) === 0) {
			header("Location: /list/web/");
			exit();
		}
	}
}

function reseller_lockdown_enforce(): void {
	if (defined("NO_AUTH_REQUIRED")) {
		return;
	}

	if (!isset($_SESSION["user"])) {
		return;
	}

	if (!reseller_session_is_reseller()) {
		return;
	}

	// Managing a hosting customer (login as user)
	if (reseller_is_managing_customer()) {
		reseller_sync_impersonation_user();
		reseller_lockdown_enforce_customer_session();
		return;
	}

	$path = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH);
	$path = $path ?: "/";
	if (substr($path, -1) !== "/") {
		$path .= "/";
	}

	if ($path === "/login/" || $path === "/logout/") {
		return;
	}

	// Resellers may only impersonate via /list/reseller-user/ (POST + ownership check).
	if (strpos($path, "/login/") === 0 && !empty($_GET["loginas"])) {
		header("Location: /list/reseller-user/");
		exit();
	}

	// Edit reseller account or owned hosting user
	if (strpos($path, "/edit/user/") === 0) {
		$edit_user = $_GET["user"] ?? "";
		if ($edit_user === "" || $edit_user === $_SESSION["user"]) {
			return;
		}
		if (reseller_user_owns_child($_SESSION["user"], $edit_user)) {
			return;
		}
		header("Location: /list/reseller-dashboard/");
		exit();
	}

	$allowed_prefixes = [
		"/list/reseller-dashboard/",
		"/list/reseller-account/",
		"/list/reseller-user/",
		"/add/reseller-user/",
		"/list/reseller-hosting-package/",
		"/add/reseller-hosting-package/",
		"/error/",
	];

	foreach ($allowed_prefixes as $prefix) {
		if (strpos($path, $prefix) === 0) {
			return;
		}
	}

	header("Location: /list/reseller-dashboard/");
	exit();
}

// Run after main.php defines $user (included from main.php after user assignment).
reseller_sync_impersonation_user();
