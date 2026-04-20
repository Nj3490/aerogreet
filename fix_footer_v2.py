#!/usr/bin/env python3
import re

# Read the correct footer from index.html
with open('index.html', 'r', encoding='utf-8') as f:
    index_content = f.read()
    # Extract footer from index.html (between <footer> and </footer>)
    footer_match = re.search(r'<footer>.*?</footer>', index_content, re.DOTALL)
    if not footer_match:
        print("ERROR: Could not find footer in index.html")
        exit(1)
    correct_footer = footer_match.group(0)
    print(f"Found correct footer from index.html ({len(correct_footer)} chars)")

# Read about.html
with open('about.html', 'r', encoding='utf-8') as f:
    about_content = f.read()

# Replace the entire footer section with correct one
# This should work because we're replacing the corrupted version with known-good UTF-8
new_content = re.sub(r'<footer>.*?</footer>', correct_footer, about_content, flags=re.DOTALL)

if new_content == about_content:
    print("WARNING: No replacement made - footer may not exist or pattern didn't match")
else:
    print(f"Replacement successful - file size changed from {len(about_content)} to {len(new_content)} chars")
    
# Write back to about.html
with open('about.html', 'w', encoding='utf-8') as f:
    f.write(new_content)
    print("File written: about.html")

# Verify the fix
with open('about.html', 'r', encoding='utf-8') as f:
    verification = f.read()
    if '──' in verification:
        print("✅ VERIFIED: Correct footer encoding found (──)")
    else:
        print("❌ FAILED: Footer still shows corruption")
