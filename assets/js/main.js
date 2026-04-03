document.addEventListener('DOMContentLoaded', () => {
    // Mobile navigation toggle
    const navToggle = document.getElementById('nav-toggle');
    const mainNav = document.getElementById('site-navigation');
    
    if (navToggle && mainNav) {
        navToggle.addEventListener('click', () => {
            mainNav.classList.toggle('is-open');
            const isExpanded = navToggle.getAttribute('aria-expanded') === 'true';
            navToggle.setAttribute('aria-expanded', !isExpanded);
            navToggle.textContent = mainNav.classList.contains('is-open') ? '✕' : '☰';
        });
    }

    // Header scroll effect
    const header = document.getElementById('masthead');
    const heroSection = document.querySelector('.hero-section');
    
    if (header) {
        window.addEventListener('scroll', () => {
            // Change bg after 100px scroll
            if (window.scrollY > 100) {
                header.classList.add('scrolled-dark');
            } else {
                header.classList.remove('scrolled-dark');
            }
        });
    }
});
