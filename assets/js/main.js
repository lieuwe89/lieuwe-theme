(function () {
  'use strict';

  const header = document.getElementById('site-header');
  const toggle = document.getElementById('nav-toggle');
  const nav    = document.getElementById('site-nav');
  const heroEl = document.querySelector('.hero');

  // ── Mark hero pages so CSS can hide the site name until scrolled ──────────
  if (heroEl) {
    header.classList.add('hero-page');
  }

  // ── Scroll: add dark background to header once past hero ──────────────────
  function onScroll() {
    const threshold = heroEl ? heroEl.offsetHeight * 0.8 : 80;
    if (window.scrollY > threshold) {
      header.classList.add('is-scrolled');
    } else {
      header.classList.remove('is-scrolled');
    }
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll(); // run once on load

  // ── Mobile nav toggle ──────────────────────────────────────────────────────
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      const isOpen = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(isOpen));
      document.body.style.overflow = isOpen ? 'hidden' : '';
    });

    // Close nav when a link is clicked
    nav.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      });
    });
  }
  // ── Video thumbnails: capture first frame for cards without featured image ──
  document.querySelectorAll('.portfolio-card__video-thumb').forEach(function (el) {
    var videoUrl = el.dataset.video;
    if ( ! videoUrl ) return;

    var video = document.createElement('video');
    video.muted      = true;
    video.preload    = 'metadata';
    video.crossOrigin = 'anonymous';

    video.addEventListener('loadedmetadata', function () {
      video.currentTime = 0.001;
    });

    video.addEventListener('seeked', function () {
      var canvas  = document.createElement('canvas');
      canvas.width  = video.videoWidth  || 640;
      canvas.height = video.videoHeight || 480;
      canvas.className = 'portfolio-card__image';
      try {
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        el.replaceWith(canvas);
      } catch (e) {
        // CORS or decode error — leave the placeholder div in place
      }
    });

    video.src = videoUrl;
    video.load();
  });
}());
