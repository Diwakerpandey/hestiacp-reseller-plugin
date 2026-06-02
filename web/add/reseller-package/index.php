<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "RESELLER";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

if ($_SESSION["userContext"] !== "admin" || $_SESSION["look"] !== "") {
	header("Location: /list/user/");
	exit();
}

$user_packages = [];
exec(HESTIA_CMD . "v-list-user-packages json", $up_out, $up_rc);
if ($up_rc === 0 && !empty($up_out)) {
	$user_packages = array_keys(json_decode(implode("", $up_out), true) ?: []);
}
unset($up_out);

if (!empty($_POST["ok"])) {
	verify_csrf($_POST);
	if (empty($_POST["v_package"])) {
		$_SESSION["error_msg"] = _("Package name is required.");
	} else {
		$allowed = isset($_POST["v_allowed_packages"]) && is_array($_POST["v_allowed_packages"])
			? implode(",", $_POST["v_allowed_packages"])
			: "default";
		$tmp = tempnam("/tmp", "rspkg");
		$contents = "MAX_USERS='" . ($_POST["v_max_users"] ?: "10") . "'\n"
			. "MAX_DISK='" . ($_POST["v_max_disk"] ?: "unlimited") . "'\n"
			. "MAX_BANDWIDTH='" . ($_POST["v_max_bandwidth"] ?: "unlimited") . "'\n"
			. "ALLOWED_PACKAGES='" . $allowed . "'\n";
		file_put_contents($tmp, $contents);
		exec(
			HESTIA_CMD . "v-add-reseller-package "
				. quoteshellarg($tmp) . " "
				. quoteshellarg($_POST["v_package"]),
			$output,
			$return_var,
		);
		unlink($tmp);
		check_return_code($return_var, $output);
		if (empty($_SESSION["error_msg"])) {
			$_SESSION["request_msg"] = _("Reseller package created.");
			$_SESSION["request_ok"] = "ok";
			header("Location: /list/reseller-package/");
			exit();
		}
	}
}

render_page($user, $TAB, "add_reseller_package");
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
