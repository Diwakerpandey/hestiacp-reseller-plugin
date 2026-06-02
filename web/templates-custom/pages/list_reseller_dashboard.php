<div class="container">
<main class="da-main">
	<div class="da-page-header">
		<h1 class="da-page-title"><?= _("Reseller Dashboard") ?></h1>
		<p class="da-page-subtitle"><?= _("Welcome") ?>, <strong><?= htmlspecialchars($_SESSION["user"] ?? "") ?></strong></p>
	</div>

	<div class="da-dashboard-grid">
		<a href="/list/reseller-user/" class="da-card da-card-blue">
			<div class="da-card-icon"><i class="fas fa-users"></i></div>
			<h3 class="da-card-title"><?= _("Hosting Users") ?></h3>
			<p class="da-card-description"><?= _("Create and manage your customer accounts") ?></p>
			<div class="da-card-stats">
				<div class="da-card-stat">
					<div class="da-card-stat-label"><?= _("Active") ?></div>
					<div class="da-card-stat-value"><?= (int)($user_count ?? 0) ?> / <?= htmlspecialchars($stats["MAX_USERS"] ?? "∞") ?></div>
				</div>
			</div>
		</a>

		<a href="/list/reseller-hosting-package/" class="da-card da-card-green">
			<div class="da-card-icon"><i class="fas fa-box"></i></div>
			<h3 class="da-card-title"><?= _("Hosting Packages") ?></h3>
			<p class="da-card-description"><?= _("Define plans for your customers") ?></p>
			<div class="da-card-stats">
				<div class="da-card-stat">
					<div class="da-card-stat-label"><?= _("Plans") ?></div>
					<div class="da-card-stat-value"><?= (int)($pkg_count ?? 0) ?></div>
				</div>
			</div>
		</a>

		<a href="/add/reseller-user/" class="da-card da-card-pink">
			<div class="da-card-icon"><i class="fas fa-user-plus"></i></div>
			<h3 class="da-card-title"><?= _("Add Customer") ?></h3>
			<p class="da-card-description"><?= _("Create a new hosting account") ?></p>
		</a>

		<a href="/list/reseller-account/" class="da-card da-card-indigo">
			<div class="da-card-icon"><i class="fas fa-user-gear"></i></div>
			<h3 class="da-card-title"><?= _("My Account") ?></h3>
			<p class="da-card-description"><?= _("Password, email, and profile settings") ?></p>
		</a>
	</div>

	<div class="da-quick-stats u-mt30">
		<div class="da-stat-card">
			<div class="da-stat-icon blue"><i class="fas fa-hard-drive"></i></div>
			<div class="da-stat-content">
				<div class="da-stat-label"><?= _("Pooled disk (MB)") ?></div>
				<div class="da-stat-value">
					<?= htmlspecialchars($stats["R_DISK"] ?? "0") ?> / <?= htmlspecialchars($stats["MAX_DISK"] ?? "∞") ?>
				</div>
			</div>
		</div>
		<div class="da-stat-card">
			<div class="da-stat-icon green"><i class="fas fa-right-left"></i></div>
			<div class="da-stat-content">
				<div class="da-stat-label"><?= _("Pooled bandwidth (MB)") ?></div>
				<div class="da-stat-value">
					<?= htmlspecialchars($stats["R_BANDWIDTH"] ?? "0") ?> / <?= htmlspecialchars($stats["MAX_BANDWIDTH"] ?? "∞") ?>
				</div>
			</div>
		</div>
	</div>
</main>
</div>
