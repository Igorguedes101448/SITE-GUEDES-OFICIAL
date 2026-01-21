// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Form submission handler
const contactForm = document.querySelector('.contact-form');
if (contactForm) {
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form data (for future server communication)
        const formData = {
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            message: document.getElementById('message').value
        };
        
        // In a production environment, you would send formData to a server here
        // For now, we'll show a success message
        
        // Create and display success message
        const successMessage = document.createElement('div');
        successMessage.className = 'form-success-message';
        successMessage.textContent = 'Obrigado pela sua mensagem! Entraremos em contato em breve.';
        successMessage.style.cssText = 'background: #10b981; color: white; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1rem; text-align: center;';
        
        contactForm.insertBefore(successMessage, contactForm.firstChild);
        
        // Remove message after 5 seconds
        setTimeout(() => {
            successMessage.remove();
        }, 5000);
        
        // Reset form
        contactForm.reset();
    });
}

// Add active state to navigation links on scroll
const sections = document.querySelectorAll('section[id]');
const navLinks = document.querySelectorAll('.nav-menu a');

window.addEventListener('scroll', () => {
    let current = '';
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;
        if (pageYOffset >= sectionTop - 100) {
            current = section.getAttribute('id');
        }
    });
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === `#${current}`) {
            link.classList.add('active');
        }
    });
});
