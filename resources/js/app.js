import './bootstrap';
import { initTMDB, initSidebarToggle, initSidebarSlider } from './tmdb';
import { initParticles } from './particles';

document.addEventListener('DOMContentLoaded', async () => {
    await initParticles();
    initTMDB();
    initSidebarToggle();
    initSidebarSlider();
});