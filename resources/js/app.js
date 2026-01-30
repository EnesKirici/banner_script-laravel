import './bootstrap';
import { initTMDB } from './tmdb';
import { initParticles } from './particles';

// DOM yüklendiğinde başlat
document.addEventListener('DOMContentLoaded', async () => {
    // Particles background'u başlat
    await initParticles();
    
    // TMDB fonksiyonlarını başlat
    initTMDB();
});