#!/usr/bin/env python3
import sys
import os

print("Python is working!", file=sys.stdout, flush=True)
print(f"Current directory: {os.getcwd()}", file=sys.stdout, flush=True)
print(f"Files in directory: {len(os.listdir('.'))}", file=sys.stdout, flush=True)

# Check about.html
if os.path.exists('about.html'):
    size = os.path.getsize('about.html')
    print(f"about.html exists, size: {size} bytes", file=sys.stdout, flush=True)
    
    with open('about.html', 'r', encoding='utf-8') as f:
        content = f.read()
        if 'â€"' in content:
            print("Corruption found: â€\" exists in file", file=sys.stdout, flush=True)
        if '——' in content:
            print("Correct em-dash found", file=sys.stdout, flush=True)
        if 'â"€â"€' in content:
            print("Corruption found: â\"€â\"€ exists in file", file=sys.stdout, flush=True)
else:
    print("ERROR: about.html not found", file=sys.stdout, flush=True)
