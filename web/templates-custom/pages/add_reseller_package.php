<div class="toolbar">
	<div class="toolbar-inner">
		<a class="button button-secondary button-back" href="/list/reseller-package/"><i class="fas fa-arrow-left icon-blue"></i><?= _("Back") ?></a>
	</div>
</div>

<div class="container">
	<h1 class="u-text-center u-mb20"><?= _("Add Reseller Package") ?></h1>
	<form method="post">
		<input type="hidden" name="token" value="<?= $_SESSION["token"] ?>">
		<input type="hidden" name="ok" value="1">
		<div class="u-mb10">
			<label class="form-label" for="v_package"><?= _("Package name") ?></label>
			<input class="form-control" type="text" name="v_package" id="v_package" required pattern="[a-zA-Z0-9_-]+">
		</div>
		<div class="u-mb10">
			<label class="form-label" for="v_max_users"><?= _("Max hosting users") ?></label>
			<input class="form-control" type="text" name="v_max_users" id="v_max_users" value="10" placeholder="10 or unlimited">
		</div>
		<div class="u-mb10">
			<label class="form-label" for="v_max_disk"><?= _("Max total disk (MB, pooled)") ?></label>
			<input class="form-control" type="text" name="v_max_disk" id="v_max_disk" value="10240" placeholder="10240 or unlimited">
		</div>
		<div class="u-mb10">
			<label class="form-label" for="v_max_bandwidth"><?= _("Max total bandwidth (MB/month, pooled)") ?></label>
			<input class="form-control" type="text" name="v_max_bandwidth" id="v_max_bandwidth" value="102400" placeholder="unlimited">
		</div>
		<div class="u-mb20">
			<label class="form-label"><?= _("Hosting packages resellers may assign") ?></label>
			<?php foreach ($user_packages as $upkg): ?>
			<label class="form-check">
				<input type="checkbox" name="v_allowed_packages[]" value="<?= htmlspecialchars($upkg) ?>" <?= $upkg === "default" ? "checked" : "" ?>>
				<?= htmlspecialchars($upkg) ?>
			</label>
			<?php endforeach; ?>
			<?php if (empty($user_packages)): ?>
			<p><?= _("No user packages found — create hosting packages under Server first.") ?></p>
			<?php endif; ?>
		</div>
		<button type="submit" class="button button-secondary"><?= _("Save Package") ?></button>
	</form>
</div>
