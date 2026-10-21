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
    '<img alt="">' +
    '<button class="lb-next" type="button" aria-label="Next">&#8250;</button>';
  document.body.appendChild(box);
  var lbImg = box.querySelector('img');

  if (images.length < 2) {
    box.querySelector('.lb-prev').hidden = true;
    box.querySelector('.lb-next').hidden = true;
  }

  function openBox() { lbImg.src = images[index]; box.hidden = false; document.body.classList.add('no-scroll'); }
  function closeBox() { box.hidden = true; document.body.classList.remove('no-scroll'); }
  function step(d) { show(index + d); lbImg.src = images[index]; }

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
})();
