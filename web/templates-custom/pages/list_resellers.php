<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/user/">
				<i class="fas fa-arrow-left icon-blue"></i><?= _("Back") ?>
			</a>
			<a href="/add/reseller/" class="button button-secondary">
				<i class="fas fa-circle-plus icon-green"></i><?= _("Add Reseller") ?>
			</a>
			<a href="/list/reseller-package/" class="button button-secondary">
				<i class="fas fa-box icon-orange"></i><?= _("Reseller Packages") ?>
			</a>
		</div>
	</div>
</div>

<div class="container">
	<h1 class="u-text-center u-mb20"><?= _("Resellers") ?></h1>
	<p class="u-text-center u-mb20"><?= _("Sell hosting through reseller accounts with pooled limits (like DirectAdmin / cPanel reseller plans).") ?></p>

	<?php if (empty($data)): ?>
		<p class="u-text-center"><?= _("No resellers yet.") ?></p>
	<?php else: ?>
	<div class="units-table js-units-container">
		<div class="units-table-header">
			<div class="units-table-cell"><?= _("Username") ?></div>
			<div class="units-table-cell"><?= _("Plan") ?></div>
			<div class="units-table-cell u-text-center"><?= _("Users") ?></div>
			<div class="units-table-cell u-text-center"><?= _("Disk (MB)") ?></div>
			<div class="units-table-cell u-text-center"><?= _("Bandwidth") ?></div>
			<div class="units-table-cell"></div>
		</div>
		<?php foreach ($data as $name => $row): ?>
		<div class="units-table-row">
			<div class="units-table-cell u-text-bold"><?= htmlspecialchars($name) ?></div>
			<div class="units-table-cell"><?= htmlspecialchars($row["RESELLER_PACKAGE"] ?? "") ?></div>
			<div class="units-table-cell u-text-center"><?= htmlspecialchars(($row["R_USERS"] ?? "0") . " / " . ($row["MAX_USERS"] ?? "?")) ?></div>
			<div class="units-table-cell u-text-center"><?= htmlspecialchars(($row["R_DISK"] ?? "0") . " / " . ($row["MAX_DISK"] ?? "?")) ?></div>
			<div class="units-table-cell u-text-center"><?= htmlspecialchars($row["R_BANDWIDTH"] ?? "0") ?></div>
			<div class="units-table-cell u-text-right">
				<a href="/list/reseller-user/?reseller=<?= urlencode($name) ?>" class="button button-secondary button-xs"><?= _("Users") ?></a>
				<form method="post" class="u-inline" onsubmit="return confirm('<?= _("Delete this reseller?") ?>');">
					<input type="hidden" name="token" value="<?= $_SESSION["token"] ?>">
					<input type="hidden" name="action" value="delete">
					<input type="hidden" name="v_username" value="<?= htmlspecialchars($name) ?>">
					<button type="submit" class="button button-secondary button-xs"><?= _("Delete") ?></button>
				</form>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
</div>
