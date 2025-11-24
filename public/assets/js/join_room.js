// public/assets/js/join_room.js
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('joinRoomForm');
  const msg = document.getElementById('jrMsg');
  const submit = document.getElementById('submitJoin');

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    submit.disabled = true;
    msg.style.display = 'none';
    msg.textContent = '';

    const formData = new FormData(form);

    try {
      // explicit relative path (from join_room.php location)
      const res = await fetch('./api/join_room.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      });

      // Log status for debugging
      console.log('join_room.php status:', res.status, res.statusText);

      // Try to parse JSON; if the server returned HTML or an error page, parsing will fail
      let data;
      const text = await res.text();
      try {
        data = JSON.parse(text);
      } catch (parseErr) {
        console.error('join_room: response is not valid JSON. Raw response:', text);
        throw new Error('Server returned invalid JSON (see console).');
      }

      if (data.success) {
        msg.style.display = 'block';
        msg.style.color = '#bfffcf';
        msg.textContent = `Joined — redirecting to room...`;
        // redirect to room page (server returns room_id & room_code)
        setTimeout(() => {
          window.location.href = `room.php?room_id=${encodeURIComponent(data.room_id)}&code=${encodeURIComponent(data.room_code)}`;
        }, 700);
      } else {
        submit.disabled = false;
        msg.style.display = 'block';
        msg.style.color = '#ffb3b3';
        msg.textContent = data.error || 'Failed to join room.';
      }
    } catch (err) {
      submit.disabled = false;
      msg.style.display = 'block';
      msg.style.color = '#ffb3b3';
      // Surface a slightly more informative message in dev
      msg.textContent = 'Network error. Try again.';
      console.error('join_room error:', err);
    }
  });
});
