<?php

$stats = $stats ?? [];

$is_reseller = function_exists("reseller_session_is_reseller")

	? reseller_session_is_reseller()

	: (($_SESSION["userContext"] ?? "") === "reseller");

$add_url = "/add/reseller-user/" . ($is_reseller ? "" : "?reseller=" . urlencode($v_reseller ?? ""));

$back_url = $is_reseller ? "/list/reseller-dashboard/" : "/list/reseller/";

?>

<div class="reseller-hosting-users-page">

<div class="toolbar">

	<div class="toolbar-inner">

		<div class="toolbar-buttons">

			<?php if (!$is_reseller): ?>

			<a class="button button-secondary button-back" href="<?= htmlspecialchars($back_url) ?>">

				<i class="fas fa-arrow-left icon-blue"></i><?= _("Back") ?>

			</a>

			<?php endif; ?>

			<a href="/list/reseller-hosting-package/<?= $is_reseller ? '' : '?reseller=' . urlencode($v_reseller ?? '') ?>" class="button button-secondary">

				<i class="fas fa-box icon-orange"></i><?= _("Packages") ?>

			</a>

			<a href="<?= htmlspecialchars($add_url) ?>" class="button button-secondary">

				<i class="fas fa-circle-plus icon-green"></i><?= _("Add Hosting User") ?>

			</a>

			<?php if ($is_reseller): ?>

			<a href="/list/reseller-account/" class="button button-secondary">

				<i class="fas fa-user-gear icon-blue"></i><?= _("My Account") ?>

			</a>

			<?php endif; ?>

		</div>

		<?php if (!empty($data)): ?>

		<div class="reseller-hosting-users-search-wrap">

			<label class="u-hidden-visually" for="reseller-users-search"><?= _("Search hosting users") ?></label>

			<input

				type="search"

				id="reseller-users-search"

				class="reseller-hosting-users-search"

				placeholder="<?= _("Search by username, package, status…") ?>"

				autocomplete="off"

			>

		</div>

		<?php endif; ?>

	</div>

</div>



<div class="container">

	<h1 class="u-text-center u-mb20">

		<?= $is_reseller ? _("My Hosting Users") : sprintf(_("Hosting Users — %s"), htmlspecialchars($v_reseller ?? "")) ?>

	</h1>



	<?php if (!empty($stats)): ?>

	<div class="u-mb30 u-text-center">

		<p>

			<strong><?= _("Usage") ?>:</strong>

			<?= htmlspecialchars($stats["R_USERS"] ?? "0") ?> / <?= htmlspecialchars($stats["MAX_USERS"] ?? "∞") ?> <?= _("users") ?>,

			<?= htmlspecialchars($stats["R_DISK"] ?? "0") ?> / <?= htmlspecialchars($stats["MAX_DISK"] ?? "∞") ?> MB <?= _("disk") ?>,

			<?= htmlspecialchars($stats["R_BANDWIDTH"] ?? "0") ?> / <?= htmlspecialchars($stats["MAX_BANDWIDTH"] ?? "∞") ?> MB <?= _("bandwidth") ?>

		</p>

	</div>

	<?php endif; ?>



	<?php if (empty($data)): ?>

		<p class="u-text-center"><?= _("No hosting users under this reseller.") ?></p>

	<?php else: ?>

	<p id="reseller-users-search-empty" class="reseller-hosting-users-search-empty u-text-center u-hidden" role="status">

		<?= _("No users match your search.") ?>

	</p>

	<div class="units-table reseller-hosting-users-table" id="reseller-hosting-users-table">

		<div class="units-table-header">

			<div class="units-table-cell"><?= _("Username") ?></div>

			<div class="units-table-cell"><?= _("Package") ?></div>

			<div class="units-table-cell u-text-center"><?= _("Disk") ?></div>

			<div class="units-table-cell u-text-center"><?= _("Bandwidth") ?></div>

			<div class="units-table-cell u-text-center"><?= _("Domains") ?></div>

			<div class="units-table-cell u-text-center"><?= _("Status") ?></div>

			<div class="units-table-cell u-text-center"><?= _("Actions") ?></div>

		</div>

		<?php foreach ($data as $uname => $row):

			$search_blob = strtolower(

				$uname . ' '

				. ($row["PACKAGE"] ?? '') . ' '

				. ($row["SUSPENDED"] ?? '') . ' '

				. ($row["U_DISK"] ?? '') . ' '

				. ($row["U_BANDWIDTH"] ?? '') . ' '

				. ($row["U_WEB_DOMAINS"] ?? '')

			);

			$edit_href = "/list/reseller-user/?edit=" . urlencode($uname);

			if (!$is_reseller && !empty($v_reseller)) {

				$edit_href .= "&reseller=" . urlencode($v_reseller);

			}

			$manage_href = $is_reseller

				? "/list/reseller-user/?loginas=" . urlencode($uname) . "&token=" . urlencode($_SESSION["token"])

				: "/login/?loginas=" . urlencode($uname) . "&token=" . urlencode($_SESSION["token"]);

		?>

		<div

			class="units-table-row reseller-user-row"

			data-username="<?= htmlspecialchars($uname, ENT_QUOTES, "UTF-8") ?>"

			data-search="<?= htmlspecialchars($search_blob, ENT_QUOTES, "UTF-8") ?>"

		>

			<div class="units-table-cell u-text-bold">

				<?php if ($is_reseller): ?>

					<span class="reseller-user-row__name"><?= htmlspecialchars($uname) ?></span>

				<?php else: ?>

					<a href="<?= htmlspecialchars($manage_href) ?>"><?= htmlspecialchars($uname) ?></a>

				<?php endif; ?>

			</div>

			<div class="units-table-cell"><?= htmlspecialchars($row["PACKAGE"] ?? "") ?></div>

			<div class="units-table-cell u-text-center"><?= htmlspecialchars($row["U_DISK"] ?? "0") ?></div>

			<div class="units-table-cell u-text-center"><?= htmlspecialchars($row["U_BANDWIDTH"] ?? "0") ?></div>

			<div class="units-table-cell u-text-center"><?= htmlspecialchars($row["U_WEB_DOMAINS"] ?? "0") ?></div>

			<div class="units-table-cell u-text-center">

				<span class="reseller-user-status reseller-user-status--<?= ($row["SUSPENDED"] ?? "no") === "yes" ? "suspended" : "active" ?>">

					<?= htmlspecialchars($row["SUSPENDED"] ?? "no") ?>

				</span>

			</div>

			<div class="units-table-cell">

				<div class="reseller-user-actions">

					<a

						class="reseller-user-action reseller-user-action--edit"

						href="<?= htmlspecialchars($edit_href) ?>"

						title="<?= _("Edit") ?>"

					>

						<i class="fas fa-pen-to-square" aria-hidden="true"></i>

						<span><?= _("Edit") ?></span>

					</a>

					<a

						class="reseller-user-action reseller-user-action--manage"

						href="<?= htmlspecialchars($manage_href) ?>"

						title="<?= _("Manage") ?>"

					>

						<i class="fas fa-arrow-right-to-bracket" aria-hidden="true"></i>

						<span><?= _("Manage") ?></span>

					</a>

					<form

						method="post"

						class="reseller-user-action-form"

						onsubmit="return confirm('<?= htmlspecialchars(_("Delete this user and all data?"), ENT_QUOTES, "UTF-8") ?>');"

					>

						<input type="hidden" name="token" value="<?= $_SESSION["token"] ?>">

						<input type="hidden" name="action" value="delete">

						<input type="hidden" name="v_username" value="<?= htmlspecialchars($uname, ENT_QUOTES, "UTF-8") ?>">

						<button type="submit" class="reseller-user-action reseller-user-action--delete" title="<?= _("Delete") ?>">

							<i class="fas fa-trash-can" aria-hidden="true"></i>

							<span><?= _("Delete") ?></span>

						</button>

					</form>

				</div>

			</div>

		</div>

		<?php endforeach; ?>

	</div>

	<?php endif; ?>

</div>

</div>



<?php if (!empty($data)): ?>

<script>

(function () {

	var input = document.getElementById("reseller-users-search");

	var table = document.getElementById("reseller-hosting-users-table");

	var emptyMsg = document.getElementById("reseller-users-search-empty");

	if (!input || !table) return;



	function filterRows() {

		var q = (input.value || "").trim().toLowerCase();

		var rows = table.querySelectorAll(".reseller-user-row");

		var visible = 0;

		rows.forEach(function (row) {

			var hay = row.getAttribute("data-search") || "";

			var show = q === "" || hay.indexOf(q) !== -1;

			row.style.display = show ? "" : "none";

			if (show) visible++;

		});

		if (emptyMsg) {

			emptyMsg.classList.toggle("u-hidden", visible > 0 || q === "");

		}

	}



	input.addEventListener("input", filterRows);

	input.addEventListener("search", filterRows);

})();

</script>

<?php endif; ?>

