import os
import re

files = [
    'home.html', 'models.html', 'influencers.html', 'video.html', 
    'clients.html', 'our-works.html', 'become-a-model.html', 
    'hire-a-model.html', 'application.html', 'faq.html', 
    'news.html', 'contact.html', 'privacy-policy.html'
]

def get_navbar_html(top_active, sub_active):
    # Top level active classes
    home_active = 'active text-primary' if top_active == 'home' else 'text-gray-600'
    talent_active = 'active text-primary' if top_active == 'talent' else 'text-gray-600'
    work_active = 'active text-primary' if top_active == 'work' else 'text-gray-600'
    join_active = 'active text-primary' if top_active == 'join' else 'text-gray-600'
    contact_active = 'active text-primary' if top_active == 'contact' else 'text-gray-600'
    
    # Sub-item active (for dropdown items)
    models_active = 'active' if sub_active == 'models' else ''
    influencers_active = 'active' if sub_active == 'influencers' else ''
    showcase_active = 'active' if sub_active == 'showcase' else ''
    video_active = 'active' if sub_active == 'video' else ''
    clients_active = 'active' if sub_active == 'clients' else ''
    become_active = 'active' if sub_active == 'become' else ''
    apply_active = 'active' if sub_active == 'apply' else ''
    hire_active = 'active' if sub_active == 'hire' else ''
    faq_active = 'active' if sub_active == 'faq' else ''
    contact_us_active = 'active' if sub_active == 'contact_us' else ''

    return f'''          <div
            class="hidden xl:flex items-center space-x-8 text-xs font-bold uppercase tracking-wider"
          >
            <a href="home.html" class="menu-link {home_active}">Home</a>

            <!-- Talent -->
            <div class="dropdown-wrapper group">
              <span class="menu-link flex items-center {talent_active}">Talent <i class="fas fa-chevron-down nav-arrow"></i></span>
              <div class="dropdown-menu">
                <a href="models.html" class="dropdown-item {models_active}">Models <i class="fas fa-arrow-right"></i></a>
                <a href="influencers.html" class="dropdown-item {influencers_active}">Influencers <i class="fas fa-arrow-right"></i></a>
              </div>
            </div>

            <!-- Work -->
            <div class="dropdown-wrapper group">
              <span class="menu-link flex items-center {work_active}">Our Work <i class="fas fa-chevron-down nav-arrow"></i></span>
              <div class="dropdown-menu">
                <a href="our-works.html" class="dropdown-item {showcase_active}">Showcase <i class="fas fa-arrow-right"></i></a>
                <a href="video.html" class="dropdown-item {video_active}">Video <i class="fas fa-arrow-right"></i></a>
                <a href="clients.html" class="dropdown-item {clients_active}">Our Clients <i class="fas fa-arrow-right"></i></a>
              </div>
            </div>

            <!-- Join/Hire -->
            <div class="dropdown-wrapper group">
              <span class="menu-link flex items-center {join_active}">Join Us <i class="fas fa-chevron-down nav-arrow"></i></span>
              <div class="dropdown-menu">
                <a href="become-a-model.html" class="dropdown-item {become_active}">Become a Model <i class="fas fa-arrow-right"></i></a>
                <a href="application.html" class="dropdown-item {apply_active}">General Apply <i class="fas fa-arrow-right"></i></a>
                <a href="hire-a-model.html" class="dropdown-item {hire_active}">Hire a Model <i class="fas fa-arrow-right"></i></a>
              </div>
            </div>

            <!-- More -->
            <div class="dropdown-wrapper group">
              <span class="menu-link flex items-center {contact_active}">Contact <i class="fas fa-chevron-down nav-arrow"></i></span>
              <div class="dropdown-menu">
                <a href="faq.html" class="dropdown-item {faq_active}">FAQ\'s <i class="fas fa-arrow-right"></i></a>
                <a href="contact.html" class="dropdown-item {contact_us_active}">Contact Us <i class="fas fa-arrow-right"></i></a>
              </div>
            </div>
          </div>'''

def get_mobile_menu_html(sub_active):
    m_home = 'text-primary' if sub_active == 'home' else 'text-gray-500'
    m_models = 'text-primary' if sub_active == 'models' else 'text-gray-500'
    m_influencers = 'text-primary' if sub_active == 'influencers' else 'text-gray-500'
    m_showcase = 'text-primary' if sub_active == 'showcase' else 'text-gray-500'
    m_video = 'text-primary' if sub_active == 'video' else 'text-gray-500'
    m_clients = 'text-primary' if sub_active == 'clients' else 'text-gray-500'
    m_become = 'text-primary' if sub_active == 'become' else 'text-gray-500'
    m_apply = 'text-primary' if sub_active == 'apply' else 'text-gray-500'
    m_hire = 'text-primary' if sub_active == 'hire' else 'text-gray-500'
    m_faq = 'text-primary' if sub_active == 'faq' else 'text-gray-500'
    m_contact = 'text-primary' if sub_active == 'contact_us' else 'text-gray-500'

    return f'''        <div
          class="px-6 pt-4 pb-6 flex flex-col space-y-1 text-sm font-bold uppercase tracking-wider"
        >
          <a href="home.html" class="{m_home} py-3 border-b border-gray-50">Home</a>
          
          <div class="pt-4 pb-2 text-[10px] text-gray-400 tracking-[0.2em]">Talent</div>
          <a href="models.html" class="{m_models} hover:text-primary py-3 border-b border-gray-50 transition">Models</a>
          <a href="influencers.html" class="{m_influencers} hover:text-primary py-3 border-b border-gray-50 transition">Influencers</a>
          
          <div class="pt-4 pb-2 text-[10px] text-gray-400 tracking-[0.2em]">Our Work</div>
          <a href="our-works.html" class="{m_showcase} hover:text-primary py-3 border-b border-gray-50 transition">Showcase</a>
          <a href="video.html" class="{m_video} hover:text-primary py-3 border-b border-gray-50 transition">Video</a>
          <a href="clients.html" class="{m_clients} hover:text-primary py-3 border-b border-gray-50 transition">Our Clients</a>
          
          <div class="pt-4 pb-2 text-[10px] text-gray-400 tracking-[0.2em]">Join & Booking</div>
          <a href="become-a-model.html" class="{m_become} hover:text-primary py-3 border-b border-gray-50 transition">Become a Model</a>
          <a href="application.html" class="{m_apply} hover:text-primary py-3 border-b border-gray-50 transition">Apply</a>
          <a href="hire-a-model.html" class="{m_hire} hover:text-primary py-3 border-b border-gray-50 transition">Hire a Model</a>
          
          <div class="pt-4 pb-2 text-[10px] text-gray-400 tracking-[0.2em]">Information</div>
          <a href="faq.html" class="{m_faq} hover:text-primary py-3 border-b border-gray-50 transition">FAQ\'s</a>
          <a href="contact.html" class="{m_contact} hover:text-primary py-3 border-b border-gray-50 transition">Contact Us</a>
        </div>'''

def process_file(filename):
    with open(filename, 'r') as f:
        content = f.read()
    
    # Determine sub_active page
    sub_active = 'home'
    if filename == 'home.html': sub_active = 'home'
    elif filename == 'models.html': sub_active = 'models'
    elif filename == 'influencers.html': sub_active = 'influencers'
    elif filename == 'video.html': sub_active = 'video'
    elif filename == 'clients.html': sub_active = 'clients'
    elif filename == 'our-works.html': sub_active = 'showcase'
    elif filename == 'become-a-model.html': sub_active = 'become'
    elif filename == 'application.html': sub_active = 'apply'
    elif filename == 'hire-a-model.html': sub_active = 'hire'
    elif filename == 'faq.html': sub_active = 'faq'
    elif filename == 'contact.html': sub_active = 'contact_us'
    
    # Map top-level active state
    top_active = sub_active
    if sub_active in ['models', 'influencers']: top_active = 'talent'
    elif sub_active in ['video', 'showcase', 'clients']: top_active = 'work'
    elif sub_active in ['become', 'apply', 'hire']: top_active = 'join'
    elif sub_active in ['faq', 'contact_us']: top_active = 'contact'

    # Replace Desktop Nav
    desktop_pattern = re.compile(r'<div\s+class="hidden xl:flex items-center space-x-[^"]*text-xs font-bold uppercase tracking-wider"\s*>.*?</div>', re.DOTALL)
    content = desktop_pattern.sub(get_navbar_html(top_active, sub_active), content)

    # Replace Mobile Nav
    mobile_pattern = re.compile(r'<div\s+id="mobile-menu"[^>]*>.*?<div\s+class="px-6 pt-4 pb-6 flex flex-col[^>]*>.*?</div>\s*</div>', re.DOTALL)
    
    def mobile_sub(match):
        return f'<div id="mobile-menu" class="hidden xl:hidden bg-white border-t border-gray-100 shadow-xl pb-4">\n{get_mobile_menu_html(sub_active)}\n      </div>'

    content = mobile_pattern.sub(mobile_sub, content)
    
    with open(filename, 'w') as f:
        f.write(content)

for f in files:
    if os.path.exists(f):
        print(f"Updating {f}...")
        process_file(f)
