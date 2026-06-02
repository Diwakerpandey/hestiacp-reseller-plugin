<div class="toolbar">
	<div class="toolbar-inner">
		<a class="button button-secondary button-back" href="/list/reseller-hosting-package/<?= ($_SESSION["userContext"] ?? "") === "admin" ? "?reseller=" . urlencode($v_reseller ?? "") : "" ?>">
			<i class="fas fa-arrow-left icon-blue"></i><?= _("Back") ?>
		</a>
	</div>
</div>

<div class="container">
	<h1 class="u-text-center u-mb20"><?= _("Add Hosting Package") ?></h1>
	<form method="post">
		<input type="hidden" name="token" value="<?= $_SESSION["token"] ?>">
		<input type="hidden" name="ok" value="1">
		<div class="u-mb10">
			<label class="form-label" for="v_package"><?= _("Package name") ?></label>
			<input class="form-control" type="text" name="v_package" id="v_package" required pattern="[a-zA-Z0-9_-]+" placeholder="starter">
		</div>
		<div class="u-mb10">
			<label class="form-label" for="v_web_domains"><?= _("Web domains") ?></label>
			<input class="form-control" type="text" name="v_web_domains" id="v_web_domains" value="5">
		</div>
		<div class="u-mb10">
			<label class="form-label" for="v_disk_quota"><?= _("Disk quota (MB per account)") ?></label>
			<input class="form-control" type="text" name="v_disk_quota" id="v_disk_quota" value="2048">
		</div>
		<div class="u-mb10">
			<label class="form-label" for="v_bandwidth"><?= _("Bandwidth (MB/month per account)") ?></label>
			<input class="form-control" type="text" name="v_bandwidth" id="v_bandwidth" value="20480">
		</div>
		<div class="u-mb10">
			<label class="form-label" for="v_databases"><?= _("Databases") ?></label>
			<input class="form-control" type="text" name="v_databases" id="v_databases" value="5">
		</div>
		<div class="u-mb10">
			<label class="form-label" for="v_mail_domains"><?= _("Mail domains") ?></label>
			<input class="form-control" type="text" name="v_mail_domains" id="v_mail_domains" value="5">
		</div>
		<div class="u-mb10">
			<label class="form-label" for="v_mail_accounts"><?= _("Mail accounts") ?></label>
			<input class="form-control" type="text" name="v_mail_accounts" id="v_mail_accounts" value="50">
		</div>
		<div class="u-mb20">
			<label class="form-label" for="v_dns_domains"><?= _("DNS zones") ?></label>
			<input class="form-control" type="text" name="v_dns_domains" id="v_dns_domains" value="5">
		</div>
		<button type="submit" class="button button-secondary"><?= _("Create Package") ?></button>
	</form>
</div>
