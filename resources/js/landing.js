/**
 * Landing page interactions: scroll progress, nav condensing, reveals,
 * counters, cursor glow, theme toggle, testimonial slider, confetti.
 * Everything here degrades gracefully — if an element isn't present
 * (e.g. this script loading on a non-landing page) each block just no-ops.
 */

document.addEventListener('DOMContentLoaded', () => {

  /* ---------- Scroll progress bar ---------- */
  const progressBar = document.getElementById('lpProgress');
  if (progressBar) {
    const updateProgress = () => {
      const h = document.documentElement;
      const scrolled = (h.scrollTop) / (h.scrollHeight - h.clientHeight) * 100;
      progressBar.style.width = scrolled + '%';
    };
    window.addEventListener('scroll', updateProgress, { passive: true });
    updateProgress();
  }

  /* ---------- Nav: condense on scroll ---------- */
  const lpNav = document.getElementById('lpNav');
  if (lpNav) {
    const onScroll = () => lpNav.classList.toggle('scrolled', window.scrollY > 30);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ---------- Scroll-to-top button ---------- */
  const toTop = document.getElementById('lpToTop');
  if (toTop) {
    window.addEventListener('scroll', () => {
      toTop.classList.toggle('show', window.scrollY > 700);
    }, { passive: true });
    toTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  /* ---------- Scroll-reveal ---------- */
  const revealTargets = document.querySelectorAll('[data-reveal], [data-reveal-group]');
  if (revealTargets.length) {
    const revealObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('in-view');
          revealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });
    revealTargets.forEach(el => revealObserver.observe(el));
  }

  /* ---------- Count-up numbers ---------- */
  const counters = document.querySelectorAll('[data-count]');
  if (counters.length) {
    const countObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        const target = parseInt(el.dataset.count, 10) || 0;
        const duration = 900;
        const start = performance.now();
        function tick(now) {
          const progress = Math.min((now - start) / duration, 1);
          const eased = 1 - Math.pow(1 - progress, 3);
          el.textContent = Math.round(eased * target);
          if (progress < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
        countObserver.unobserve(el);
      });
    }, { threshold: 0.4 });
    counters.forEach(el => countObserver.observe(el));
  }

  /* ---------- Cursor-follow spotlight (hero only, desktop only) ---------- */
  const spotlightZone = document.querySelector('.lp-hero');
  if (spotlightZone && matchMedia('(hover: hover) and (pointer: fine)').matches) {
    spotlightZone.addEventListener('pointermove', (e) => {
      const rect = spotlightZone.getBoundingClientRect();
      spotlightZone.style.setProperty('--mx', ((e.clientX - rect.left) / rect.width * 100) + '%');
      spotlightZone.style.setProperty('--my', ((e.clientY - rect.top) / rect.height * 100) + '%');
    });
  }

  /* ---------- Theme toggle (light / dark), persisted ---------- */
  const themeToggle = document.getElementById('lpThemeToggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      const isDark = document.documentElement.getAttribute('data-lp-theme') === 'dark';
      const next = isDark ? 'light' : 'dark';
      document.documentElement.setAttribute('data-lp-theme', next);
      try { localStorage.setItem('lp-theme', next); } catch (e) {}
    });
  }

  /* ---------- Testimonial slider ---------- */
  const slider = document.getElementById('lpTestimonials');
  if (slider) {
    const slides = slider.querySelectorAll('.lp-testimonial');
    const dotsWrap = document.getElementById('lpTestimonialDots');
    let index = 0;
    let timer;

    const dots = Array.from(slides).map((_, i) => {
      const d = document.createElement('button');
      d.className = 'lp-dot' + (i === 0 ? ' active' : '');
      d.setAttribute('aria-label', 'Go to testimonial ' + (i + 1));
      d.addEventListener('click', () => { show(i); resetTimer(); });
      dotsWrap.appendChild(d);
      return d;
    });

    function show(i) {
      slides[index].classList.remove('active');
      dots[index].classList.remove('active');
      index = (i + slides.length) % slides.length;
      slides[index].classList.add('active');
      dots[index].classList.add('active');
    }
    function resetTimer() {
      clearInterval(timer);
      timer = setInterval(() => show(index + 1), 6000);
    }
    document.getElementById('lpTestimonialPrev')?.addEventListener('click', () => { show(index - 1); resetTimer(); });
    document.getElementById('lpTestimonialNext')?.addEventListener('click', () => { show(index + 1); resetTimer(); });
    resetTimer();
  }

  /* ---------- Confetti burst on primary CTA click ---------- */
  document.querySelectorAll('[data-confetti]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
      const colors = ['#C9862C', '#1F4B4B', '#3E7D58', '#F3E1BE', '#8F5E17'];
      const originX = e.clientX, originY = e.clientY;
      for (let i = 0; i < 26; i++) {
        const p = document.createElement('span');
        p.className = 'lp-confetti-piece';
        p.style.left = originX + 'px';
        p.style.top = originY + 'px';
        p.style.background = colors[i % colors.length];
        const angle = (Math.PI * 2 * i) / 26 + Math.random() * 0.5;
        const dist = 70 + Math.random() * 90;
        p.style.setProperty('--dx', Math.cos(angle) * dist + 'px');
        p.style.setProperty('--dy', Math.sin(angle) * dist + 'px');
        p.style.setProperty('--rot', (Math.random() * 480 - 240) + 'deg');
        document.body.appendChild(p);
        p.addEventListener('animationend', () => p.remove());
      }
    });
  });

  /* ---------- Mobile menu ---------- */
  const menuBtn = document.getElementById('lpMenuBtn');
  const mobileMenu = document.getElementById('lpMobileMenu');
  if (menuBtn && mobileMenu) {
    menuBtn.addEventListener('click', () => {
      const isOpen = mobileMenu.classList.toggle('open');
      menuBtn.classList.toggle('open', isOpen);
      menuBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    mobileMenu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
      mobileMenu.classList.remove('open');
      menuBtn.classList.remove('open');
    }));
  }

});

/* Apply saved theme immediately (before DOMContentLoaded) to avoid a flash */
(function () {
  try {
    const saved = localStorage.getItem('lp-theme');
    if (saved) document.documentElement.setAttribute('data-lp-theme', saved);
  } catch (e) {}
})();
