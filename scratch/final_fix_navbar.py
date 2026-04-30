import os

files = [
    'video.html', 'clients.html', 'our-works.html', 
    'hire-a-model.html', 'news.html', 'contact.html', 'privacy-policy.html'
]

# Desktop mess pattern
desktop_mess = '          <a href="influencers.html" class="text-gray-500 hover:text-primary border-b border-gray-50 pb-2 transition">Influencers</a>            <a href="influencers.html" class="menu-link text-gray-600">Influencers</a>'
desktop_fix = '            <a href="influencers.html" class="menu-link text-gray-600">Influencers</a>'

# Mobile mess pattern
mobile_mess = '<a href="models.html"          <a href="influencers.html" class="text-gray-500 hover:text-primary border-b border-gray-50 pb-2 transition">Influencers</a>            class="text-gray-500 hover:text-primary border-b border-gray-50 pb-2 transition"'
mobile_fix = '<a href="models.html" class="text-gray-500 hover:text-primary border-b border-gray-50 pb-2 transition"'

def fix_file(filepath):
    with open(filepath, 'r') as f:
        content = f.read()
    
    # Fix Desktop
    content = content.replace(desktop_mess, desktop_fix)
    
    # Fix Mobile
    content = content.replace(mobile_mess, mobile_fix)
    
    # Final check: if influencers.html is missing in mobile menu, add it correctly
    if 'href="influencers.html"' not in content[content.find('mobile-menu'):]:
        # Very specific mobile replacement to avoid duplicates
        m_search = 'class="text-gray-500 hover:text-primary border-b border-gray-50 pb-2 transition"\n            >Models</a>'
        m_replace = m_search + '\n          <a href="influencers.html" class="text-gray-500 hover:text-primary border-b border-gray-50 pb-2 transition">Influencers</a>'
        content = content.replace(m_search, m_replace)

    with open(filepath, 'w') as f:
        f.write(content)

for f in files:
    if os.path.exists(f):
        print(f"Fixing {f}...")
        fix_file(f)
