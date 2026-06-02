<?php
// Legacy URL — redirect to reseller-user (same route as Hosting Users list).
$query = $_SERVER["QUERY_STRING"] ?? "";
$user = trim($_GET["user"] ?? "");
if ($user !== "") {
	$params = ["loginas" => $user];
	if (!empty($_GET["token"])) {
		$params["token"] = $_GET["token"];
	}
	$query = http_build_query($params);
}
header("Location: /list/reseller-user/" . ($query !== "" ? "?" . $query : ""));
exit();
