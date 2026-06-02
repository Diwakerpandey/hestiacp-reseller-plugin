<?php
/**
 * Inline styles for reseller plugin pages on default Hestia theme (no custom da-* CSS).
 */

function reseller_panel_is_default_hestia(): bool {
	static $cache = null;
	if ($cache !== null) {
		return $cache;
	}
	$panel = ($_SERVER["HESTIA"] ?? "/usr/local/hestia") . "/web/templates/includes/panel.php";
	if (!is_readable($panel)) {
		$cache = true;
		return $cache;
	}
	$head = file_get_contents($panel, false, null, 0, 12000);
	$cache = is_string($head) && str_contains($head, "main-menu-list");
	return $cache;
}

function reseller_page_needs_inline_styles(string $page): bool {
	return (bool) preg_match('/^(list_|add_|edit_)reseller/', $page);
}

function reseller_print_inline_styles(): void {
	static $printed = false;
	if ($printed || !reseller_panel_is_default_hestia()) {
		return;
	}
	$printed = true;
	?>
<style id="hestia-reseller-default-theme">
/* Reseller plugin — default Hestia theme layout */
.da-main {
	max-width: 1200px;
	margin: 0 auto;
	padding: 1.5rem 1.25rem 3rem;
	box-sizing: border-box;
}
.da-page-header {
	margin-bottom: 1.75rem;
	text-align: center;
}
.da-page-title {
	margin: 0 0 0.35rem;
	font-size: 1.75rem;
	font-weight: 600;
	color: var(--color-text-heading, #e8e8e8);
}
.da-page-subtitle {
	margin: 0;
	font-size: 1rem;
	color: var(--color-text, #cdcdcd);
	opacity: 0.9;
}
.da-dashboard-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
	gap: 1.25rem;
	margin-bottom: 1.5rem;
}
.da-card {
	display: block;
	text-decoration: none;
	color: inherit;
	background: var(--alert-border-color, #212121);
	border: 1px solid #404040;
	border-radius: 8px;
	padding: 1.25rem 1.35rem;
	transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}
.da-card:hover {
	transform: translateY(-2px);
	border-color: #5a5a5a;
	box-shadow: 0 6px 16px rgba(0, 0, 0, 0.35);
	text-decoration: none;
	color: inherit;
}
.da-card-icon {
	font-size: 1.75rem;
	margin-bottom: 0.75rem;
	opacity: 0.95;
}
.da-card-blue .da-card-icon { color: var(--icon-color-blue, #0092f4); }
.da-card-green .da-card-icon { color: var(--icon-color-green, #37cf39); }
.da-card-pink .da-card-icon { color: var(--icon-color-maroon, #ff3478); }
.da-card-indigo .da-card-icon { color: var(--icon-color-purple, #c364ff); }
.da-card-title {
	margin: 0 0 0.4rem;
	font-size: 1.15rem;
	font-weight: 600;
	color: var(--color-text-heading, #e8e8e8);
}
.da-card-description {
	margin: 0 0 0.85rem;
	font-size: 0.9rem;
	line-height: 1.45;
	color: var(--color-text-link, #4fabe9);
	opacity: 0.95;
}
.da-card-stats {
	display: flex;
	flex-wrap: wrap;
	gap: 0.75rem;
}
.da-card-stat-label {
	font-size: 0.75rem;
	text-transform: uppercase;
	letter-spacing: 0.04em;
	color: #909090;
	margin-bottom: 0.15rem;
}
.da-card-stat-value {
	font-size: 1.1rem;
	font-weight: 600;
	color: var(--color-text-heading, #e8e8e8);
}
.da-quick-stats {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
	gap: 1rem;
}
.da-stat-card {
	display: flex;
	align-items: center;
	gap: 1rem;
	padding: 1rem 1.25rem;
	background: var(--alert-border-color, #212121);
	border: 1px solid #404040;
	border-radius: 8px;
}
.da-stat-icon {
	width: 2.75rem;
	height: 2.75rem;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 50%;
	font-size: 1.15rem;
	flex-shrink: 0;
}
.da-stat-icon.blue { background: rgba(0, 146, 244, 0.2); color: #4fabe9; }
.da-stat-icon.green { background: rgba(55, 207, 57, 0.2); color: #37cf39; }
.da-stat-label {
	font-size: 0.8rem;
	color: #909090;
	margin-bottom: 0.2rem;
}
.da-stat-value {
	font-size: 1.05rem;
	font-weight: 600;
	color: var(--color-text-heading, #e8e8e8);
}
.u-mt30 { margin-top: 1.75rem !important; }

/* Hosting users list extras */
.reseller-hosting-users-page .toolbar-inner {
	flex-wrap: wrap;
	gap: 0.75rem;
	align-items: center;
}
.reseller-hosting-users-search-wrap {
	flex: 1 1 220px;
	max-width: 320px;
	margin-left: auto;
}
.reseller-hosting-users-search {
	width: 100%;
	padding: 0.45rem 0.75rem;
	font-size: 0.95rem;
	border: 1px solid #505050;
	border-radius: 4px;
	background: #333;
	color: var(--color-text, #cdcdcd);
	box-sizing: border-box;
}
.reseller-hosting-users-search:focus {
	outline: none;
	border-color: var(--icon-color-blue, #0092f4);
}
.reseller-user-actions {
	display: flex;
	flex-wrap: wrap;
	gap: 0.35rem;
	justify-content: center;
	align-items: center;
}
.reseller-user-action {
	display: inline-flex;
	align-items: center;
	gap: 0.35rem;
	padding: 0.35rem 0.6rem;
	font-size: 0.8rem;
	border-radius: 4px;
	border: 1px solid #505050;
	background: #383838;
	color: var(--color-text, #cdcdcd);
	text-decoration: none;
	cursor: pointer;
	white-space: nowrap;
}
.reseller-user-action:hover {
	background: #454545;
	color: #fff;
	text-decoration: none;
}
.reseller-user-action--delete {
	border-color: #6a3030;
	color: #f0a0a0;
}
.reseller-user-action-form {
	display: inline;
	margin: 0;
}
.reseller-user-status {
	display: inline-block;
	padding: 0.2rem 0.5rem;
	border-radius: 4px;
	font-size: 0.8rem;
	font-weight: 600;
}
.reseller-user-status--active {
	background: rgba(55, 207, 57, 0.15);
	color: #37cf39;
}
.reseller-user-status--suspended {
	background: rgba(209, 53, 53, 0.2);
	color: #e88;
}
.reseller-hosting-users-table .units-table-cell {
	vertical-align: middle;
}
@media (max-width: 768px) {
	.da-dashboard-grid {
		grid-template-columns: 1fr;
	}
	.da-quick-stats {
		grid-template-columns: 1fr;
	}
	.reseller-hosting-users-search-wrap {
		max-width: none;
		width: 100%;
		margin-left: 0;
	}
}
</style>
	<?php
}
