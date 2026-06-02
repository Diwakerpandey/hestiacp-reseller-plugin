#!/bin/bash
# Shared helpers for HestiaCP Reseller Hosting plugin

RESELLER_DATA="${HESTIA}/data/resellers"
RESELLER_PKG_DIR="${HESTIA}/data/reseller-packages"

reseller_conf_path() {
	echo "${RESELLER_DATA}/$1/reseller.conf"
}

reseller_users_list() {
	echo "${RESELLER_DATA}/$1/users.list"
}

reseller_hosting_pkg_dir() {
	echo "${RESELLER_DATA}/$1/hosting-packages"
}

reseller_sanitize_pkg_name() {
	echo "$1" | sed 's/[^a-zA-Z0-9_-]//g'
}

reseller_global_pkg_name() {
	local reseller="$1"
	local name="$2"
	name=$(reseller_sanitize_pkg_name "$name")
	echo "rs_${reseller}_${name}"
}

reseller_hosting_pkg_meta() {
	echo "$(reseller_hosting_pkg_dir "$1")/$2.meta"
}

reseller_owns_hosting_package() {
	local reseller="$1"
	local pkg="$2"
	local meta
	meta=$(reseller_hosting_pkg_meta "$reseller" "$pkg")
	[ -f "$meta" ]
}

reseller_resolve_package_for_user() {
	local reseller="$1"
	local pkg="$2"
	local global_name dir meta

	if reseller_owns_hosting_package "$reseller" "$pkg"; then
		global_name=$(grep -m1 "^GLOBAL_NAME=" "$(reseller_hosting_pkg_meta "$reseller" "$pkg")" 2>/dev/null | cut -f 2 -d \')
		if [ -z "$global_name" ]; then
			global_name=$(reseller_global_pkg_name "$reseller" "$pkg")
		fi
		if [ -f "$HESTIA/data/packages/${global_name}.pkg" ]; then
			echo "$global_name"
			return 0
		fi
		return 1
	fi

	# Legacy: admin-assigned global package names from ALLOWED_PACKAGES
	if is_package_allowed_for_reseller "$reseller" "$pkg"; then
		if [ -f "$HESTIA/data/packages/${pkg}.pkg" ]; then
			echo "$pkg"
			return 0
		fi
	fi
	return 1
}

reseller_list_hosting_package_names() {
	local reseller="$1"
	local dir
	dir=$(reseller_hosting_pkg_dir "$reseller")
	[ -d "$dir" ] || return 0
	for meta in "$dir"/*.meta; do
		[ -f "$meta" ] || continue
		basename "$meta" .meta
	done
}

is_reseller_account() {
	local u="$1"
	[ -f "$(reseller_conf_path "$u")" ] && return 0
	local role
	role=$(grep -m1 "^ROLE=" "$HESTIA/data/users/$u/user.conf" 2>/dev/null | cut -f 2 -d \')
	[ "$role" = "reseller" ]
}

get_user_reseller_owner() {
	local u="$1"
	grep -m1 "^RESELLER_OWNER=" "$HESTIA/data/users/$u/user.conf" 2>/dev/null | cut -f 2 -d \'
}

# True if $child is listed under reseller (users.list) or RESELLER_OWNER matches.
reseller_owns_child_user() {
	local reseller="$1"
	local child="$2"
	local owner list child_role

	[ -n "$reseller" ] && [ -n "$child" ] && [ "$reseller" != "$child" ] || return 1

	[ -f "$HESTIA/data/users/$child/user.conf" ] || return 1
	child_role=$(grep -m1 "^ROLE=" "$HESTIA/data/users/$child/user.conf" 2>/dev/null | cut -f 2 -d \')
	if [ "$child_role" = "admin" ] || [ "$child_role" = "reseller" ]; then
		return 1
	fi

	owner=$(get_user_reseller_owner "$child")
	if [ "$owner" = "$reseller" ]; then
		return 0
	fi

	list=$(reseller_users_list "$reseller")
	if [ -f "$list" ] && grep -qxF "$child" "$list" 2>/dev/null; then
		if [ "$owner" != "$reseller" ]; then
			reseller_link_child "$reseller" "$child"
		fi
		return 0
	fi

	return 1
}

is_reseller_child() {
	local u="$1"
	local owner
	owner=$(get_user_reseller_owner "$u")
	[ -n "$owner" ] && [ -f "$(reseller_conf_path "$owner")" ]
}

source_reseller_package() {
	local pkg="$1"
	local f="${RESELLER_PKG_DIR}/${pkg}.pkg"
	[ -f "$f" ] || return 1
	# shellcheck disable=SC1090
	source "$f"
	return 0
}

source_reseller_conf() {
	local reseller="$1"
	local f
	f=$(reseller_conf_path "$reseller")
	[ -f "$f" ] || return 1
	# shellcheck disable=SC1090
	source "$f"
	return 0
}

reseller_list_children() {
	local reseller="$1"
	local list
	list=$(reseller_users_list "$reseller")
	if [ -f "$list" ]; then
		grep -v '^[[:space:]]*$' "$list" 2>/dev/null || true
	fi
}

reseller_count_children() {
	reseller_list_children "$1" | wc -l | tr -d ' '
}

reseller_aggregate_usage() {
	local reseller="$1"
	local child disk bw
	disk=0
	bw=0
	while read -r child; do
		[ -z "$child" ] && continue
		[ -d "$HESTIA/data/users/$child" ] || continue
		local ud ub
		ud=$(grep -m1 "^U_DISK=" "$HESTIA/data/users/$child/user.conf" 2>/dev/null | cut -f 2 -d \')
		ub=$(grep -m1 "^U_BANDWIDTH=" "$HESTIA/data/users/$child/user.conf" 2>/dev/null | cut -f 2 -d \')
		ud=${ud:-0}
		ub=${ub:-0}
		disk=$((disk + ud))
		bw=$((bw + ub))
	done < <(reseller_list_children "$reseller")
	echo "$disk $bw"
}

is_package_allowed_for_reseller() {
	local reseller="$1"
	local pkg="$2"
	local allowed

	if reseller_owns_hosting_package "$reseller" "$pkg"; then
		return 0
	fi

	source_reseller_conf "$reseller" || return 1
	allowed="${ALLOWED_PACKAGES:-}"
	if [ -z "$allowed" ]; then
		return 0
	fi
	IFS=',' read -r -a arr <<< "$allowed"
	for p in "${arr[@]}"; do
		p=$(echo "$p" | xargs)
		[ "$p" = "$pkg" ] && return 0
	done
	return 1
}

reseller_check_can_add_user() {
	local reseller="$1"
	local pkg="$2"
	local max_users max_disk max_bw
	local cur_users agg_disk agg_bw

	source_reseller_conf "$reseller" || {
		echo "Error: reseller configuration not found"
		return "$E_NOTEXIST"
	}

	if ! is_package_allowed_for_reseller "$reseller" "$pkg"; then
		echo "Error: package '$pkg' is not allowed for this reseller"
		return "$E_INVALID"
	fi

	cur_users=$(reseller_count_children "$reseller")
	max_users="${MAX_USERS:-unlimited}"
	if [ "$max_users" != "unlimited" ] && [ "$cur_users" -ge "$max_users" ]; then
		echo "Error: reseller user limit reached ($cur_users / $max_users)"
		return "$E_LIMIT"
	fi

	read -r agg_disk agg_bw < <(reseller_aggregate_usage "$reseller")
	max_disk="${MAX_DISK:-unlimited}"
	max_bw="${MAX_BANDWIDTH:-unlimited}"

	if [ "$max_disk" != "unlimited" ] && [ "$agg_disk" -ge "$max_disk" ]; then
		echo "Error: reseller disk pool exhausted"
		return "$E_LIMIT"
	fi

	if [ "$max_bw" != "unlimited" ] && [ "$agg_bw" -ge "$max_bw" ]; then
		echo "Error: reseller bandwidth pool exhausted"
		return "$E_LIMIT"
	fi

	return 0
}

reseller_link_child() {
	local reseller="$1"
	local child="$2"
	local list
	list=$(reseller_users_list "$reseller")
	mkdir -p "${RESELLER_DATA}/$reseller"
	touch "$list"
	if ! grep -qxF "$child" "$list" 2>/dev/null; then
		echo "$child" >> "$list"
	fi
	if grep -q "^RESELLER_OWNER=" "$HESTIA/data/users/$child/user.conf" 2>/dev/null; then
		sed -i "s/^RESELLER_OWNER=.*/RESELLER_OWNER='$reseller'/" "$HESTIA/data/users/$child/user.conf"
	else
		echo "RESELLER_OWNER='$reseller'" >> "$HESTIA/data/users/$child/user.conf"
	fi
}

reseller_unlink_child() {
	local reseller="$1"
	local child="$2"
	local list
	list=$(reseller_users_list "$reseller")
	[ -f "$list" ] && sed -i "/^${child}$/d" "$list"
	if [ -f "$HESTIA/data/users/$child/user.conf" ]; then
		sed -i '/^RESELLER_OWNER=/d' "$HESTIA/data/users/$child/user.conf"
	fi
}

reseller_update_counter() {
	local reseller="$1"
	local key="$2"
	local val="$3"
	local conf
	conf=$(reseller_conf_path "$reseller")
	[ -f "$conf" ] || return 1
	if grep -q "^${key}=" "$conf"; then
		sed -i "s/^${key}=.*/${key}='${val}'/" "$conf"
	else
		echo "${key}='${val}'" >> "$conf"
	fi
}

reseller_sync_counters() {
	local reseller="$1"
	local count disk bw
	count=$(reseller_count_children "$reseller")
	read -r disk bw < <(reseller_aggregate_usage "$reseller")
	reseller_update_counter "$reseller" "R_USERS" "$count"
	reseller_update_counter "$reseller" "R_DISK" "$disk"
	reseller_update_counter "$reseller" "R_BANDWIDTH" "$bw"
}
