<div class="toolbar">
	<div class="toolbar-inner">
		<a class="button button-secondary button-back" href="/list/reseller/"><i class="fas fa-arrow-left icon-blue"></i><?= _("Back") ?></a>
	</div>
</div>

<div class="container">
	<h1 class="u-text-center u-mb20"><?= _("Add Reseller") ?></h1>
	<?php if (empty($packages)): ?>
		<p class="u-text-center"><?= _("Create a reseller package first.") ?> <a href="/add/reseller-package/"><?= _("Add package") ?></a></p>
	<?php else: ?>
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
			<label class="form-label" for="v_reseller_package"><?= _("Reseller Package") ?></label>
			<select class="form-select" name="v_reseller_package" id="v_reseller_package" required>
				<?php foreach ($packages as $pkg => $_row): ?>
				<option value="<?= htmlspecialchars($pkg) ?>"><?= htmlspecialchars($pkg) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<button type="submit" class="button button-secondary"><?= _("Create Reseller") ?></button>
	</form>
	<?php endif; ?>
</div>
