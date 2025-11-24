// public/assets/js/animations.js
document.addEventListener('DOMContentLoaded', () => {
  // simple reveal
  const io = new IntersectionObserver((entries) => {
    entries.forEach(en => {
      if (en.isIntersecting) {
        en.target.classList.add('is-visible');
        io.unobserve(en.target);
      }
    });
  }, { threshold: 0.15 });

  document.querySelectorAll('.reveal').forEach(el => io.observe(el));

  // hero micro animation
  const hero = document.querySelector('.hero h1, .hero-inner h1');
  if (hero) {
    hero.style.transform = 'translateY(8px) scale(.99)';
    setTimeout(() => hero.style.transition = 'transform .6s cubic-bezier(.22,.9,.35,1)', 10);
    setTimeout(() => hero.style.transform = 'none', 80);
  }

  // If GSAP available, run a couple of niceties
  if (window.gsap && window.ScrollTrigger) {
    gsap.registerPlugin(ScrollTrigger);
    gsap.utils.toArray('.card, .module-card, .testimonial').forEach((el, i) => {
      gsap.from(el, {
        y: 28, opacity: 0, duration: .8, delay: i * .06, ease: 'power3.out',
        scrollTrigger: { trigger: el, start: 'top 85%' }
      });
    });
  }
});
