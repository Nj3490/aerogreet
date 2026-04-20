#!/usr/bin/env python3
import re
import os

# List of all files to fix
files = [
    'about.html',
    'contact.html',
    'blog.html',
    'privacy.html',
    'refund.html', 
    'terms.html',
    'airports.html',
    'international.html',
    'delhi-airport-meet-greet.html',
    'agartala-airport-meet-greet.html',
    'agra-airport-meet-greet.html',
    'ahmedabad-airport-meet-greet.html',
    'aizawl-airport-meet-greet.html',
    'amravati-airport-meet-greet.html',
    'amritsar-airport-meet-greet.html',
    'aurangabad-airport-meet-greet.html',
    'ayodhya-airport-meet-greet.html',
    'bagdogra-airport-meet-greet.html',
    'bareilly-airport-meet-greet.html',
    'belagavi-airport-meet-greet.html',
    'bengaluru-airport-meet-greet.html',
    'bhopal-airport-meet-greet.html',
    'bhubaneswar-airport-meet-greet.html',
    'bikaner-airport-meet-greet.html',
    'chandigarh-airport-meet-greet.html',
    'chennai-airport-meet-greet.html',
    'coimbatore-airport-meet-greet.html',
    'darbhanga-airport-meet-greet.html',
    'deoghar-airport-meet-greet.html',
    'dharamshala-airport-meet-greet.html',
    'dibrugarh-airport-meet-greet.html',
    'dimapur-airport-meet-greet.html',
    'diu-airport-meet-greet.html',
    'durgapur-airport-meet-greet.html',
    'gaya-airport-meet-greet.html',
    'goa-airport-meet-greet.html',
    'gorakhpur-airport-meet-greet.html',
    'guwahati-airport-meet-greet.html',
    'gwalior-airport-meet-greet.html',
    'hirasar-airport-meet-greet.html',
    'hubli-airport-meet-greet.html',
    'hyderabad-airport-meet-greet.html',
    'imphal-airport-meet-greet.html',
    'indore-airport-meet-greet.html',
    'itanagar-airport-meet-greet.html',
    'jabalpur-airport-meet-greet.html',
    'jagdalpur-airport-meet-greet.html',
    'jaipur-airport-meet-greet.html',
    'jammu-airport-meet-greet.html',
    'jharsuguda-airport-meet-greet.html',
    'jodhpur-airport-meet-greet.html',
    'jorhat-airport-meet-greet.html',
    'kadapa-airport-meet-greet.html',
    'kandla-airport-meet-greet.html',
    'kannur-airport-meet-greet.html',
    'kanpur-airport-meet-greet.html',
    'khajuraho-airport-meet-greet.html',
    'kishangarh-airport-meet-greet.html',
    'kochi-airport-meet-greet.html',
    'kohima-airport-meet-greet.html',
    'kolhapur-airport-meet-greet.html',
    'kolkata-airport-meet-greet.html',
    'keshod-airport-meet-greet.html',
    'kozhikode-airport-meet-greet.html',
    'kurnool-airport-meet-greet.html',
    'leh-airport-meet-greet.html',
    'lilabari-airport-meet-greet.html',
    'lucknow-airport-meet-greet.html',
    'ludhiana-airport-meet-greet.html',
    'madurai-airport-meet-greet.html',
    'mangaluru-airport-meet-greet.html',
    'mopa-airport-meet-greet.html',
    'mumbai-airport-meet-greet.html',
    'mysuru-airport-meet-greet.html',
    'nagpur-airport-meet-greet.html',
    'nashik-airport-meet-greet.html',
    'patna-airport-meet-greet.html',
    'port-blair-airport-meet-greet.html',
    'porbandar-airport-meet-greet.html',
    'prayagraj-airport-meet-greet.html',
    'pune-airport-meet-greet.html',
    'rajahmundry-airport-meet-greet.html',
    'raipur-airport-meet-greet.html',
    'rajkot-airport-meet-greet.html',
    'ranchi-airport-meet-greet.html',
    'salem-airport-meet-greet.html',
    'shimla-airport-meet-greet.html',
    'shillong-airport-meet-greet.html',
    'shivamogga-airport-meet-greet.html',
    'silchar-airport-meet-greet.html',
    'srinagar-airport-meet-greet.html',
    'surat-airport-meet-greet.html',
    'thiruvananthapuram-airport-meet-greet.html',
    'tirupati-airport-meet-greet.html',
    'tiruchirappalli-airport-meet-greet.html',
    'tuticorin-airport-meet-greet.html',
    'udaipur-airport-meet-greet.html',
    'vadodara-airport-meet-greet.html',
    'varanasi-airport-meet-greet.html',
    'visakhapatnam-airport-meet-greet.html',
    'vijayawada-airport-meet-greet.html',
]

fixed_count = 0
for filename in files:
    filepath = os.path.join('.', filename)
    if not os.path.exists(filepath):
        continue
    
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original_len = len(content)
        
        # Fix corrupted UTF-8 sequences
        content = re.sub(r'â€"', '—', content)  # Corrupted em-dash
        content = re.sub(r'â"€', '─', content)  # Corrupted horizontal line
        content = re.sub(r'â"€â"€', '──', content)  # Double corrupted lines
        
        if len(content) != original_len or True:  # Always write to ensure UTF-8 is correct
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            fixed_count += 1
            print(f'✅ {filename}')
        
    except Exception as e:
        print(f'❌ {filename}: {e}')

print(f'\n✅ Fixed {fixed_count} files!')
