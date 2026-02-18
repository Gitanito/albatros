/**
 * Albatros Cookie Consent Manager
 * Easily administrable cookie overlay.
 */

(function () {
    const CONSENT_KEY = 'albatros_cookie_consent';

    function initCookieConsent() {
        // Check if consent already given
        if (localStorage.getItem(CONSENT_KEY)) return;

        // Create Banner HTML
        const banner = document.createElement('div');
        banner.id = 'cookie-banner';
        banner.innerHTML = `
            <div class="cookie-container">
                <div class="cookie-content">
                    <h3 class="cookie-title">
                        <i data-lucide="shield-check" style="width: 20px; height: 20px; color: #fbbf24;"></i>
                        Informationen zu Cookies
                    </h3>
                    <p class="cookie-text">
                        Ich verwende Cookies, um dir die bestmögliche Erfahrung auf meiner Seite zu bieten und Inhalte zu personalisieren. 
                        Weitere Informationen findest du in meiner <a href="index.html#privacy">Datenschutzerklärung</a>.
                    </p>
                </div>
                <div class="cookie-actions">
                    <button class="cookie-btn cookie-btn-secondary" id="cookie-decline">Nur Notwendige</button>
                    <button class="cookie-btn cookie-btn-primary" id="cookie-accept">Alle akzeptieren</button>
                </div>
            </div>
        `;

        document.body.appendChild(banner);

        // Re-initialize Lucide icons if available
        if (window.lucide) {
            window.lucide.createIcons();
        }

        // Show banner with delay for smooth entrance
        setTimeout(() => {
            banner.classList.add('show');
        }, 500);

        // Event Listeners
        document.getElementById('cookie-accept').addEventListener('click', () => {
            saveConsent('all');
        });

        document.getElementById('cookie-decline').addEventListener('click', () => {
            saveConsent('necessary');
        });
    }

    function saveConsent(level) {
        const banner = document.getElementById('cookie-banner');
        localStorage.setItem(CONSENT_KEY, level);

        // Hide banner
        banner.classList.remove('show');

        // Remove from DOM after transition
        setTimeout(() => {
            banner.remove();
        }, 600);

        // Here you could trigger analytics or other scripts based on 'level'
        console.log(`Cookie consent saved: ${level}`);
    }

    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCookieConsent);
    } else {
        initCookieConsent();
    }
})();
