#!/usr/bin/env python3
"""
Script 2: fix_light_theme.py
Fixes light-theme CSS issues:
1. Appends extended light-theme CSS fixes to styles.css and all HTML inline <style> blocks
2. Fixes 3 CSS selector bugs in inline blocks
"""

import os
import re
import glob

ADDITIONAL_LIGHT_CSS = """
/* ── Extended light-theme fixes ─────────────────────────────── */

/* Verbose variable aliases (about/contact/policy pages use --dark etc.) */
[data-theme="light"] {
  --dark:   #ffffff;
  --dark2:  #f5f7fa;
  --dark3:  #edf0f4;
  --dark4:  #e4e8ee;
  --light:  #1a202c;
  --white:  #111827;
  --slate:  #4a5568;
  --border: rgba(212,175,55,.38);
  --border2: rgba(0,0,0,.09);
}

/* Fix always-dark navbar on airport pages and secondary pages */
[data-theme="light"] #nav,
[data-theme="light"] #navbar {
  background: rgba(255,255,255,.97) !important;
  border-bottom-color: rgba(212,175,55,.25) !important;
  box-shadow: 0 2px 16px rgba(0,0,0,.07);
}

/* Secondary page nav elements */
[data-theme="light"] .nav-logo-text { color: #111827; }
[data-theme="light"] .nav-links a   { color: #374151; }
[data-theme="light"] .nav-links a:hover { color: #111; background: rgba(0,0,0,.05); }
[data-theme="light"] .hamburger span { background: #374151; }
[data-theme="light"] .mobile-menu {
  background: rgba(255,255,255,.99) !important;
  border-bottom-color: rgba(0,0,0,.1);
}
[data-theme="light"] .mobile-menu a { color: #374151; border-bottom-color: rgba(0,0,0,.07); }

/* Page hero on secondary pages — fix hardcoded dark gradient */
[data-theme="light"] .page-hero {
  background: linear-gradient(160deg,#f5f7fa 0%,#edf0f4 100%) !important;
  border-bottom-color: rgba(0,0,0,.1);
}

/* Secondary-page section and body text */
[data-theme="light"] .section-title { color: #111827 !important; }
[data-theme="light"] .section-sub   { color: #4a5568 !important; }
[data-theme="light"] .values-section { background: #f3f4f6; }
[data-theme="light"] .about-stat-v  { color: #111827; }

/* Contact / policy form labels */
[data-theme="light"] .form-group label,
[data-theme="light"] .lg label,
[data-theme="light"] .contact-label { color: #374151; }
[data-theme="light"] .form-group input,
[data-theme="light"] .form-group select,
[data-theme="light"] .form-group textarea {
  background: #ffffff;
  border-color: rgba(0,0,0,.2);
  color: #111111;
}
[data-theme="light"] .form-group input::placeholder,
[data-theme="light"] .form-group textarea::placeholder { color: #9ca3af; }

/* Footer alternate class names */
[data-theme="light"] .site-footer,
[data-theme="light"] .footer { background: #f5f7fa !important; border-top-color: rgba(0,0,0,.1); }
[data-theme="light"] .footer-col h5,
[data-theme="light"] .footer-col-title { color: #374151; }
[data-theme="light"] .footer-col ul li a,
[data-theme="light"] .footer-links a,
[data-theme="light"] .footer-col ul a { color: #6b7280; }
[data-theme="light"] .footer-col ul li a:hover { color: var(--gl); }
[data-theme="light"] .footer-bottom,
[data-theme="light"] .footer-copy,
[data-theme="light"] .f-copy,
[data-theme="light"] .f-copy a { color: #9ca3af; }

/* Fixed: pdbox-label and pd-label (was double-selector bug) */
[data-theme="light"] .pdbox-label,
[data-theme="light"] .pd-label    { color: #6b7280; }
[data-theme="light"] .pdbox-note  { color: #9ca3af; }
[data-theme="light"] .pd-breakdown { color: #9ca3af; }
[data-theme="light"] .pd-na       { color: #9ca3af; }

/* Booking widget step tabs */
[data-theme="light"] .bw-step-tab         { color: #9ca3af; }
[data-theme="light"] .bw-step-tab.active  { color: var(--gl); }
[data-theme="light"] .bw-step-tab.done    { color: var(--gl); }

/* Airport card sub-details */
[data-theme="light"] .ap-name     { color: #9ca3af; }
[data-theme="light"] .ap-dom-only { color: #9ca3af; }
[data-theme="light"] .ap-iata     { color: var(--gl); }
[data-theme="light"] .pbv         { color: #111827; font-weight: 700; }
[data-theme="light"] .ap-book-btn { background: rgba(212,175,55,.1); color: #111827; border-color: rgba(212,175,55,.3); }
[data-theme="light"] .td-nm       { color: #6b7280; }
[data-theme="light"] .td-code     { color: var(--gl); }
[data-theme="light"] .td-p        { color: #111827; font-weight: 700; }

/* Why-us floating card (hardcoded dark background) */
[data-theme="light"] .why-float {
  background: rgba(255,255,255,.95);
  border-color: rgba(212,175,55,.3);
  box-shadow: 0 4px 20px rgba(0,0,0,.1);
}
[data-theme="light"] .wf-title { color: #111827; }
[data-theme="light"] .wf-sub   { color: #6b7280; }

/* Trust/proof row (below hero — on white bg in light theme) */
[data-theme="light"] .hp-stats,
[data-theme="light"] .trust-strip { background: #f5f7fa; border-color: rgba(0,0,0,.07); }
[data-theme="light"] .hp-stat-v   { color: #111827; }
[data-theme="light"] .hp-stat-l   { color: #6b7280; }

/* Breadcrumb */
[data-theme="light"] .bc   { color: #6b7280; }
[data-theme="light"] .bc a { color: var(--gl); }

/* Info block (international info / iblk) */
[data-theme="light"] .iblk   { background: #f9fafb; border-color: rgba(0,0,0,.08); }

/* Policy page / terms / privacy content */
[data-theme="light"] .policy-nav { background: #f5f7fa; border-color: rgba(0,0,0,.08); }
[data-theme="light"] .policy-nav a { color: #374151; }
[data-theme="light"] .policy-nav a.active { color: var(--gl); }
[data-theme="light"] .policy-section h2,
[data-theme="light"] .policy-section h3 { color: #111827; }
[data-theme="light"] .policy-section p,
[data-theme="light"] .policy-section li  { color: #4a5568; }

/* Contact page contact cards */
[data-theme="light"] .contact-card  { background: #f9fafb; border-color: rgba(0,0,0,.08); }
[data-theme="light"] .contact-card h3,
[data-theme="light"] .contact-label-h { color: #111827; }

/* Blog page */
[data-theme="light"] .blog-card   { background: #f9fafb; border-color: rgba(0,0,0,.08); }
[data-theme="light"] .blog-card h2,
[data-theme="light"] .blog-card h3 { color: #111827; }
[data-theme="light"] .blog-card p  { color: #4a5568; }

/* Sidebar admin elements */
[data-theme="light"] .sidebar-label { color: #9ca3af; }
[data-theme="light"] .sidebar-item  { color: #374151; }
[data-theme="light"] .sidebar-item:hover { background: rgba(212,175,55,.08); }
"""

BUG_FIXES = [
    # Bug 1: Double [data-theme="light"] selector
    (
        r'\[data-theme="light"\]\s+\[data-theme="light"\]\s+\.pdbox-label\s*,\s*\.pd-label\s*{',
        '[data-theme="light"] .pdbox-label,[data-theme="light"] .pd-label{'
    ),
    # Bug 2: .cta-band applies in ALL themes
    (
        r'\[data-theme="light"\]\s+\.cta\s*,\s*\.cta-band\s*{',
        '[data-theme="light"] .cta,[data-theme="light"] .cta-band{'
    ),
    # Bug 3: .aa-search input applies in ALL themes
    (
        r'\[data-theme="light"\]\s+\.srch-wrap\s+input\s*,\s*\.aa-search\s+input\s*{',
        '[data-theme="light"] .srch-wrap input,[data-theme="light"] .aa-search input{'
    ),
]

def fix_html_css_selectors(content):
    """Fix the 3 CSS selector bugs in inline <style> blocks."""
    for pattern, replacement in BUG_FIXES:
        content = re.sub(pattern, replacement, content)
    return content

def add_css_to_file(filepath, additional_css):
    """Add ADDITIONAL_LIGHT_CSS before </style> tag."""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # First fix the selector bugs
    content = fix_html_css_selectors(content)

    # Find the last </style> tag in the file
    last_style_index = content.rfind('</style>')
    if last_style_index == -1:
        print(f"  ✗ No </style> tag found")
        return False

    # Insert before </style>
    new_content = content[:last_style_index] + additional_css + '\n' + content[last_style_index:]

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)

    return True

def process_styles_css(script_dir):
    """Add ADDITIONAL_LIGHT_CSS to styles.css."""
    styles_path = os.path.join(script_dir, 'styles.css')
    if not os.path.exists(styles_path):
        print(f"  ✗ styles.css not found")
        return False

    with open(styles_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Append at the very end
    new_content = content.rstrip() + '\n' + ADDITIONAL_LIGHT_CSS + '\n'

    with open(styles_path, 'w', encoding='utf-8') as f:
        f.write(new_content)

    print(f"  ✓ styles.css — added extended light-theme CSS")
    return True

def process_html_files(script_dir):
    """Process all HTML files: fix bugs and add extended CSS."""
    html_files = glob.glob(os.path.join(script_dir, '*.html'))
    html_files += glob.glob(os.path.join(script_dir, '**', '*.html'), recursive=True)

    # Remove duplicates
    html_files = list(set(html_files))

    updated_count = 0

    for filepath in sorted(html_files):
        if add_css_to_file(filepath, ADDITIONAL_LIGHT_CSS):
            updated_count += 1
            filename = os.path.basename(filepath)
            print(f"  ✓ {filename}")
        else:
            filename = os.path.basename(filepath)
            print(f"  ✗ {filename}")

    return updated_count

def main():
    script_dir = os.path.dirname(os.path.abspath(__file__))

    print("=" * 70)
    print("SCRIPT 2: fix_light_theme.py — Fixing light-theme CSS")
    print("=" * 70)

    print("\n► Processing styles.css...")
    process_styles_css(script_dir)

    print("\n► Processing HTML files (fixing bugs + adding extended CSS)...")
    html_count = process_html_files(script_dir)

    print("\n" + "=" * 70)
    print(f"RESULT: {html_count} HTML files updated")
    print("=" * 70)

if __name__ == "__main__":
    main()
