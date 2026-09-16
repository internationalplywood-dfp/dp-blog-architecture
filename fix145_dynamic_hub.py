#!/usr/bin/env python3
"""
Replace the hardcoded card grid on Blog hub Page 145 with the dynamic
[dp_guides_grid] shortcode. Keeps the branded .bi-header hero, its
stylesheet, and the .bi-body wrapper intact.

Database credentials are read directly from the environment's wp-config.php,
so no password needs to be supplied or typed.

Dry run :  python3 fix145_dynamic_hub.py --env=staging
Apply   :  python3 fix145_dynamic_hub.py --env=staging --apply
"""

import sys, os, re, datetime, difflib

try:
    import pymysql
except ImportError:
    sys.exit("pymysql missing:  apt-get install -y python3-pymysql")

WP_ROOTS = {
    "staging":    "/var/www/staging",
    "production": "/var/www/production",
}

PAGE_ID = 145
BACKUP_DIR = "/root/dp-backups"

APPLY = "--apply" in sys.argv
ENV = "staging"
for a in sys.argv[1:]:
    if a.startswith("--env="):
        ENV = a.split("=", 1)[1]
if ENV not in WP_ROOTS:
    sys.exit("--env must be staging or production")

WP_ROOT = WP_ROOTS[ENV]
CONFIG = os.path.join(WP_ROOT, "wp-config.php")
if not os.path.isfile(CONFIG):
    sys.exit(f"wp-config.php not found at {CONFIG}")


def wp_config_values(path):
    """Pull DB_* constants and $table_prefix out of wp-config.php."""
    with open(path, "r", encoding="utf-8", errors="replace") as fh:
        src = fh.read()

    out = {}
    for key in ("DB_NAME", "DB_USER", "DB_PASSWORD", "DB_HOST"):
        m = re.search(
            r"""define\(\s*['"]%s['"]\s*,\s*(['"])(.*?)\1\s*\)""" % key,
            src, re.S,
        )
        if not m:
            sys.exit(f"Could not read {key} from {path}")
        out[key] = m.group(2)

    m = re.search(r"""\$table_prefix\s*=\s*(['"])(.*?)\1""", src)
    out["prefix"] = m.group(2) if m else "wp_"
    return out


cfg = wp_config_values(CONFIG)
host, port = cfg["DB_HOST"], 3306
if ":" in host:
    host, p = host.rsplit(":", 1)
    if p.isdigit():
        port = int(p)

posts_table = cfg["prefix"] + "posts"
print(f"env={ENV}  root={WP_ROOT}  db={cfg['DB_NAME']}  table={posts_table}")

conn = pymysql.connect(host=host, port=port, user=cfg["DB_USER"],
                       password=cfg["DB_PASSWORD"], db=cfg["DB_NAME"],
                       charset="utf8mb4")
cur = conn.cursor()
cur.execute(f"SELECT post_content, post_title, post_status FROM `{posts_table}` WHERE ID=%s",
            (PAGE_ID,))
row = cur.fetchone()
if not row:
    sys.exit(f"Page {PAGE_ID} not found in {cfg['DB_NAME']}")
original, title, status = row
print(f"page {PAGE_ID}: {title!r} [{status}] — {len(original)} bytes")

# ---- Always back the original row content up to disk first ----
os.makedirs(BACKUP_DIR, exist_ok=True)
stamp = datetime.datetime.now().strftime("%Y%m%d_%H%M%S")
backup_path = f"{BACKUP_DIR}/page{PAGE_ID}_{ENV}_{stamp}.html"
with open(backup_path, "w", encoding="utf-8") as fh:
    fh.write(original)
print(f"original post_content backed up -> {backup_path}")

# ---- Locate the body wrapper holding the hardcoded cards ----
open_marker = '<div class="bi-body">'
start = original.find(open_marker)
if start == -1:
    if "[dp_guides_grid]" in original:
        sys.exit("Page already uses [dp_guides_grid] — nothing to do.")
    sys.exit('Could not find <div class="bi-body"> — aborting, no change made.')

head = original[:start]        # stylesheet + .bi-header hero, preserved as-is
tail_src = original[start:]

card_count = tail_src.count('class="bi-card"')
link_count = len(re.findall(r'href="([^"]+)"', tail_src))

new_body = (
    '<div class="bi-body">\n'
    '  <div class="bi-cnt">\n'
    '    <div class="bi-section">\n'
    '      <h2 style="color:#1B3A6B!important;">All Guides &#8212; Newest First</h2>\n'
    '      [dp_guides_grid]\n'
    '    </div>\n'
    '    <div class="bi-section">\n'
    '      <div class="bi-card" style="border-top:4px solid #1B3A6B;">\n'
    '        <h3 style="color:#1B3A6B!important;">'
    '<a href="/trade-account/" style="color:#1B3A6B!important;text-decoration:none!important;">'
    'Open a Contractor Trade Account</a></h3>\n'
    '        <p style="color:#4A5261!important;">Licensed contractors can apply for Net-30 terms, '
    'volume pricing, and a dedicated account rep.</p>\n'
    '        <a class="bi-read" href="/trade-account/" '
    'style="color:#E8590C!important;text-decoration:none!important;">Apply Now &rarr;</a>\n'
    '      </div>\n'
    '    </div>\n'
    '  </div>\n'
    '</div>\n'
)

updated = head + new_body

print(f"\nreplacing {card_count} hardcoded cards / {link_count} hardcoded links "
      f"with one [dp_guides_grid] shortcode")
print(f"content size: {len(original)} -> {len(updated)} bytes")
print("\n--- unified diff (first 50 lines) ---")
for i, line in enumerate(difflib.unified_diff(
        original.splitlines(), updated.splitlines(), "before", "after",
        lineterm="", n=1)):
    if i >= 50:
        print("... (truncated)")
        break
    print(line)

if not APPLY:
    print("\nDRY RUN — nothing written. Re-run with --apply to commit.")
    conn.close()
    sys.exit(0)

cur.execute(f"UPDATE `{posts_table}` SET post_content=%s WHERE ID=%s",
            (updated, PAGE_ID))
conn.commit()
conn.close()
print(f"\nAPPLIED to {cfg['DB_NAME']} page {PAGE_ID}.")
print(f"Now flush cache:  wp --path={WP_ROOT} --allow-root w3-total-cache flush all")
print(f"Rollback: restore post_content from {backup_path}")
