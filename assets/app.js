import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';
import './styles/landing.css';
import './styles/login.css';
import './styles/user/dashboard.css';
import './styles/user/evenements.css';
import './styles/admin/dashboard.css';
import './styles/admin/evenements.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

/* ── Mobile navigation toggles ── */
document.addEventListener('DOMContentLoaded', () => {
    // User navbar toggle
    const navToggle = document.getElementById('navToggle');
    const navLinks = document.getElementById('navLinks');
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', () => navLinks.classList.toggle('open'));
    }

    // Admin sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    }
});
