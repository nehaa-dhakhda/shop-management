// Auto-hide flash messages
document.addEventListener('DOMContentLoaded', () => {
    const flash = document.querySelector('.alert-floating');
    if (flash) {
        setTimeout(() => flash.style.opacity = '0', 3000);
        setTimeout(() => flash.remove(), 3400);
    }
});
