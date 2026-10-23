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
  var slide = '<div class="lb-slide"><img class="lb-thumb" alt="" draggable="false"><img class="lb-full" alt="" draggable="false"></div>';
  box.innerHTML =
    '<div class="lb-stage">' + slide + slide + slide + '</div>' +
    '<button class="lb-close" type="button" aria-label="Close">&times;</button>' +
    '<button class="lb-prev" type="button" aria-label="Previous">&#8249;</button>' +
    '<button class="lb-next" type="button" aria-label="Next">&#8250;</button>' +
    '<div class="lb-count" aria-live="polite"></div>';
  document.body.appendChild(box);

  var stage = box.querySelector('.lb-stage');
  var slides = Array.prototype.slice.call(box.querySelectorAll('.lb-slide'));
  var lbCount = box.querySelector('.lb-count');
  var many = images.length > 1;
  var order = [0, 1, 2];       // order[role] = which slide element is previous (0), current (1) or next (2)
  var animating = false;

  if (!many) {
    box.querySelector('.lb-prev').hidden = true;
    box.querySelector('.lb-next').hidden = true;
    lbCount.hidden = true;
  }

  function width() { return box.clientWidth || window.innerWidth; }
  function wrap(i) { return (i + images.length) % images.length; }

  // Small previews (already on the page) show instantly; the full photo fades in over them.
  var tiny = images.map(function (u, k) {
    var t = thumbs[k] && thumbs[k].querySelector('img');
    return (t && t.getAttribute('src')) || u;
  });
  // Only ever give a slide new pictures while it is off-screen, so nothing flashes.
  function setSrc(el, i) {
    var url = images[i];
    var th = el.querySelector('.lb-thumb'), full = el.querySelector('.lb-full');
    if (full.getAttribute('data-u') === url) return;
    full.setAttribute('data-u', url);
    th.src = tiny[i];
    full.style.transition = 'none';
    full.style.opacity = '0';
    full.onload = function () {
      if (full.getAttribute('data-u') !== url) return;
      full.style.transition = 'opacity .18s ease-out';
      full.style.opacity = '1';
    };
    full.src = url;
    if (full.complete && full.naturalWidth) full.onload();
    if (full.decode) full.decode().catch(function () {});
  }
  // Keep a few photos in each direction downloading ahead of the swipe.
  var warm = {};
  function warmUrl(u) {
    if (warm[u]) return;
    var im = new Image();
    im.src = u;
    if (im.decode) im.decode().catch(function () {});
    warm[u] = im;                            // keep a reference so the browser does not drop it
  }
  function warmAround() {
    if (!many) return;
    tiny.forEach(warmUrl);                   // every preview is small
    [1, -1, 2, -2, 3, -3].forEach(function (k) { warmUrl(images[wrap(index + k)]); });
  }
  function place(dx, animate) {
    for (var r = 0; r < 3; r++) {
      var el = slides[order[r]];
      el.style.transition = animate ? 'transform .22s ease-out' : 'none';
      el.style.transform = 'translate3d(' + ((r - 1) * width() + dx) + 'px,0,0)';
    }
  }
  function paint() {
    setSrc(slides[order[1]], index);
    setSrc(slides[order[0]], wrap(index - 1));
    setSrc(slides[order[2]], wrap(index + 1));
    slides[order[0]].hidden = slides[order[2]].hidden = !many;
    lbCount.textContent = (index + 1) + ' / ' + images.length;
    warmAround();
  }
  function resetStage() {
    stage.style.transition = 'none';
    stage.style.transform = '';
    box.style.background = '';
  }
  function openBox() {
    resetStage();
    paint();
    place(0, false);
    box.hidden = false;
    document.body.classList.add('no-scroll');
  }
  function closeBox() {
    box.hidden = true;
    document.body.classList.remove('no-scroll');
    resetStage();
    place(0, false);
  }
  // d = +1 next photo, -1 previous photo
  function go(d) {
    if (animating || !many) return;
    animating = true;
    show(index + d);                         // page behind + thumbnails
    place(-d * width(), true);               // slide the neighbour in
    setTimeout(function () {
      order = d > 0 ? [order[1], order[2], order[0]] : [order[2], order[0], order[1]];
      paint();                               // only the recycled, off-screen slide gets a new src
      place(0, false);
      animating = false;
    }, 240);
  }

  main.parentNode.addEventListener('click', openBox);
  box.querySelector('.lb-close').addEventListener('click', closeBox);
  box.querySelector('.lb-prev').addEventListener('click', function () { go(-1); });
  box.querySelector('.lb-next').addEventListener('click', function () { go(1); });
  box.addEventListener('click', function (e) {
    if (!e.target.closest('img') && !e.target.closest('button')) closeBox();   // tap on the dark background
  });
  document.addEventListener('keydown', function (e) {
    if (box.hidden) return;
    if (e.key === 'Escape') closeBox();
    if (e.key === 'ArrowRight') go(1);
    if (e.key === 'ArrowLeft') go(-1);
  });
  window.addEventListener('resize', function () { if (!box.hidden && !animating) place(0, false); });

  // ----- swipe: left/right = next/previous photo, down = close -----
  var g = null;
  function snap() {
    place(0, true);
    stage.style.transition = 'transform .2s ease-out';
    stage.style.transform = '';
    box.style.background = '';
  }
  box.addEventListener('touchstart', function (e) {
    var zoomed = window.visualViewport && window.visualViewport.scale > 1.05;   // let a pinch-zoomed photo pan normally
    if (animating || zoomed || e.touches.length !== 1 || e.target.closest('button')) { g = null; return; }
    g = {x: e.touches[0].clientX, y: e.touches[0].clientY, t: Date.now(), axis: null, dx: 0, dy: 0};
    stage.style.transition = 'none';
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
      place(g.dx, false);
    } else if (g.axis === 'y' && g.dy > 0) {
      stage.style.transform = 'translateY(' + g.dy + 'px)';
      box.style.background = 'rgba(10, 9, 8, ' + Math.max(0.35, 0.98 - g.dy / 600) + ')';
    }
  }, {passive: false});
  function finish(cancelled) {
    var s = g; g = null;
    if (!s || !s.axis) { if (cancelled) snap(); return; }
    var speed = Math.abs(s.dx) / Math.max(1, Date.now() - s.t);
    if (s.axis === 'x' && many && (Math.abs(s.dx) > 60 || (speed > 0.4 && Math.abs(s.dx) > 25))) {
      go(s.dx < 0 ? 1 : -1);                 // swipe left shows the next photo
    } else if (!cancelled && s.axis === 'y' && s.dy > 100) {
      closeBox();
    } else {
      snap();
    }
  }
  box.addEventListener('touchend', function () { finish(false); });
  box.addEventListener('touchcancel', function () { finish(true); });
})();
