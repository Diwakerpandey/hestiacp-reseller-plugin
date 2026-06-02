<?php
$back = "/list/reseller-user/";
if (($_SESSION["userContext"] ?? "") === "admin" && !empty($v_reseller)) {
	$back .= "?reseller=" . urlencode($v_reseller);
}
?>
<div class="toolbar">
	<div class="toolbar-inner">
		<a class="button button-secondary button-back" href="<?= htmlspecialchars($back) ?>">
			<i class="fas fa-arrow-left icon-blue"></i><?= _("Back") ?>
		</a>
	</div>
</div>

<div class="container">
	<h1 class="u-text-center u-mb20"><?= _("Edit Hosting User") ?>: <?= htmlspecialchars($v_child ?? "") ?></h1>

	<form method="post" action="/list/reseller-user/?edit=<?= urlencode($v_child ?? "") ?><?= (($_SESSION["userContext"] ?? "") === "admin" && !empty($v_reseller)) ? "&reseller=" . urlencode($v_reseller) : "" ?>">
		<input type="hidden" name="token" value="<?= $_SESSION["token"] ?>">
		<input type="hidden" name="save" value="1">
		<input type="hidden" name="v_edit_user" value="<?= htmlspecialchars($v_child ?? "") ?>">

		<div class="u-mb10">
			<label class="form-label" for="v_package"><?= _("Hosting Package") ?></label>
			<select class="form-select" name="v_package" id="v_package" required>
				<?php
				$pkg_selected = $v_package_select ?? ($v_package ?? "");
				$listed = [];
				if (!empty($hosting_packages)):
					foreach ($hosting_packages as $pkg => $_row):
						$listed[$pkg] = true;
				?>
				<option value="<?= htmlspecialchars($pkg) ?>"<?= $pkg_selected === $pkg ? " selected" : "" ?>><?= htmlspecialchars($pkg) ?></option>
				<?php
					endforeach;
				endif;
				if (!empty($legacy_packages)):
					foreach ($legacy_packages as $pkg):
						if (!empty($listed[$pkg])) {
							continue;
						}
						$listed[$pkg] = true;
				?>
				<option value="<?= htmlspecialchars($pkg) ?>"<?= $pkg_selected === $pkg ? " selected" : "" ?>><?= htmlspecialchars($pkg) ?> (<?= _("system") ?>)</option>
				<?php
					endforeach;
				endif;
				if (empty($listed)):
					$listed["default"] = true;
				?>
				<option value="default"<?= $pkg_selected === "default" ? " selected" : "" ?>>default</option>
				<?php endif; ?>
				<?php if ($pkg_selected !== "" && empty($listed[$pkg_selected])): ?>
				<option value="<?= htmlspecialchars($pkg_selected) ?>" selected><?= htmlspecialchars($pkg_selected) ?> (<?= _("current") ?>)</option>
				<?php endif; ?>
			</select>
			<?php if (empty($hosting_packages) && empty($legacy_packages)): ?>
			<p class="u-mt10"><a href="/add/reseller-hosting-package/"><?= _("Create a hosting package first") ?></a></p>
			<?php endif; ?>
		</div>

		<div class="u-mb10">
			<label class="form-label" for="v_name"><?= _("Contact name") ?></label>
			<input class="form-control" type="text" name="v_name" id="v_name" value="<?= htmlspecialchars($v_name ?? "") ?>">
		</div>

		<div class="u-mb10">
			<label class="form-label" for="v_email"><?= _("Email") ?></label>
			<input class="form-control" type="email" name="v_email" id="v_email" value="<?= htmlspecialchars($v_email ?? "") ?>" required>
		</div>

		<div class="u-mb20">
			<label class="form-label" for="v_password"><?= _("New password") ?></label>
			<input class="form-control" type="password" name="v_password" id="v_password" autocomplete="new-password">
			<p class="u-mt10 u-text-small"><?= _("Leave blank to keep the current password.") ?></p>
		</div>

		<button type="submit" class="button button-secondary"><?= _("Save") ?></button>
	</form>
</div>
