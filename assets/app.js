(function () {
  // ----- mobile menu -----
  var toggle = document.querySelector('.nav-toggle');
  var header = document.querySelector('.site-header');
  if (toggle && header) {
    toggle.addEventListener('click', function () {
      var open = header.classList.toggle('nav-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  // ----- inventory search -----
  var input = document.getElementById('filter');
  if (input) {
    var cards = Array.prototype.slice.call(document.querySelectorAll('.card[data-search]'));
    var none = document.getElementById('no-results');
    var count = document.getElementById('result-count');
    input.addEventListener('input', function () {
      var q = input.value.trim().toLowerCase();
      var shown = 0;
      cards.forEach(function (c) {
        var match = !q || c.getAttribute('data-search').indexOf(q) !== -1;
        c.hidden = !match;
        if (match) shown++;
      });
      if (none) none.hidden = shown !== 0;
      if (count) count.textContent = shown + (shown === 1 ? ' car' : ' cars');
    });
  }

  // ----- car page gallery + lightbox -----
  var gallery = document.querySelector('[data-gallery]');
  if (!gallery) return;

  var images = [];
  try { images = JSON.parse(gallery.getAttribute('data-images') || '[]'); } catch (e) {}
  if (!images.length) return;

  var main = gallery.querySelector('.main-photo img');
  var thumbs = Array.prototype.slice.call(gallery.querySelectorAll('.thumbs button'));
  var index = 0;

  function show(i) {
    index = (i + images.length) % images.length;
    main.src = images[index];
    thumbs.forEach(function (t, k) { t.classList.toggle('active', k === index); });
  }
  thumbs.forEach(function (t, k) { t.addEventListener('click', function () { show(k); }); });

  var box = document.createElement('div');
  box.className = 'lightbox';
  box.hidden = true;
  box.innerHTML =
    '<button class="lb-close" type="button" aria-label="Close">&times;</button>' +
    '<button class="lb-prev" type="button" aria-label="Previous">&#8249;</button>' +
    '<img alt="" draggable="false">' +
    '<button class="lb-next" type="button" aria-label="Next">&#8250;</button>' +
    '<div class="lb-count" aria-live="polite"></div>';
  document.body.appendChild(box);
  var lbImg = box.querySelector('img');
  var lbCount = box.querySelector('.lb-count');
  var many = images.length > 1;

  if (!many) {
    box.querySelector('.lb-prev').hidden = true;
    box.querySelector('.lb-next').hidden = true;
    lbCount.hidden = true;
  }

  function paint() {
    lbImg.src = images[index];
    lbCount.textContent = (index + 1) + ' / ' + images.length;
    if (many) {   // warm the cache so the next swipe is instant
      [index + 1, index - 1].forEach(function (k) { new Image().src = images[(k + images.length) % images.length]; });
    }
  }
  function resetImg() {
    lbImg.style.transition = 'none';
    lbImg.style.transform = '';
    lbImg.style.opacity = '';
    box.style.background = '';
  }
  function openBox() { resetImg(); paint(); box.hidden = false; document.body.classList.add('no-scroll'); }
  function closeBox() { box.hidden = true; document.body.classList.remove('no-scroll'); resetImg(); }
  function step(d) { show(index + d); paint(); }

  main.parentNode.addEventListener('click', openBox);
  box.querySelector('.lb-close').addEventListener('click', closeBox);
  box.querySelector('.lb-prev').addEventListener('click', function () { step(-1); });
  box.querySelector('.lb-next').addEventListener('click', function () { step(1); });
  box.addEventListener('click', function (e) { if (e.target === box) closeBox(); });
  document.addEventListener('keydown', function (e) {
    if (box.hidden) return;
    if (e.key === 'Escape') closeBox();
    if (e.key === 'ArrowRight') step(1);
    if (e.key === 'ArrowLeft') step(-1);
  });

  // ----- swipe: left/right = next/previous photo, down = close -----
  var g = null, animating = false;
  function snap() {
    lbImg.style.transition = 'transform .2s ease-out';
    lbImg.style.transform = '';
    box.style.background = '';
  }
  box.addEventListener('touchstart', function (e) {
    var zoomed = window.visualViewport && window.visualViewport.scale > 1.05;   // let pinch-zoomed photos pan normally
    if (animating || zoomed || e.touches.length !== 1 || e.target.closest('button')) { g = null; return; }
    g = {x: e.touches[0].clientX, y: e.touches[0].clientY, t: Date.now(), axis: null, dx: 0, dy: 0};
    lbImg.style.transition = 'none';
  }, {passive: true});
  box.addEventListener('touchmove', function (e) {
    if (!g) return;
    if (e.touches.length !== 1) { g = null; snap(); return; }
    e.preventDefault();   // iOS Safari otherwise scrolls the page behind and cancels the swipe
    g.dx = e.touches[0].clientX - g.x;
    g.dy = e.touches[0].clientY - g.y;
    if (!g.axis) {
      if (Math.abs(g.dx) < 8 && Math.abs(g.dy) < 8) return;
      g.axis = Math.abs(g.dx) > Math.abs(g.dy) ? 'x' : 'y';
    }
    if (g.axis === 'x' && many) {
      lbImg.style.transform = 'translateX(' + g.dx + 'px)';
    } else if (g.axis === 'y' && g.dy > 0) {
      lbImg.style.transform = 'translateY(' + g.dy + 'px)';
      box.style.background = 'rgba(10, 9, 8, ' + Math.max(0.35, 0.95 - g.dy / 600) + ')';
    }
  }, {passive: false});
  function finish(cancelled) {
    var s = g; g = null;
    if (!s || !s.axis) { if (cancelled) snap(); return; }
    var speed = Math.abs(s.dx) / Math.max(1, Date.now() - s.t);
    if (s.axis === 'x' && many && (Math.abs(s.dx) > 60 || (speed > 0.4 && Math.abs(s.dx) > 25))) {
      var dir = s.dx < 0 ? 1 : -1;           // swipe left shows the next photo
      animating = true;
      lbImg.style.transition = 'transform .16s ease-in, opacity .16s';
      lbImg.style.transform = 'translateX(' + (-dir * window.innerWidth * 0.7) + 'px)';
      lbImg.style.opacity = '0';
      setTimeout(function () {
        step(dir);
        lbImg.style.transition = 'none';
        lbImg.style.transform = 'translateX(' + (dir * window.innerWidth * 0.4) + 'px)';
        void lbImg.offsetWidth;              // apply the start position before animating in
        lbImg.style.transition = 'transform .2s ease-out, opacity .2s';
        lbImg.style.transform = '';
        lbImg.style.opacity = '1';
        setTimeout(function () { animating = false; }, 200);
      }, 160);
    } else if (!cancelled && s.axis === 'y' && s.dy > 100) {
      closeBox();
    } else {
      snap();
    }
  }
  box.addEventListener('touchend', function () { finish(false); });
  box.addEventListener('touchcancel', function () { finish(true); });
})();
