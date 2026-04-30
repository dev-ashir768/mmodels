import os
import re

files = [
    'faq.html', 'video.html', 'clients.html', 'our-works.html', 
    'become-a-model.html', 'hire-a-model.html', 'application.html', 
    'news.html', 'contact.html', 'privacy-policy.html'
]

def update_file(filepath):
    with open(filepath, 'r') as f:
        content = f.read()
    
    # Desktop update (usually single line)
    desktop_pattern = re.compile(r'(<a\s+href="models.html"\s+class="menu-link[^>]*>\s*Models\s*</a>)', re.DOTALL)
    desktop_replacement = r'\1\n            <a href="influencers.html" class="menu-link text-gray-600">Influencers</a>'
    content = desktop_pattern.sub(desktop_replacement, content)
    
    # Mobile update (multiline and potentially split </a>)
    mobile_regex = re.compile(r'(<a\s+href="models.html".*?Models\s*</a\s*>)', re.DOTALL)
    
    def mobile_sub(match):
        original = match.group(1)
        new_link = '\n          <a href="influencers.html" class="text-gray-500 hover:text-primary border-b border-gray-50 pb-2 transition">Influencers</a>'
        # Avoid duplicate addition
        if 'influencers.html' in content[match.end():match.end()+200]:
            return original
        return original + new_link

    content = mobile_regex.sub(mobile_sub, content)
    
    with open(filepath, 'w') as f:
        f.write(content)

for f in files:
    if os.path.exists(f):
        print(f"Updating {f}...")
        update_file(f)
