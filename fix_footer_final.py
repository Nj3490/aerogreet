#!/usr/bin/env python3
# Final attempt: Read entire files, extract parts, reconstruct with correct footer

# 1. Read index.html and extract footer
with open('index.html', 'r', encoding='utf-8') as f:
    index_content = f.read()
    # Find footer
    footer_start = index_content.find('<footer>')
    footer_end = index_content.find('</footer>') + len('</footer>')
    if footer_start >= 0 and footer_end > footer_start:
        correct_footer = index_content[footer_start:footer_end]
    else:
        exit("Footer not found in index.html")

# 2. Read about.html
with open('about.html', 'r', encoding='utf-8') as f:
    about_content = f.read()

# 3. Split about.html at footer boundaries
about_footer_start = about_content.find('<footer>')
about_footer_end = about_content.find('</footer>')

if about_footer_start >= 0 and about_footer_end > about_footer_start:
    # Reconstruct: before footer + correct footer + after footer
    fixed_content = (
        about_content[:about_footer_start] +
        correct_footer +
        about_content[about_footer_end + len('</footer>'):]
    )
    
    # Write back
    with open('about.html', 'w', encoding='utf-8') as f:
        f.write(fixed_content)
else:
    exit("Footer not found in about.html")
