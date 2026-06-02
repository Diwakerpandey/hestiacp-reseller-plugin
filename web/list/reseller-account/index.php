<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "RESELLER_ACCOUNT";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/reseller_helpers.php";

if (!reseller_session_is_reseller() || !empty($_SESSION["look"])) {
	header("Location: /list/reseller-dashboard/");
	exit();
}

$reseller_name = $_SESSION["user"];

if (!empty($_POST["save"])) {
	verify_csrf($_POST);
	$errors = [];

	if (!empty($_POST["v_password"]) && !validate_password($_POST["v_password"])) {
		$errors[] = _("Password");
	}
	if (!empty($_POST["v_email"]) && !filter_var($_POST["v_email"], FILTER_VALIDATE_EMAIL)) {
		$errors[] = _("Email");
	}

	if (!empty($errors)) {
		$_SESSION["error_msg"] = _("Please check the form fields.");
	} else {
		$v_user = quoteshellarg($reseller_name);

		if (!empty($_POST["v_password"])) {
			$v_password = tempnam("/tmp", "vst");
			file_put_contents($v_password, $_POST["v_password"] . "\n");
			exec(
				HESTIA_CMD . "v-change-user-password " . $v_user . " " . quoteshellarg($v_password),
				$output,
				$return_var,
			);
			unlink($v_password);
			check_return_code($return_var, $output);
			unset($output);
		}

		if (empty($_SESSION["error_msg"]) && !empty($_POST["v_email"])) {
			exec(
				HESTIA_CMD
					. "v-change-user-contact "
					. $v_user
					. " "
					. quoteshellarg($_POST["v_email"]),
				$output,
				$return_var,
			);
			check_return_code($return_var, $output);
			unset($output);
		}

		if (empty($_SESSION["error_msg"]) && !empty($_POST["v_name"])) {
			exec(
				HESTIA_CMD
					. "v-change-user-name "
					. $v_user
					. " "
					. quoteshellarg($_POST["v_name"]),
				$output,
				$return_var,
			);
			check_return_code($return_var, $output);
			unset($output);
		}

		if (empty($_SESSION["error_msg"])) {
			$_SESSION["request_msg"] = _("Account updated successfully.");
			$_SESSION["request_ok"] = "ok";
			header("Location: /list/reseller-account/");
			exit();
		}
	}
}

exec(HESTIA_CMD . "v-list-user " . quoteshellarg($reseller_name) . " json", $output, $return_var);
if ($return_var !== 0 || empty($output)) {
	header("Location: /list/reseller-dashboard/");
	exit();
}
$user_data = json_decode(implode("", $output), true);
unset($output);

$v_email = $user_data[$reseller_name]["CONTACT"] ?? "";
$v_name = $user_data[$reseller_name]["NAME"] ?? "";
$v_package = $user_data[$reseller_name]["PACKAGE"] ?? "";

render_page($user, $TAB, "edit_reseller_account");
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
