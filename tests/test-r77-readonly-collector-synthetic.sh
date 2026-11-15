#!/usr/bin/env bash
# R77 disposable-only execution: synthetic WP-CLI response fixture.
# NO real Hostinger connection, DB dump, PDF file, credentials or secrets.
set -euo pipefail
tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT
mkdir -p "$tmp/bin" "$tmp/site/wp-content/plugins/pdf-library-foundation-12"
: > "$tmp/site/wp-config.php"
cat > "$tmp/site/wp-content/plugins/pdf-library-foundation-12/pdf-library-digital-reading.php" <<'PHP'
<?php
/*
Plugin Name: File12 Synthetic Offline Reader
Version: 1.1.0-rc.51
*/
PHP
cat > "$tmp/bin/wp" <<'STUB'
#!/usr/bin/env bash
set -euo pipefail
printf '%s\n' "$*" >> "$WP_TEST_LOG"
args="$*"
if [[ "$args" == *'config get table_prefix'* ]];then
  printf 'wp_\n'
elif [[ "$args" == *'core version'* ]];then
  printf '7.1.4\n'
elif [[ "$args" == *"option_name='active_plugins'"* ]];then
  p='pdf-library-foundation-12/pdf-library-digital-reading.php'
  len="$(printf '%s' "$p" | wc -c)"
  printf 'a:1:{i:0;s:%d:"%s";}\n' "$len" "$p"
elif [[ "$args" == *'AS version_or_revision'* ]];then
  printf 'option_name\tversion_or_revision\npldr_db_version\t1.1.0\npldr_future_db_version\t1.1.0\npldr_future_schema_revision\t2026-08-13-r20-17\n'
elif [[ "$args" == *"'PRESENT' AS state"* ]];then
  printf 'option_name\tstate\npldr_future_migration_lock\tPRESENT\n'
elif [[ "$args" == *'information_schema.TABLES'* ]];then
  printf 'TABLE_NAME\tENGINE\tTABLE_ROWS\nwp_pldr_documents\tInnoDB\t0\n'
elif [[ "$args" == *'information_schema.COLUMNS'* ]];then
  printf 'TABLE_NAME\tCOLUMN_NAME\tCOLUMN_TYPE\tIS_NULLABLE\nwp_pldr_documents\tid\tbigint\tNO\n'
elif [[ "$args" == *'information_schema.STATISTICS'* ]];then
  printf 'TABLE_NAME\tINDEX_NAME\tCOLUMN_NAME\nwp_pldr_documents\tPRIMARY\tid\n'
else
  printf 'Unexpected synthetic WP command (test isolation failure)\n' >&2
  exit 91
fi
STUB
chmod 0700 "$tmp/bin/wp"
export WP_TEST_LOG="$tmp/commands.log"
: > "$WP_TEST_LOG"
PATH="$tmp/bin:$PATH" bash scripts/file12-live-reality-freeze-readonly.sh "$tmp/site" > "$tmp/report.txt"
grep -Fq 'PLUGIN_HEADER_VERSION=1.1.0-rc.51' "$tmp/report.txt"
grep -Fq 'pldr_future_schema_revision' "$tmp/report.txt"
grep -Fq 'pldr_future_migration_lock' "$tmp/report.txt"
grep -Fq 'PLUGIN_ROOT=pdf-library-foundation-12' "$tmp/report.txt"
for key in pldr_db_version pldr_contract_version pldr_future_db_version pldr_future_schema_revision pldr_schema_corrections_revision pldr_schema_error pldr_future_schema_error pldr_legacy_migration_state;do
  grep -Fq "$key" "$WP_TEST_LOG" || { echo "Missing $key in actual synthetic SQL queries" >&2;exit 1; }
done
if grep -Eiq '(^|[[:space:]])(UPDATE|DELETE|INSERT|DROP|TRUNCATE)[[:space:]]' "$WP_TEST_LOG";then
  echo 'Unauthorized SQL write in synthetic read-only freeze commands' >&2;exit 1
fi
echo 'R77 synthetic WP-CLI read-only schema freeze execution PASS; zero actual Hostinger calls.'
