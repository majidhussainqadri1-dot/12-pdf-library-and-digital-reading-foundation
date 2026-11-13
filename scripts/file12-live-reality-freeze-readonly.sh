#!/usr/bin/env bash
# R56 File12: an authorized read-only source/deployed/DB evidence collector.
# The script does not read private PDF data, patient rows, raw logs or secrets.
set -euo pipefail
export LC_ALL=C
if [ "$#" -ne 1 ]; then
  printf 'Usage: bash scripts/file12-live-reality-freeze-readonly.sh /absolute/wordpress/root\n' >&2
  exit 64
fi
root="$1"
if [ ! -d "$root" ] || [ ! -f "$root/wp-config.php" ]; then
  printf 'WordPress filesystem root is unavailable or unverified.\n' >&2
  exit 66
fi
for bin in wp php sha256sum find sort xargs date; do
  command -v "$bin" >/dev/null 2>&1 || { printf 'Missing tool: %s\n' "$bin" >&2; exit 69; }
done
prefix="$(wp --path="$root" --skip-plugins --skip-themes config get table_prefix --type=variable)"
if ! [[ "$prefix" =~ ^[A-Za-z0-9_]+$ ]]; then
  printf 'Unsafe or unverified database prefix. No inspection attempted.\n' >&2
  exit 65
fi
opts="$prefix""options"
pattern="$(printf '^%spldr_' "$prefix")"
printf '=== File12 read-only freeze UTC %s ===\n' "$(date -u '+%Y-%m-%dT%H:%M:%SZ')"
printf 'No PDF/patient row, secret option value, encryption key or raw server log is returned.\n'
printf '\n[Installed WordPress core version; not a plugin runtime version]\n'
wp --path="$root" --skip-plugins --skip-themes core version
printf '\n[CLI PHP version; PHP-FPM runtime may differ]\n'
php -r 'echo PHP_VERSION,PHP_EOL;'
printf '\n[Active plugin paths from wp_options; configuration, not runtime parity]\n'
wp --path="$root" --skip-plugins --skip-themes db query "SELECT option_value FROM $opts WHERE option_name='active_plugins' LIMIT 1" --skip-column-names |
php -r '
  $raw=trim(stream_get_contents(STDIN));
  $plugins=@unserialize($raw,["allowed_classes"=>false]);
  if(!is_array($plugins)){echo "unverified-active-plugin-option\n";exit(0);}
  foreach($plugins as $path){
    if(is_string($path)&&preg_match("/^[A-Za-z0-9._\/-]+$/D",$path)){echo $path,"\n";}
  }
'
printf '\n[File12 physical database-version wp_option]\n'
wp --path="$root" --skip-plugins --skip-themes db query "SELECT option_value AS db_version_option FROM $opts WHERE option_name='pldr_db_version' LIMIT 1"
printf '\n[Migration/upgrade markers: option names only, never lock tokens]\n'
wp --path="$root" --skip-plugins --skip-themes db query "SELECT option_name,'PRESENT' AS state FROM $opts WHERE option_name IN ('pldr_migration_lock','pldr_migration_state','pldr_deferred_upgrade') ORDER BY option_name"
printf '\n[Physical File12 tables; approximate row counts, no row content]\n'
wp --path="$root" --skip-plugins --skip-themes db query "SELECT TABLE_NAME,ENGINE,TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME REGEXP '$pattern' ORDER BY TABLE_NAME"
printf '\n[Physical File12 columns and types]\n'
wp --path="$root" --skip-plugins --skip-themes db query "SELECT TABLE_NAME,COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME REGEXP '$pattern' ORDER BY TABLE_NAME,ORDINAL_POSITION"
printf '\n[Physical File12 index metadata]\n'
wp --path="$root" --skip-plugins --skip-themes db query "SELECT TABLE_NAME,INDEX_NAME,COLUMN_NAME,SEQ_IN_INDEX,NON_UNIQUE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME REGEXP '$pattern' ORDER BY TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX"
printf '\n[Standard deployed File12 directories and deterministic source file digests]\n'
found=0
for plugin_name in pdf-library-foundation-12 12-pdf-library-foundation; do
  plugin="$root/wp-content/plugins/$plugin_name"
  if [ ! -d "$plugin" ]; then continue; fi
  found=1
  printf 'PLUGIN_ROOT=%s\n' "$plugin_name"
  entry="$plugin/pdf-library-digital-reading.php"
  if [ -f "$entry" ]; then sha256sum "$entry"; else printf 'ENTRYPOINT_MISSING\n'; fi
  ( cd "$plugin" && find . -type f -print0 | sort -z | xargs -0 -r sha256sum | sha256sum )
done
if [ "$found" -eq 0 ]; then
  printf 'No standard File12 folder found; custom plugin location or absent deployment UNVERIFIED.\n'
fi
printf '\n[Runtime error-log existence only; actual logs require restricted manual review]\n'
if [ -f "$root/wp-content/debug.log" ]; then
  printf 'debug.log present; do NOT publish its private contents.\n'
else
  printf 'Default debug.log absent; no inference of zero runtime errors.\n'
fi
printf '\nEND: no Production changes, no DB migration, no Staging/Live parity claim.\n'
