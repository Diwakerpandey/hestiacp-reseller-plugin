<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "RESELLER";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

if ($_SESSION["userContext"] !== "admin" || $_SESSION["look"] !== "") {
	header("Location: /list/user/");
	exit();
}

$packages = [];
exec(HESTIA_CMD . "v-list-reseller-packages json", $pkg_out, $pkg_rc);
if ($pkg_rc === 0 && !empty($pkg_out)) {
	$packages = json_decode(implode("", $pkg_out), true) ?: [];
}
unset($pkg_out);

if (!empty($_POST["ok"])) {
	verify_csrf($_POST);
	$errors = [];
	if (empty($_POST["v_username"])) {
		$errors[] = _("Username");
	}
	if (empty($_POST["v_password"])) {
		$errors[] = _("Password");
	}
	if (empty($_POST["v_email"])) {
		$errors[] = _("Email");
	}
	if (empty($_POST["v_reseller_package"])) {
		$errors[] = _("Reseller Package");
	}
	if (!empty($errors)) {
		$_SESSION["error_msg"] = sprintf(_('Field "%s" can not be blank.'), implode(", ", $errors));
	} elseif (!validate_password($_POST["v_password"])) {
		$_SESSION["error_msg"] = _("Password does not match the minimum requirements.");
	} else {
		$v_password = tempnam("/tmp", "vst");
		file_put_contents($v_password, $_POST["v_password"] . "\n");
		$cmd = HESTIA_CMD . "v-add-reseller "
			. quoteshellarg($_POST["v_username"]) . " "
			. quoteshellarg($v_password) . " "
			. quoteshellarg($_POST["v_email"]) . " "
			. quoteshellarg($_POST["v_reseller_package"]) . " "
			. quoteshellarg($_POST["v_name"] ?? $_POST["v_username"]);
		exec($cmd, $output, $return_var);
		unlink($v_password);
		check_return_code($return_var, $output);
		if (empty($_SESSION["error_msg"])) {
			$_SESSION["request_msg"] = _("Reseller account created.");
			$_SESSION["request_ok"] = "ok";
			header("Location: /list/reseller/");
			exit();
		}
	}
}

render_page($user, $TAB, "add_reseller");
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
