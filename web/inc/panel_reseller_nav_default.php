<?php
/**
 * Reseller navigation for default Hestia theme (main-menu).
 */
?>
<!-- HESTIA_RESELLER_LOCKDOWN_NAV -->
<li class="main-menu-item">
	<a class="main-menu-item-link <?php if ($TAB === "RESELLER_DASH") {
		echo "active";
	} ?>" href="/list/reseller-dashboard/">
		<p class="main-menu-item-label"><?= _("DASHBOARD") ?><i class="fas fa-gauge-high"></i></p>
	</a>
</li>
<li class="main-menu-item">
	<a class="main-menu-item-link <?php if ($TAB === "RESELLER_USER") {
		echo "active";
	} ?>" href="/list/reseller-user/">
		<p class="main-menu-item-label"><?= _("USERS") ?><i class="fas fa-users"></i></p>
	</a>
</li>
<li class="main-menu-item">
	<a class="main-menu-item-link <?php if ($TAB === "RESELLER_PKG") {
		echo "active";
	} ?>" href="/list/reseller-hosting-package/">
		<p class="main-menu-item-label"><?= _("PACKAGES") ?><i class="fas fa-box"></i></p>
	</a>
</li>
<li class="main-menu-item">
	<a class="main-menu-item-link <?php if ($TAB === "RESELLER_ACCOUNT") {
		echo "active";
	} ?>" href="/list/reseller-account/">
		<p class="main-menu-item-label"><?= _("ACCOUNT") ?><i class="fas fa-id-card"></i></p>
	</a>
</li>
