/**
 * Bat Animation — Pixel yarasaları arka planda uçurur
 * Yarasalar ekran sınırları içinde kalır, kenarlara çarpınca yön değiştirir.
 */

function createBat(container, index, config) {
    const bat = document.createElement('div');
    bat.className = 'bat';

    const scale = config.scale;
    const opacity = 0.4 + Math.random() * 0.4;
    const baseSpeed = 60 / config.speed; // px per frame (düşük speed değeri = hızlı)

    // Rastgele başlangıç pozisyonu (ekran içinde)
    let x = Math.random() * 80 + 10; // %10-%90 arası
    let y = Math.random() * 70 + 10;

    // Rastgele yön (açı)
    let angle = Math.random() * Math.PI * 2;
    let speedMultiplier = 0.7 + Math.random() * 0.6;

    bat.style.cssText = `
        position: absolute;
        transform: scale(${scale});
        opacity: ${opacity};
        animation: ${config.flapSpeed}s bat steps(1) infinite;
        will-change: left, top;
    `;

    bat.style.left = x + '%';
    bat.style.top = y + '%';

    // Sola gidiyorsa normal, sağa gidiyorsa flip
    function updateDirection() {
        const vx = Math.cos(angle);
        if (vx > 0) {
            bat.style.transform = `scale(${scale}) scaleX(-1)`;
        } else {
            bat.style.transform = `scale(${scale})`;
        }
    }
    updateDirection();

    // Animasyon frame'i
    function animate() {
        const vx = Math.cos(angle) * baseSpeed * speedMultiplier;
        const vy = Math.sin(angle) * baseSpeed * speedMultiplier;

        x += vx * 0.05;
        y += vy * 0.05;

        // Sınır kontrolü — kenara gelince yön değiştir
        let bounced = false;
        if (x < 2) { angle = Math.PI - angle; x = 2; bounced = true; }
        if (x > 95) { angle = Math.PI - angle; x = 95; bounced = true; }
        if (y < 2) { angle = -angle; y = 2; bounced = true; }
        if (y > 85) { angle = -angle; y = 85; bounced = true; }

        // Küçük rastgele sapma (doğal hareket)
        if (!bounced) {
            angle += (Math.random() - 0.5) * 0.05;
        }

        if (bounced) {
            updateDirection();
        }

        bat.style.left = x + '%';
        bat.style.top = y + '%';

        requestAnimationFrame(animate);
    }

    requestAnimationFrame(animate);
    return bat;
}

export async function initBatAnimation() {
    const layer = document.getElementById('bat-animation-layer');
    if (!layer) return;

    try {
        const response = await fetch('/api/bat-animation/config');
        if (!response.ok) return;

        const data = await response.json();
        if (!data.enabled) return;

        // CSS dosyasını text olarak çek ve renkleri değiştir
        const cssResponse = await fetch('/css/bat-animation.css');
        if (!cssResponse.ok) return;

        let cssText = await cssResponse.text();

        const outerColor = data.outer_color || '#54556b';
        const innerColor = data.inner_color || '#202020';
        cssText = cssText.replaceAll('#54556b', outerColor);
        cssText = cssText.replaceAll('#202020', innerColor);

        const styleEl = document.createElement('style');
        styleEl.textContent = cssText;
        document.head.appendChild(styleEl);

        // Container
        layer.className = 'bat-animation-container';
        layer.style.cssText = 'position: absolute; inset: 0; overflow: hidden; pointer-events: none; z-index: 1;';

        const config = {
            scale: data.bat_scale || 2,
            speed: data.bat_speed || 20,
            flapSpeed: data.flap_speed || 0.4,
        };

        const count = Math.min(data.bat_count || 5, 30);
        for (let i = 0; i < count; i++) {
            layer.appendChild(createBat(layer, i, config));
        }
    } catch (error) {
        console.warn('Bat animation yüklenemedi:', error);
    }
}
