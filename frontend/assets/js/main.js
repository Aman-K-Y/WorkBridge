// ============================================
// WorkBridge — Frontend interactivity
// Small, dependency-free polish: button press feedback,
// auto-dismissing alerts, and smooth anchor scrolling.
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // --- Dark / light mode toggle ---
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            localStorage.setItem('workbridge-theme', next);
        });
    }

    // --- Auto-dismiss success alerts after a few seconds ---
    document.querySelectorAll('.alert-success').forEach(function (alert) {
        setTimeout(function () {
            if (prefersReducedMotion) {
                alert.style.display = 'none';
                return;
            }
            alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease, max-height 0.4s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-6px)';
            setTimeout(function () { alert.style.display = 'none'; }, 400);
        }, 4000);
    });

    // --- Subtle press feedback on buttons (beyond the CSS :active state) ---
    document.querySelectorAll('.btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (prefersReducedMotion) return;
            btn.style.transform = 'scale(0.97)';
            setTimeout(function () { btn.style.transform = ''; }, 120);
        });
    });

    // --- Smooth-scroll to the review section if the URL has #review ---
    if (window.location.hash === '#review') {
        const target = document.getElementById('review');
        if (target) {
            setTimeout(function () {
                target.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth', block: 'center' });
            }, 100);
        }
    }

    // --- Auto-scroll chat thread to the latest message on load ---
    const chatThread = document.querySelector('.chat-thread');
    if (chatThread) {
        chatThread.scrollTop = chatThread.scrollHeight;
    }
});
