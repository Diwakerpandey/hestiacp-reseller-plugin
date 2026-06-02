<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/reseller/">
				<i class="fas fa-arrow-left icon-blue"></i><?= _("Back") ?>
			</a>
			<a href="/add/reseller-package/" class="button button-secondary">
				<i class="fas fa-circle-plus icon-green"></i><?= _("Add Reseller Package") ?>
			</a>
		</div>
	</div>
</div>

<div class="container">
	<h1 class="u-text-center u-mb20"><?= _("Reseller Packages") ?></h1>
	<p class="u-text-center u-mb20"><?= _("Plans that define how many hosting accounts and resources each reseller may sell.") ?></p>

	<?php if (empty($data)): ?>
		<p class="u-text-center"><?= _("No reseller packages yet.") ?></p>
	<?php else: ?>
	<div class="units-table">
		<div class="units-table-header">
			<div class="units-table-cell"><?= _("Package") ?></div>
			<div class="units-table-cell u-text-center"><?= _("Max Users") ?></div>
			<div class="units-table-cell u-text-center"><?= _("Max Disk") ?></div>
			<div class="units-table-cell u-text-center"><?= _("Max Bandwidth") ?></div>
			<div class="units-table-cell"><?= _("Allowed Hosting Packages") ?></div>
			<div class="units-table-cell"></div>
		</div>
		<?php foreach ($data as $pkg => $row): ?>
		<div class="units-table-row">
			<div class="units-table-cell u-text-bold"><?= htmlspecialchars($pkg) ?></div>
			<div class="units-table-cell u-text-center"><?= htmlspecialchars($row["MAX_USERS"] ?? "") ?></div>
			<div class="units-table-cell u-text-center"><?= htmlspecialchars($row["MAX_DISK"] ?? "") ?></div>
			<div class="units-table-cell u-text-center"><?= htmlspecialchars($row["MAX_BANDWIDTH"] ?? "") ?></div>
			<div class="units-table-cell"><?= htmlspecialchars($row["ALLOWED_PACKAGES"] ?? "") ?></div>
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
