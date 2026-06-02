<?php
// Legacy URL — redirect to reseller-user (same route as Hosting Users list).
$params = [];
$user = trim($_GET["user"] ?? "");
if ($user !== "") {
	$params["edit"] = $user;
}
if (!empty($_GET["token"])) {
	$params["token"] = $_GET["token"];
}
if (!empty($_GET["reseller"])) {
	$params["reseller"] = $_GET["reseller"];
}
$query = http_build_query($params);
header("Location: /list/reseller-user/" . ($query !== "" ? "?" . $query : ""));
exit();
