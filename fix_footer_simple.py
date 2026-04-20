#!/usr/bin/env python3
import re
import sys

log = []

try:
    # Read index.html to get correct footer
    with open('index.html', 'r', encoding='utf-8') as f:
        index_text = f.read()
    
    # Find the footer in index.html
    footer_match = re.search(r'<footer>.*?</footer>', index_text, re.DOTALL)
    if not footer_match:
        log.append("ERROR: Could not find footer in index.html")
        sys.exit(1)
    
    correct_footer = footer_match.group(0)
    log.append(f"Found correct footer from index.html: {len(correct_footer)} chars")
    
    # Read about.html
    with open('about.html', 'r', encoding='utf-8') as f:
        about_text = f.read()
    
    log.append(f"Read about.html: {len(about_text)} chars")
    
    # Replace footer using regex
    new_text = re.sub(r'<footer>.*?</footer>', correct_footer, about_text, flags=re.DOTALL)
    
    if new_text == about_text:
        log.append("WARNING: No footer replacement made")
    else:
        log.append(f"Footer replaced successfully")
        # Write the fixed content back
        with open('about.html', 'w', encoding='utf-8') as f:
            f.write(new_text)
        log.append("File written successfully")
        
        # Verify 
        with open('about.html', 'r', encoding='utf-8') as f:
            verify = f.read()
        if '──' in verify and 'â€"' not in verify:
            log.append("VERIFICATION SUCCESS: Footer fixed correctly")
        else:
            log.append("VERIFICATION FAILED: Footer still has issues")

except Exception as e:
    log.append(f"EXCEPTION: {str(e)}")

finally:
    # Write log file
    with open('fix_footer_log.txt', 'w') as f:
        f.write('\n'.join(log))
