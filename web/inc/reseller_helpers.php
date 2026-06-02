<?php

/**

 * PHP helpers for HestiaCP Reseller Hosting plugin.

 */



use function Hestiacp\quoteshellarg\quoteshellarg;



function reseller_session_is_reseller(): bool {

	if (($_SESSION["userContext"] ?? "") === "reseller") {

		return true;

	}

	if (($_SESSION["role"] ?? "") === "reseller") {

		return true;

	}

	$user = $_SESSION["user"] ?? "";

	if ($user === "") {

		return false;

	}

	return is_readable("/usr/local/hestia/data/resellers/" . $user . "/reseller.conf");

}



/** @return array<string, array<string, string>> */

function reseller_fetch_children(string $reseller): array {

	if ($reseller === "") {

		return [];

	}

	exec(

		HESTIA_CMD . "v-list-reseller-users " . quoteshellarg($reseller) . " json",

		$output,

		$return_var,

	);

	if ($return_var !== 0 || empty($output)) {

		return [];

	}

	$data = json_decode(implode("", $output), true);

	return is_array($data) ? $data : [];

}



function reseller_child_in_list(array $children, string $child): bool {

	return $child !== "" && array_key_exists($child, $children);

}



function reseller_user_owns_child(string $reseller, string $child): bool {

	if ($reseller === "" || $child === "" || $reseller === $child) {

		return false;

	}

	$children = reseller_fetch_children($reseller);

	if (reseller_child_in_list($children, $child)) {

		return true;

	}

	$verify_bin = "/usr/local/hestia/bin/v-verify-reseller-child";

	if (is_executable($verify_bin)) {

		exec(

			HESTIA_CMD . "v-verify-reseller-child "

				. quoteshellarg($reseller) . " "

				. quoteshellarg($child),

			$output,

			$return_var,

		);

		return $return_var === 0;

	}

	return false;

}



function reseller_is_managing_customer(): bool {

	if (!reseller_session_is_reseller()) {

		return false;

	}

	return !empty($_SESSION["look"])

		&& ($_SESSION["look"] ?? "") !== ($_SESSION["user"] ?? "");

}



/** @return array{hosting_packages: array<string, array<string, string>>, legacy_packages: string[]} */

function reseller_load_package_options(string $reseller): array {

	$hosting_packages = [];

	$legacy_packages = [];

	if ($reseller === "") {

		return ["hosting_packages" => $hosting_packages, "legacy_packages" => $legacy_packages];

	}

	exec(

		HESTIA_CMD . "v-list-reseller-hosting-packages " . quoteshellarg($reseller) . " json",

		$hp_out,

		$hp_rc,

	);

	if ($hp_rc === 0 && !empty($hp_out)) {

		$hosting_packages = json_decode(implode("", $hp_out), true) ?: [];

	}

	unset($hp_out);

	exec(HESTIA_CMD . "v-get-reseller-stats " . quoteshellarg($reseller) . " json", $st_out, $st_rc);

	if ($st_rc === 0 && !empty($st_out)) {

		$st = json_decode(implode("", $st_out), true);

		$ap = $st["ALLOWED_PACKAGES"] ?? "";

		if ($ap !== "") {

			$legacy_packages = array_values(

				array_filter(array_map("trim", explode(",", $ap))),

			);

		}

	}

	unset($st_out);

	return ["hosting_packages" => $hosting_packages, "legacy_packages" => $legacy_packages];

}



/** Map global Hestia package name to form select value (display name or legacy name). */

function reseller_package_global_to_select(

	string $reseller,

	string $global_pkg,

	array $hosting_packages,

	array $legacy_packages,

): string {

	foreach ($hosting_packages as $name => $meta) {

		if (!is_array($meta)) {

			continue;

		}

		$global_name = $meta["GLOBAL_NAME"] ?? "rs_{$reseller}_{$name}";

		if ($global_name === $global_pkg) {

			return (string) $name;

		}

	}

	if (in_array($global_pkg, $legacy_packages, true)) {

		return $global_pkg;

	}

	return $global_pkg;

}

