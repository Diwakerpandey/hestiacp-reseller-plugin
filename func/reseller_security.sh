#!/bin/bash
# Security helpers for HestiaCP Reseller Hosting plugin

# Normalize role name
reseller_security_normalize_role() {
	echo "$1" | tr '[:upper:]' '[:lower:]'
}

# Block granting admin without explicit opt-in (admin UI sets HESTIA_ALLOW_ADMIN_ROLE=yes).
reseller_security_check_role_change() {
	local target_user="$1"
	local new_role="$2"
	local current_role
	local new_norm

	new_norm=$(reseller_security_normalize_role "$new_role")
	current_role=$(reseller_security_normalize_role "$(get_user_value '$ROLE')")

	# Primary admin account must stay admin
	if [ "$target_user" = "$ROOT_USER" ] && [ "$new_norm" != "admin" ]; then
		echo "Error: cannot change role of primary administrator ($ROOT_USER)"
		return "$E_FORBIDEN"
	fi

	# Granting admin to a non-admin user requires explicit flag (panel admin UI / trusted automation)
	if [ "$new_norm" = "admin" ] || [ "$new_norm" = "administrator" ]; then
		if [ "$current_role" != "admin" ] && [ "${HESTIA_ALLOW_ADMIN_ROLE:-}" != "yes" ]; then
			echo "Error: granting administrator role is restricted (set HESTIA_ALLOW_ADMIN_ROLE=yes for trusted admin actions)"
			return "$E_FORBIDEN"
		fi
	fi

	# Revoking admin from an admin user also requires explicit flag
	if [ "$current_role" = "admin" ] && [ "$new_norm" != "admin" ]; then
		if [ "${HESTIA_ALLOW_ADMIN_ROLE:-}" != "yes" ]; then
			echo "Error: revoking administrator role is restricted"
			return "$E_FORBIDEN"
		fi
	fi

	# Resellers cannot be promoted to admin through this command
	if [ "$new_norm" = "admin" ] || [ "$new_norm" = "administrator" ]; then
		if [ "$current_role" = "reseller" ]; then
			echo "Error: cannot promote reseller to administrator"
			return "$E_FORBIDEN"
		fi
	fi

	return 0
}

# Forbid ROLE changes via generic config API (use v-change-user-role with checks).
reseller_security_forbid_role_config_key() {
	local key="$1"
	if [ "$(echo "$key" | tr '[:upper:]' '[:lower:]')" = "role" ]; then
		echo "Error: ROLE must be changed with v-change-user-role (restricted)"
		return "$E_FORBIDEN"
	fi
	return 0
}
