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
            <a href="home.html" class="menu-link {{home_active}}">Home</a>

            <!-- Talent -->
            <div class="dropdown-wrapper group">
              <span class="menu-link flex items-center {{talent_active}}">Talent <i class="fas fa-chevron-down nav-arrow"></i></span>
              <div class="dropdown-menu">
                <a href="models.html" class="dropdown-item {{models_active}}">Models <i class="fas fa-arrow-right"></i></a>
                <a href="influencers.html" class="dropdown-item {{influencers_active}}">Influencers <i class="fas fa-arrow-right"></i></a>
              </div>
            </div>

            <!-- Work -->
            <div class="dropdown-wrapper group">
              <span class="menu-link flex items-center {{work_active}}">Our Work <i class="fas fa-chevron-down nav-arrow"></i></span>
              <div class="dropdown-menu">
                <a href="our-works.html" class="dropdown-item {{showcase_active}}">Showcase <i class="fas fa-arrow-right"></i></a>
                <a href="video.html" class="dropdown-item {{video_active}}">Video <i class="fas fa-arrow-right"></i></a>
                <a href="clients.html" class="dropdown-item {{clients_active}}">Our Clients <i class="fas fa-arrow-right"></i></a>
              </div>
            </div>

            <!-- Join/Hire -->
            <div class="dropdown-wrapper group">
              <span class="menu-link flex items-center {{join_active}}">Join Us <i class="fas fa-chevron-down nav-arrow"></i></span>
              <div class="dropdown-menu">
                <a href="become-a-model.html" class="dropdown-item {{become_active}}">Become a Model <i class="fas fa-arrow-right"></i></a>
                <a href="application.html" class="dropdown-item {{apply_active}}">General Apply <i class="fas fa-arrow-right"></i></a>
                <a href="hire-a-model.html" class="dropdown-item {{hire_active}}">Hire a Model <i class="fas fa-arrow-right"></i></a>
              </div>
            </div>

            <!-- More -->
            <div class="dropdown-wrapper group">
              <span class="menu-link flex items-center {{contact_active}}">Contact <i class="fas fa-chevron-down nav-arrow"></i></span>
              <div class="dropdown-menu">
                <a href="faq.html" class="dropdown-item {{faq_active}}">FAQ\'s <i class="fas fa-arrow-right"></i></a>
                <a href="contact.html" class="dropdown-item {{contact_us_active}}">Contact Us <i class="fas fa-arrow-right"></i></a>
              </div>
            </div>
          </div>'''

def get_mobile_menu_html(sub_active):
    m_home = 'active' if sub_active == 'home' else ''
    m_models = 'active' if sub_active == 'models' else ''
    m_influencers = 'active' if sub_active == 'influencers' else ''
    m_showcase = 'active' if sub_active == 'showcase' else ''
    m_video = 'active' if sub_active == 'video' else ''
    m_clients = 'active' if sub_active == 'clients' else ''
    m_become = 'active' if sub_active == 'become' else ''
    m_apply = 'active' if sub_active == 'apply' else ''
    m_hire = 'active' if sub_active == 'hire' else ''
    m_faq = 'active' if sub_active == 'faq' else ''
    m_contact = 'active' if sub_active == 'contact_us' else ''

    return f'''        <div
          class="px-8 pt-4 pb-10 flex flex-col space-y-1 text-sm font-bold uppercase tracking-wider"
        >
          <a href="home.html" class="mobile-link {{m_home}}">Home <i class="fas fa-chevron-right"></i></a>
          
          <div class="mobile-section-title">Talent</div>
          <a href="models.html" class="mobile-link {{m_models}}">Models <i class="fas fa-chevron-right"></i></a>
          <a href="influencers.html" class="mobile-link {{m_influencers}}">Influencers <i class="fas fa-chevron-right"></i></a>
          
          <div class="mobile-section-title">Our Work</div>
          <a href="our-works.html" class="mobile-link {{m_showcase}}">Showcase <i class="fas fa-chevron-right"></i></a>
          <a href="video.html" class="mobile-link {{m_video}}">Video <i class="fas fa-chevron-right"></i></a>
          <a href="clients.html" class="mobile-link {{m_clients}}">Our Clients <i class="fas fa-chevron-right"></i></a>
          
          <div class="mobile-section-title">Join & Booking</div>
          <a href="become-a-model.html" class="mobile-link {{m_become}}">Become a Model <i class="fas fa-chevron-right"></i></a>
          <a href="application.html" class="mobile-link {{m_apply}}">Apply <i class="fas fa-chevron-right"></i></a>
          <a href="hire-a-model.html" class="mobile-link {{m_hire}}">Hire a Model <i class="fas fa-chevron-right"></i></a>
          
          <div class="mobile-section-title">Information</div>
          <a href="faq.html" class="mobile-link {{m_faq}}">FAQ\'s <i class="fas fa-chevron-right"></i></a>
          <a href="contact.html" class="mobile-link {{m_contact}}">Contact Us <i class="fas fa-chevron-right"></i></a>
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
    mobile_pattern = re.compile(r'<div\s+id="mobile-menu"[^>]*>.*?<div\s+class="px-[^>]*>.*?</div>\s*</div>', re.DOTALL)
    
    def mobile_sub(match):
        return f'<div id="mobile-menu" class="hidden xl:hidden bg-white border-t border-gray-100 shadow-xl pb-4">\n{get_mobile_menu_html(sub_active)}\n      </div>'

    content = mobile_pattern.sub(mobile_sub, content)
    
    with open(filename, 'w') as f:
        f.write(content)

for f in files:
    if os.path.exists(f):
        print(f"Updating {{f}}...")
        process_file(f)
