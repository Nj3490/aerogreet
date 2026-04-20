#!/usr/bin/env python3
"""
Script 1: fix_shared_js.py
Adds missing shared.js and airports-data.js script tags to 8 pages
that don't have them.
"""

import os
import re

# List of files that need shared.js injection
TARGET_FILES = [
    "about.html",
    "blog.html",
    "contact.html",
    "international.html",
    "privacy.html",
    "refund.html",
    "terms.html",
]

SCRIPT_TAGS = """<script src="airports-data.js"></script>
<script src="shared.js"></script>"""

def add_shared_js(filepath):
    """Add shared.js and airports-data.js before </body> if not already present."""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Check if already present
    if 'shared.js' in content or 'airports-data.js' in content:
        print(f"  ⊘ {os.path.basename(filepath)} — already has shared.js/airports-data.js")
        return False

    # Find </body> tag and insert before it
    if '</body>' not in content:
        print(f"  ✗ {os.path.basename(filepath)} — no </body> tag found")
        return False

    # Insert before </body>
    new_content = content.replace('</body>', f'{SCRIPT_TAGS}\n</body>')

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)

    print(f"  ✓ {os.path.basename(filepath)} — added shared.js and airports-data.js")
    return True

def main():
    script_dir = os.path.dirname(os.path.abspath(__file__))
    updated_count = 0

    print("=" * 70)
    print("SCRIPT 1: fix_shared_js.py — Adding missing script tags")
    print("=" * 70)

    for filename in TARGET_FILES:
        filepath = os.path.join(script_dir, filename)
        if os.path.exists(filepath):
            if add_shared_js(filepath):
                updated_count += 1
        else:
            print(f"  ✗ {filename} — file not found")

    print("=" * 70)
    print(f"RESULT: {updated_count}/{len(TARGET_FILES)} files updated")
    print("=" * 70)

if __name__ == "__main__":
    main()
