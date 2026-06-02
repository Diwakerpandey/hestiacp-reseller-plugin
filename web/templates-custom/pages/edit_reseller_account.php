<div class="toolbar">
	<div class="toolbar-inner">
		<a class="button button-secondary button-back" href="/list/reseller-dashboard/">
			<i class="fas fa-arrow-left icon-blue"></i><?= _("Back") ?>
		</a>
	</div>
</div>

<div class="container">
	<h1 class="u-text-center u-mb20"><?= _("My Account") ?></h1>
	<p class="u-text-center u-mb30 u-text-small">
		<?= _("Update your reseller login password, email, and display name.") ?>
	</p>

	<form method="post" class="u-mb40" style="max-width: 32rem; margin: 0 auto;">
		<input type="hidden" name="token" value="<?= $_SESSION["token"] ?>">
		<input type="hidden" name="save" value="1">

		<div class="u-mb10">
			<label class="form-label"><?= _("Username") ?></label>
			<input class="form-control" type="text" value="<?= htmlspecialchars($_SESSION["user"] ?? "") ?>" disabled>
		</div>

		<div class="u-mb10">
			<label class="form-label"><?= _("Package") ?></label>
			<input class="form-control" type="text" value="<?= htmlspecialchars($v_package ?? "") ?>" disabled>
		</div>

		<div class="u-mb10">
			<label class="form-label" for="v_name"><?= _("Name") ?></label>
			<input class="form-control" type="text" name="v_name" id="v_name" value="<?= htmlspecialchars($v_name ?? "") ?>">
		</div>

		<div class="u-mb10">
			<label class="form-label" for="v_email"><?= _("Email") ?></label>
			<input class="form-control" type="email" name="v_email" id="v_email" value="<?= htmlspecialchars($v_email ?? "") ?>" required>
		</div>

		<div class="u-mb20">
			<label class="form-label" for="v_password"><?= _("New password") ?></label>
			<input class="form-control" type="password" name="v_password" id="v_password" autocomplete="new-password">
			<p class="u-mt10 u-text-small"><?= _("Leave blank to keep your current password.") ?></p>
		</div>

		<button type="submit" class="button button-secondary u-width-full">
			<i class="fas fa-floppy-disk icon-green"></i><?= _("Save") ?>
		</button>
	</form>
</div>
