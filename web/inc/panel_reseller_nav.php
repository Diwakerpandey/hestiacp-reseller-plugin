<?php
/**
 * Navigation for reseller panel sessions (included from panel.php).
 */
?>
<!-- HESTIA_RESELLER_LOCKDOWN_NAV -->
<ul class="da-nav__list u-hide-tablet">
	<li class="da-nav__item">
		<a class="da-nav__link <?= ($TAB === 'RESELLER_DASH' ? 'active' : '') ?>" href="/list/reseller-dashboard/" <?= ($TAB === 'RESELLER_DASH' ? 'aria-current="page"' : '') ?>>
			<span class="da-nav__label"><?= _("Dashboard") ?></span>
		</a>
	</li>
	<li class="da-nav__item">
		<a class="da-nav__link <?= ($TAB === 'RESELLER_USER' ? 'active' : '') ?>" href="/list/reseller-user/" <?= ($TAB === 'RESELLER_USER' ? 'aria-current="page"' : '') ?>>
			<span class="da-nav__label"><?= _("Hosting Users") ?></span>
		</a>
	</li>
	<li class="da-nav__item">
		<a class="da-nav__link <?= ($TAB === 'RESELLER_PKG' ? 'active' : '') ?>" href="/list/reseller-hosting-package/" <?= ($TAB === 'RESELLER_PKG' ? 'aria-current="page"' : '') ?>>
			<span class="da-nav__label"><?= _("Packages") ?></span>
		</a>
	</li>
	<li class="da-nav__item">
		<a class="da-nav__link <?= ($TAB === 'RESELLER_ACCOUNT' ? 'active' : '') ?>" href="/list/reseller-account/" <?= ($TAB === 'RESELLER_ACCOUNT' ? 'aria-current="page"' : '') ?>>
			<span class="da-nav__label"><?= _("Account") ?></span>
		</a>
	</li>
</ul>
<ul id="mobile-nav-list" x-cloak x-show="open" x-transition.opacity x-transition.duration.150ms class="da-nav__list da-nav__list--mobile u-hide-desktop" aria-label="Mobile menu">
	<li class="da-nav__item"><a class="da-nav__link" href="/list/reseller-dashboard/"><span class="da-nav__label"><?= _("Dashboard") ?></span></a></li>
	<li class="da-nav__item"><a class="da-nav__link" href="/list/reseller-user/"><span class="da-nav__label"><?= _("Hosting Users") ?></span></a></li>
	<li class="da-nav__item"><a class="da-nav__link" href="/list/reseller-hosting-package/"><span class="da-nav__label"><?= _("Packages") ?></span></a></li>
	<li class="da-nav__item"><a class="da-nav__link" href="/list/reseller-account/"><span class="da-nav__label"><?= _("Account") ?></span></a></li>
</ul>
