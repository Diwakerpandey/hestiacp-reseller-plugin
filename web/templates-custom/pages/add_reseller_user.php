<div class="toolbar">
	<div class="toolbar-inner">
		<a class="button button-secondary button-back" href="/list/reseller-user/<?= ($_SESSION["userContext"] ?? "") === "admin" ? "?reseller=" . urlencode($v_reseller ?? "") : "" ?>">
			<i class="fas fa-arrow-left icon-blue"></i><?= _("Back") ?>
		</a>
	</div>
</div>

<div class="container">
	<h1 class="u-text-center u-mb20"><?= _("Add Hosting User") ?></h1>
	<form method="post">
		<input type="hidden" name="token" value="<?= $_SESSION["token"] ?>">
		<input type="hidden" name="ok" value="1">
		<div class="u-mb10">
			<label class="form-label" for="v_username"><?= _("Username") ?></label>
			<input class="form-control" type="text" name="v_username" id="v_username" required>
		</div>
		<div class="u-mb10">
			<label class="form-label" for="v_password"><?= _("Password") ?></label>
			<input class="form-control" type="password" name="v_password" id="v_password" required>
		</div>
		<div class="u-mb10">
			<label class="form-label" for="v_email"><?= _("Email") ?></label>
			<input class="form-control" type="email" name="v_email" id="v_email" required>
		</div>
		<div class="u-mb10">
			<label class="form-label" for="v_name"><?= _("Contact Name") ?></label>
			<input class="form-control" type="text" name="v_name" id="v_name">
		</div>
		<div class="u-mb20">
			<label class="form-label" for="v_package"><?= _("Hosting Package") ?></label>
			<select class="form-select" name="v_package" id="v_package" required>
				<?php if (!empty($hosting_packages)): ?>
					<?php foreach ($hosting_packages as $pkg => $_row): ?>
				<option value="<?= htmlspecialchars($pkg) ?>"><?= htmlspecialchars($pkg) ?></option>
					<?php endforeach; ?>
				<?php elseif (!empty($legacy_packages)): ?>
					<?php foreach ($legacy_packages as $pkg): ?>
				<option value="<?= htmlspecialchars($pkg) ?>"><?= htmlspecialchars($pkg) ?> (<?= _("system") ?>)</option>
					<?php endforeach; ?>
				<?php else: ?>
				<option value="default">default</option>
				<?php endif; ?>
			</select>
			<?php if (empty($hosting_packages)): ?>
			<p class="u-mt10"><a href="/add/reseller-hosting-package/"><?= _("Create a hosting package first") ?></a></p>
			<?php endif; ?>
		</div>
		<button type="submit" class="button button-secondary"><?= _("Create User") ?></button>
	</form>
</div>
