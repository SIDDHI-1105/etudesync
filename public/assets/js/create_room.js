// assets/js/create_room.js
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('createRoomForm');
  const msg = document.getElementById('crMsg');
  const submit = document.getElementById('submitCreate');

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    submit.disabled = true;
    msg.style.display = 'none';
    msg.textContent = '';

    const formData = new FormData(form);

    try {
      const res = await fetch('api/create_room.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      });
      const data = await res.json();

      if (data.success) {
        msg.style.display = 'block';
        msg.style.color = '#bfffcf';
        msg.textContent = `Room created — code: ${data.room_code}. Redirecting...`;
        // short delay then redirect to room page
        setTimeout(() => {
          window.location.href = `room.php?room_id=${encodeURIComponent(data.room_id)}&code=${encodeURIComponent(data.room_code)}`;
        }, 900);
      } else {
        submit.disabled = false;
        msg.style.display = 'block';
        msg.style.color = '#ffb3b3';
        msg.textContent = data.error || 'Failed to create room.';
      }
    } catch (err) {
      submit.disabled = false;
      msg.style.display = 'block';
      msg.style.color = '#ffb3b3';
      msg.textContent = 'Network error. Try again.';
      console.error(err);
    }
  });
});
