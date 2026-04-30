import os
import re

files = [
    'home.html', 'models.html', 'video.html', 'clients.html', 'our-works.html', 
    'become-a-model.html', 'hire-a-model.html', 'application.html', 
    'faq.html', 'news.html', 'contact.html', 'privacy-policy.html'
]

def cleanup_navbar(filepath):
    with open(filepath, 'r') as f:
        lines = f.readlines()
    
    new_lines = []
    seen_desktop = False
    seen_mobile = False
    
    for line in lines:
        # Check for desktop link (contains influencers.html and menu-link)
        if 'href="influencers.html"' in line and 'menu-link' in line:
            if not seen_desktop:
                new_lines.append(line)
                seen_desktop = True
            continue
        
        # Check for mobile link (contains influencers.html and text-gray-500 or active text-primary)
        if 'href="influencers.html"' in line and ('text-gray-500' in line or 'text-primary' in line):
            # Check if it's the mobile version (usually preceded by text-gray-500 or active state)
            # In influencers.html itself it might be text-primary
            if not seen_mobile:
                new_lines.append(line)
                seen_mobile = True
            continue
            
        new_lines.append(line)
        
    with open(filepath, 'w') as f:
        f.writelines(new_lines)

for f in files:
    if os.path.exists(f):
        print(f"Cleaning {f}...")
        cleanup_navbar(f)
