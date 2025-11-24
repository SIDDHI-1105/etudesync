/* public/assets/js/script.js
   Simple automatic background slider
*/
(function () {
  const images = [
    'assets/images/background1.jpg',
    'assets/images/background2.jpg',
    'assets/images/background3.jpg',
    'assets/images/background4.jpg'
  ];

  const duration = 4000; // ms (changed from 8000 to 4000 = 4 seconds)
  let idx = 0;

  const container = document.getElementById('bg-slider');
  if (!container) return;

  // create slide elements
  images.forEach((src, i) => {
    const el = document.createElement('div');
    el.className = 'slide';
    el.style.backgroundImage = `url('${src}')`;
    if (i === 0) el.classList.add('visible');
    container.appendChild(el);
  });

  const slides = container.querySelectorAll('.slide');

  setInterval(() => {
    slides[idx].classList.remove('visible');
    idx = (idx + 1) % slides.length;
    slides[idx].classList.add('visible');
  }, duration);
})();
