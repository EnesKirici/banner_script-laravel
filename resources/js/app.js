import './bootstrap';
import { initTMDB, initSidebarSlider } from './tmdb';
import { initParticles } from './particles';

document.addEventListener('DOMContentLoaded', async () => {
    await initParticles();
    initTMDB();
    initSidebarSlider();
});