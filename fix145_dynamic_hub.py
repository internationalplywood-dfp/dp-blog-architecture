#!/usr/bin/env python3
"""
Replace the hardcoded 12-card grid on Blog hub Page 145 with the dynamic
[dp_guides_grid] shortcode. Keeps the branded .bi-header hero and the
.bi-body wrapper untouched.

Dry run  :  python3 /tmp/fix145_dynamic_hub.py
Apply    :  python3 /tmp/fix145_dynamic_hub.py --apply

Environment is selected by --env (default production).
"""

import sys, os, re, datetime, difflib

try:
    import pymysql
except ImportError:
    sys.exit("pymysql missing:  apt-get install -y python3-pymysql")

ENVS = {
    "production": dict(user="wp_production_user", db="wp_production"),
    "staging":    dict(user="wp_staging_user",    db="wp_staging"),
}

PAGE_ID = 145
BACKUP_DIR = "/root/dp-backups"

APPLY = "--apply" in sys.argv
ENV = "staging"
for a in sys.argv[1:]:
    if a.startswith("--env="):
        ENV = a.split("=", 1)[1]
if ENV not in ENVS:
    sys.exit("--env must be production or staging")

PASSWORD = os.environ.get("DP_DB_PASS")
if not PASSWORD:
    sys.exit("Set DP_DB_PASS in the environment before running.")

cfg = ENVS[ENV]
conn = pymysql.connect(host="localhost", user=cfg["user"], password=PASSWORD,
                       db=cfg["db"], charset="utf8mb4")
cur = conn.cursor()
cur.execute("SELECT post_content FROM wp_posts WHERE ID=%s", (PAGE_ID,))
row = cur.fetchone()
if not row:
    sys.exit(f"Page {PAGE_ID} not found in {cfg['db']}")
original = row[0]

# ---- Back up the original row content to disk, always ----
os.makedirs(BACKUP_DIR, exist_ok=True)
stamp = datetime.datetime.now().strftime("%Y%m%d_%H%M%S")
backup_path = f"{BACKUP_DIR}/page{PAGE_ID}_{ENV}_{stamp}.html"
with open(backup_path, "w", encoding="utf-8") as fh:
    fh.write(original)
print(f"Original content backed up -> {backup_path}  ({len(original)} bytes)")

# ---- Locate the body wrapper that holds the hardcoded cards ----
open_marker = '<div class="bi-body">'
start = original.find(open_marker)
if start == -1:
    sys.exit('Could not find <div class="bi-body"> — aborting, no change made.')

head = original[:start]          # everything incl. <style> + .bi-header hero
tail_src = original[start:]

# Count what we are replacing, for the report.
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

print(f"\nReplacing {card_count} hardcoded cards / {link_count} hardcoded links "
      f"with one [dp_guides_grid] shortcode.")
print(f"Content size: {len(original)} -> {len(updated)} bytes")
print("\n--- unified diff (first 60 lines) ---")
diff = difflib.unified_diff(original.splitlines(), updated.splitlines(),
                            "before", "after", lineterm="", n=1)
for i, line in enumerate(diff):
    if i >= 60:
        print("... (truncated)")
        break
    print(line)

if not APPLY:
    print("\nDRY RUN — nothing written. Re-run with --apply to commit.")
    conn.close()
    sys.exit(0)

cur.execute("UPDATE wp_posts SET post_content=%s WHERE ID=%s", (updated, PAGE_ID))
conn.commit()
conn.close()
print(f"\nAPPLIED to {cfg['db']} page {PAGE_ID}. Now flush cache:")
print(f"  wp --path=/var/www/{'production' if ENV=='production' else 'staging'} "
      f"--allow-root w3-total-cache flush all")
print(f"Rollback: restore post_content from {backup_path}")
