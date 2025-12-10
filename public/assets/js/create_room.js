// public/assets/js/create_room.js
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('createRoomForm');
  const btn = document.getElementById('submitCreate');
  const msg = document.getElementById('crMsg');

  if (!form || !btn || !msg) return;

  function setMessage(text, kind = 'info') {
    msg.style.display = text ? 'block' : 'none';
    msg.textContent = text || '';
    if (kind === 'error') msg.style.color = '#ffb3b3';
    else if (kind === 'success') msg.style.color = '#bfffbf';
    else msg.style.color = 'rgba(255,255,255,0.9)';
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    // simple validation
    const title = form.querySelector('[name="title"]').value.trim();
    if (!title) {
      setMessage('Please enter a room title.', 'error');
      return;
    }

    btn.disabled = true;
    const originalBtnText = btn.textContent;
    btn.textContent = 'Creating…';
    setMessage('');

    try {
      // Use URLSearchParams to send form-encoded data
      const formData = new FormData(form);
      const body = new URLSearchParams();
      for (const pair of formData.entries()) body.append(pair[0], pair[1]);

      const resp = await fetch(form.action, {
        method: (form.method || 'POST').toUpperCase(),
        headers: {
          'Accept': 'application/json',
          // don't set Content-Type when sending URLSearchParams; browser sets it
        },
        body: body
      });

      if (!resp.ok) {
        // If server returns HTML error page, read it for debugging
        const text = await resp.text();
        console.error('Non-OK response from create_room:', resp.status, text);
        setMessage('Server error while creating room. Check console/network.', 'error');
        btn.disabled = false;
        btn.textContent = originalBtnText;
        return;
      }

      const data = await resp.json().catch(() => null);
      if (!data) {
        setMessage('Invalid server response. Expected JSON.', 'error');
        console.error('Invalid JSON from create_room');
        btn.disabled = false;
        btn.textContent = originalBtnText;
        return;
      }

      if (data.success) {
        // expected server payload: { success: true, room_code: 'ABC123', url: 'room.php?code=ABC123' }
        setMessage(`Room created — code: ${data.room_code || ''}`, 'success');
        // a) If server returns a url, redirect there after a short delay
        if (data.url) {
          setTimeout(() => {
            window.location.href = data.url;
          }, 900);
          return;
        }
        // b) else keep message and enable copy button (optional)
        btn.disabled = false;
        btn.textContent = 'Create Another';
        return;
      } else {
        // show server-provided message if any
        setMessage(data.message || 'Could not create room.', 'error');
        btn.disabled = false;
        btn.textContent = originalBtnText;
      }
    } catch (err) {
      console.error('Error creating room:', err);
      setMessage('Network error. Check your connection and try again.', 'error');
      btn.disabled = false;
      btn.textContent = originalBtnText;
    }
  });

  // If your button is type="button" (not submit), forward clicks to submit the form:
  btn.addEventListener('click', () => form.requestSubmit ? form.requestSubmit() : form.submit());
});
