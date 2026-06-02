<?php
$is_reseller = ($_SESSION["userContext"] ?? "") === "reseller";
$add_url = "/add/reseller-hosting-package/" . ($is_reseller ? "" : "?reseller=" . urlencode($v_reseller ?? ""));
$list_users = "/list/reseller-user/" . ($is_reseller ? "" : "?reseller=" . urlencode($v_reseller ?? ""));
?>
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back" href="<?= $is_reseller ? '/list/reseller-dashboard/' : '/list/reseller/' ?>">
				<i class="fas fa-arrow-left icon-blue"></i><?= _("Back") ?>
			</a>
			<a href="<?= htmlspecialchars($add_url) ?>" class="button button-secondary">
				<i class="fas fa-circle-plus icon-green"></i><?= _("Add Package") ?>
			</a>
			<a href="<?= htmlspecialchars($list_users) ?>" class="button button-secondary">
				<i class="fas fa-users icon-orange"></i><?= _("Hosting Users") ?>
			</a>
		</div>
	</div>
</div>

<div class="container">
	<h1 class="u-text-center u-mb20"><?= _("Hosting Packages") ?></h1>
	<p class="u-text-center u-mb20"><?= _("Plans you assign when creating customer accounts.") ?></p>

	<?php if (empty($data)): ?>
		<p class="u-text-center"><?= _("No packages yet.") ?> <a href="<?= htmlspecialchars($add_url) ?>"><?= _("Create your first package") ?></a></p>
	<?php else: ?>
	<div class="units-table">
		<div class="units-table-header">
			<div class="units-table-cell"><?= _("Package") ?></div>
			<div class="units-table-cell u-text-center"><?= _("Web") ?></div>
			<div class="units-table-cell u-text-center"><?= _("Disk (MB)") ?></div>
			<div class="units-table-cell u-text-center"><?= _("Bandwidth") ?></div>
			<div class="units-table-cell u-text-center"><?= _("DB") ?></div>
			<div class="units-table-cell"></div>
		</div>
		<?php foreach ($data as $pkg => $row): ?>
		<div class="units-table-row">
			<div class="units-table-cell u-text-bold"><?= htmlspecialchars($pkg) ?></div>
			<div class="units-table-cell u-text-center"><?= htmlspecialchars($row["WEB_DOMAINS"] ?? "") ?></div>
			<div class="units-table-cell u-text-center"><?= htmlspecialchars($row["DISK_QUOTA"] ?? "") ?></div>
			<div class="units-table-cell u-text-center"><?= htmlspecialchars($row["BANDWIDTH"] ?? "") ?></div>
			<div class="units-table-cell u-text-center"><?= htmlspecialchars($row["DATABASES"] ?? "") ?></div>
			<div class="units-table-cell u-text-right">
				<form method="post" class="u-inline" onsubmit="return confirm('<?= _("Delete package?") ?>');">
					<input type="hidden" name="token" value="<?= $_SESSION["token"] ?>">
					<input type="hidden" name="action" value="delete">
					<input type="hidden" name="v_package" value="<?= htmlspecialchars($pkg) ?>">
					<button type="submit" class="button button-secondary button-xs"><?= _("Delete") ?></button>
				</form>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
</div>
