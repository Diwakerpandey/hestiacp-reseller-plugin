<?php

use function Hestiacp\quoteshellarg\quoteshellarg;



$TAB = "RESELLER_USER";



include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/reseller_helpers.php";



$is_admin = ($_SESSION["userContext"] ?? "") === "admin" && empty($_SESSION["look"]);

$is_reseller = reseller_session_is_reseller();



if (!$is_admin && !$is_reseller) {

	header("Location: /list/web/");

	exit();

}



$reseller_name = $is_reseller ? ($_SESSION["user"] ?? "") : trim($_GET["reseller"] ?? $_POST["reseller"] ?? "");



// Load children once — same data as the list table (source of truth for Edit/Manage/Delete).

$data = reseller_fetch_children($reseller_name);



if ($is_admin && $reseller_name === "" && empty($_GET["edit"]) && empty($_GET["loginas"]) && empty($_POST["action"])) {

	header("Location: /list/reseller/");

	exit();

}



function reseller_user_list_back(string $reseller_name, bool $is_admin): string {

	$back = "/list/reseller-user/";

	if ($is_admin && $reseller_name !== "") {

		$back .= "?reseller=" . urlencode($reseller_name);

	}

	return $back;

}



function reseller_user_do_loginas(string $reseller_name, string $loginas_child): void {

	exec(HESTIA_CMD . "v-list-user " . quoteshellarg($loginas_child) . " json", $output, $return_var);

	if ($return_var !== 0 || empty($output)) {

		$_SESSION["error_msg"] = _("User not found.");

		header("Location: " . reseller_user_list_back($reseller_name, false));

		exit();

	}

	unset($output);

	$_SESSION["look"] = $loginas_child;

	unset($_SESSION["_sf2_attributes"], $_SESSION["_sf2_meta"]);

	$reseller = quoteshellarg($_SESSION["user"]);

	exec(

		HESTIA_CMD

			. "v-log-action "

			. $reseller

			. " 'Info' 'Reseller' 'Managing hosting user (User: "

			. $loginas_child

			. ")'",

		$output,

		$return_var,

	);

	unset($output);

	header("Location: /list/web/");

	exit();

}



// Manage (POST or GET with token — same ownership rule as the visible list).

$loginas_child = "";

if (!empty($_POST["action"]) && $_POST["action"] === "loginas") {

	verify_csrf($_POST);

	$loginas_child = trim($_POST["v_username"] ?? "");

} elseif (!empty($_GET["loginas"])) {

	verify_csrf($_GET);

	$loginas_child = trim($_GET["loginas"] ?? "");

}



if ($loginas_child !== "") {

	if (!$is_reseller || !empty($_SESSION["look"])) {

		header("Location: /list/reseller-dashboard/");

		exit();

	}

	if (!reseller_child_in_list($data, $loginas_child)) {

		$_SESSION["error_msg"] = _("Invalid hosting user.");

		header("Location: " . reseller_user_list_back($reseller_name, false));

		exit();

	}

	reseller_user_do_loginas($reseller_name, $loginas_child);

}



// Edit hosting user.

$edit_child = trim($_GET["edit"] ?? $_POST["v_edit_user"] ?? "");

if ($edit_child !== "" || !empty($_POST["save"])) {

	if ($edit_child === "") {

		header("Location: " . reseller_user_list_back($reseller_name, $is_admin));

		exit();

	}

	if (!reseller_child_in_list($data, $edit_child)) {

		$_SESSION["error_msg"] = _("Invalid hosting user.");

		header("Location: " . reseller_user_list_back($reseller_name, $is_admin));

		exit();

	}



	$pkg_opts = reseller_load_package_options($reseller_name);

	$hosting_packages = $pkg_opts["hosting_packages"];

	$legacy_packages = $pkg_opts["legacy_packages"];



	if (!empty($_POST["save"])) {

		verify_csrf($_POST);

		$errors = [];



		if (!empty($_POST["v_password"]) && !validate_password($_POST["v_password"])) {

			$errors[] = _("Password");

		}

		if (!empty($_POST["v_email"]) && !filter_var($_POST["v_email"], FILTER_VALIDATE_EMAIL)) {

			$errors[] = _("Email");

		}

		if (empty($_POST["v_package"])) {

			$errors[] = _("Package");

		}



		if (!empty($errors)) {

			$_SESSION["error_msg"] = _("Please check the form fields.");

		} else {

			$v_child = quoteshellarg($edit_child);



			if (!empty($_POST["v_password"])) {

				$v_password = tempnam("/tmp", "vst");

				file_put_contents($v_password, $_POST["v_password"] . "\n");

				exec(

					HESTIA_CMD . "v-change-user-password " . $v_child . " " . quoteshellarg($v_password),

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

						. $v_child

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

						. $v_child

						. " "

						. quoteshellarg($_POST["v_name"]),

					$output,

					$return_var,

				);

				check_return_code($return_var, $output);

				unset($output);

			}



			if (empty($_SESSION["error_msg"]) && !empty($_POST["v_package"])) {

				exec(

					HESTIA_CMD

						. "v-change-reseller-user-package "

						. quoteshellarg($reseller_name) . " "

						. $v_child . " "

						. quoteshellarg($_POST["v_package"]),

					$output,

					$return_var,

				);

				check_return_code($return_var, $output);

				unset($output);

			}



			if (empty($_SESSION["error_msg"])) {

				$_SESSION["request_msg"] = _("User updated successfully.");

				$_SESSION["request_ok"] = "ok";

				header("Location: " . reseller_user_list_back($reseller_name, $is_admin));

				exit();

			}

		}

	}



	exec(HESTIA_CMD . "v-list-user " . quoteshellarg($edit_child) . " json", $output, $return_var);

	if ($return_var !== 0 || empty($output)) {

		header("Location: " . reseller_user_list_back($reseller_name, $is_admin));

		exit();

	}

	$user_data = json_decode(implode("", $output), true);

	unset($output);

	$v_email = $user_data[$edit_child]["CONTACT"] ?? "";

	$v_name = $user_data[$edit_child]["NAME"] ?? "";

	$v_package = $user_data[$edit_child]["PACKAGE"] ?? "";

	$v_package_select = !empty($_POST["v_package"])

		? (string) $_POST["v_package"]

		: reseller_package_global_to_select(

			$reseller_name,

			$v_package,

			$hosting_packages,

			$legacy_packages,

		);

	$v_reseller = $reseller_name;

	$v_child = $edit_child;

	render_page($user, $TAB, "edit_reseller_hosting_user");

	$_SESSION["back"] = $_SERVER["REQUEST_URI"];

	exit();

}



if (!empty($_POST["action"]) && $_POST["action"] === "delete") {

	verify_csrf($_POST);

	$target = trim($_POST["v_username"] ?? "");

	if ($target !== "" && reseller_child_in_list($data, $target)) {

		$cmd = HESTIA_CMD . "v-delete-reseller-user "

			. quoteshellarg($reseller_name) . " "

			. quoteshellarg($target);

		exec($cmd, $out, $rc);

		if ($rc === 0) {

			$_SESSION["request_msg"] = _("Hosting user deleted.");

			$_SESSION["request_ok"] = "ok";

		} else {

			$_SESSION["error_msg"] = !empty($out) ? htmlspecialchars(implode("\n", $out)) : _("Delete failed.");

		}

	} elseif ($target !== "") {

		$_SESSION["error_msg"] = _("Invalid hosting user.");

	}

	header("Location: " . reseller_user_list_back($reseller_name, $is_admin));

	exit;

}



$stats = [];

exec(HESTIA_CMD . "v-get-reseller-stats " . quoteshellarg($reseller_name) . " json", $stats_out, $stats_rc);

if ($stats_rc === 0 && !empty($stats_out)) {

	$stats = json_decode(implode("", $stats_out), true) ?: [];

}

unset($stats_out);



$v_reseller = $reseller_name;

render_page($user, $TAB, "list_reseller_users");

$_SESSION["back"] = $_SERVER["REQUEST_URI"];

