// Admin panel JavaScript
// Minimal - sadece gerekli fonksiyonlar

document.addEventListener('DOMContentLoaded', () => {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('[class*="bg-emerald"], [class*="bg-red"]');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.3s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});
