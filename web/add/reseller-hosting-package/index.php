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

if (!empty($_POST["ok"])) {
	verify_csrf($_POST);
	if (empty($_POST["v_package"])) {
		$_SESSION["error_msg"] = _("Package name is required.");
	} else {
		$tmp = tempnam("/tmp", "rspkg");
		$contents = "WEB_DOMAINS='" . ($_POST["v_web_domains"] ?: "1") . "'\n"
			. "WEB_ALIASES='unlimited'\n"
			. "DNS_DOMAINS='" . ($_POST["v_dns_domains"] ?: "1") . "'\n"
			. "DNS_RECORDS='unlimited'\n"
			. "MAIL_DOMAINS='" . ($_POST["v_mail_domains"] ?: "1") . "'\n"
			. "MAIL_ACCOUNTS='" . ($_POST["v_mail_accounts"] ?: "10") . "'\n"
			. "DATABASES='" . ($_POST["v_databases"] ?: "1") . "'\n"
			. "CRON_JOBS='unlimited'\n"
			. "DISK_QUOTA='" . ($_POST["v_disk_quota"] ?: "1024") . "'\n"
			. "BANDWIDTH='" . ($_POST["v_bandwidth"] ?: "10240") . "'\n"
			. "RATE_LIMIT='200'\n"
			. "SHELL='nologin'\n"
			. "BACKUPS='1'\n"
			. "BACKUPS_INCREMENTAL='no'\n";
		file_put_contents($tmp, $contents);
		exec(
			HESTIA_CMD . "v-add-reseller-hosting-package "
				. quoteshellarg($reseller_name) . " "
				. quoteshellarg($tmp) . " "
				. quoteshellarg($_POST["v_package"]),
			$output,
			$return_var,
		);
		unlink($tmp);
		check_return_code($return_var, $output);
		if (empty($_SESSION["error_msg"])) {
			$_SESSION["request_msg"] = _("Hosting package created.");
			$_SESSION["request_ok"] = "ok";
			$back = "/list/reseller-hosting-package/";
			if ($is_admin) {
				$back .= "?reseller=" . urlencode($reseller_name);
			}
			header("Location: " . $back);
			exit();
		}
	}
}

$v_reseller = $reseller_name;
render_page($user, $TAB, "add_reseller_hosting_package");
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
