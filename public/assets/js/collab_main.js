// assets/js/collab_main.js
// small UX helpers for CollabSphere landing

document.addEventListener('DOMContentLoaded', function () {
  // subtle entrance animation
  const cards = document.querySelectorAll('.action-card');
  cards.forEach((c, i) => {
    c.style.transform = 'translateY(18px) scale(.985)';
    c.style.opacity = 0;
    setTimeout(() => {
      c.style.transition = 'transform .45s cubic-bezier(.2,.9,.2,1), opacity .45s';
      c.style.transform = 'translateY(0) scale(1)';
      c.style.opacity = 1;
    }, 80 * i + 120);
  });

  // keyboard: press C -> create, J -> join, H -> history
  document.addEventListener('keydown', function (e) {
    if (e.key === 'c' || e.key === 'C') {
      window.location.href = 'create_room.php';
    } else if (e.key === 'j' || e.key === 'J') {
      window.location.href = 'join_room.php';
    } else if (e.key === 'h' || e.key === 'H') {
      window.location.href = 'room_history.php';
    }
  });
});
