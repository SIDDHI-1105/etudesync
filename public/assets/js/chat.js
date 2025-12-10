// public/assets/js/chat.js
// Requires ROOM_ID and USER_ID globals
document.addEventListener('DOMContentLoaded', () => {
  const roomId = window.ROOM_ID;
  const userId = window.USER_ID;
  if (!roomId) return;

  const chatList = document.getElementById('chatList');
  const chatForm = document.getElementById('chatForm');
  const chatInput = document.getElementById('chatInput');
  const pollInterval = 2500; // ms
  let lastMessageId = 0;
  let fetching = false;

  // defend
  if (!chatList || !chatForm || !chatInput) {
    console.warn('Chat: missing DOM elements');
    return;
  }

  async function fetchMessages() {
    if (fetching) return;
    fetching = true;
    try {
      const url = `api/fetch_messages.php?room_id=${encodeURIComponent(roomId)}&since_id=${encodeURIComponent(lastMessageId)}`;
      const res = await fetch(url, { credentials: 'same-origin' });
      if (!res.ok) throw new Error('Network response not ok');
      const data = await res.json();
      if (!data.success) { fetching = false; return; }
      if (Array.isArray(data.messages) && data.messages.length) {
        appendMessages(data.messages);
      }
    } catch (e) {
      console.error('fetch error', e);
    } finally {
      fetching = false;
    }
  }

  function appendMessages(msgs) {
    // msgs assumed ascending order
    const pinnedArea = document.getElementById('pinnedArea');
    const pinnedList = document.getElementById('pinnedList');
    const newPinned = [];

    msgs.forEach(m => {
      const id = Number(m.message_id || m.id || 0);
      if (!id) return;
      if (id <= lastMessageId) return;
      lastMessageId = id;

      if (Number(m.is_pinned) === 1) {
        newPinned.push(m);
      }

      const row = document.createElement('div');
      row.className = 'chat-row' + (m.is_pinned ? ' pinned' : '');
      row.dataset.id = id;

      const avatar = document.createElement('img');
      avatar.className = 'chat-avatar';
      avatar.src = m.avatar_path || 'assets/images/profile-placeholder.png';
      avatar.alt = m.user_name || 'User';

      const body = document.createElement('div');
      body.className = 'chat-body';

      const meta = document.createElement('div');
      meta.className = 'chat-meta';
      meta.innerHTML = `<span class="chat-user">${escapeHtml(m.user_name || 'User')}</span>
                        <span class="chat-time">${new Date(m.created_at).toLocaleTimeString()}</span>
                        ${m.is_edited ? '<span class="chat-edited"> • edited</span>' : ''}`;
      const text = document.createElement('div');
      text.className = 'chat-text';
      text.textContent = m.message;

      body.appendChild(meta);
      body.appendChild(text);

      const actions = document.createElement('div');
      actions.className = 'chat-actions';

      // edit/delete only for the message owner
      if (Number(m.user_id) === Number(userId)) {
        const editBtn = document.createElement('button');
        editBtn.className = 'btn small outline chat-edit';
        editBtn.textContent = 'Edit';
        editBtn.addEventListener('click', () => startEdit(row, m));
        actions.appendChild(editBtn);

        const delBtn = document.createElement('button');
        delBtn.className = 'btn small outline chat-delete';
        delBtn.textContent = 'Delete';
        delBtn.addEventListener('click', () => deleteMessage(id));
        actions.appendChild(delBtn);
      }

      // allow pin/unpin to those allowed by server (we still show button if server supports)
      const pinBtn = document.createElement('button');
      pinBtn.className = 'btn small';
      pinBtn.textContent = Number(m.is_pinned) === 1 ? 'Unpin' : 'Pin';
      pinBtn.addEventListener('click', () => togglePin(id, Number(m.is_pinned) === 1));
      actions.appendChild(pinBtn);

      row.appendChild(avatar);
      row.appendChild(body);
      row.appendChild(actions);

      chatList.appendChild(row);
    });

    // pinned messages area handling (if exists)
    if (pinnedList && pinnedArea && newPinned.length) {
      const existingIds = Array.from(pinnedList.querySelectorAll('[data-id]')).map(n => Number(n.dataset.id));
      newPinned.forEach(p => {
        const pid = Number(p.message_id || p.id);
        if (existingIds.includes(pid)) return;
        const el = document.createElement('div');
        el.className = 'pinned-msg';
        el.dataset.id = pid;
        el.innerHTML = `<div class="pm-text">${escapeHtml(p.message)}</div>
                        <div class="pm-meta">${escapeHtml(p.user_name || '')} • ${new Date(p.created_at).toLocaleTimeString()}</div>`;
        // optional unpin for owner (we still call API; API decides permission)
        if (Number(p.user_id) === Number(userId)) {
          const unpin = document.createElement('button');
          unpin.className = 'btn small outline';
          unpin.textContent = 'Unpin';
          unpin.addEventListener('click', async () => {
            const fd = new FormData(); fd.append('message_id', pid); fd.append('pin', 0);
            const r = await fetch('api/pin_message.php', { method:'POST', body:fd, credentials:'same-origin' });
            const j = await r.json();
            if (j.success) {
              el.remove();
              const mainEl = document.querySelector(`.chat-row[data-id="${pid}"]`);
              if (mainEl) mainEl.classList.remove('pinned');
            } else alert(j.error || 'Unpin failed');
          });
          el.appendChild(unpin);
        }
        pinnedList.appendChild(el);
        pinnedArea.style.display = 'block';
      });
    }

    // autoscroll if near bottom
    const nearBottom = chatList.scrollTop + chatList.clientHeight + 120 >= chatList.scrollHeight;
    if (nearBottom) chatList.scrollTo({ top: chatList.scrollHeight, behavior: 'smooth' });
  }

  function escapeHtml(s){ return (''+s).replace(/[&<>"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]; }); }

  async function startEdit(rowEl, msg) {
    const textDiv = rowEl.querySelector('.chat-text');
    if (!textDiv) return;
    const original = msg.message;
    const input = document.createElement('textarea');
    input.value = original;
    input.style.width = '100%';
    textDiv.innerHTML = '';
    textDiv.appendChild(input);
    const saveBtn = document.createElement('button');
    saveBtn.className = 'btn small';
    saveBtn.textContent = 'Save';
    saveBtn.addEventListener('click', async () => {
      const newText = input.value.trim();
      if (!newText) return alert('Message cannot be empty');
      const fd = new FormData();
      fd.append('message_id', msg.message_id);
      fd.append('message', newText);
      const res = await fetch('api/edit_message.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      const d = await res.json();
      if (d.success) {
        textDiv.textContent = newText;
      } else alert('Edit failed: ' + (d.error||''));
    });
    textDiv.appendChild(saveBtn);
  }

  async function deleteMessage(id) {
    if (!confirm('Delete this message?')) return;
    const fd = new FormData();
    fd.append('message_id', id);
    const res = await fetch('api/delete_message.php', { method: 'POST', body: fd, credentials: 'same-origin' });
    const d = await res.json();
    if (d.success) {
      const el = document.querySelector(`.chat-row[data-id="${id}"]`);
      if (el) el.remove();
    } else alert('Delete failed: ' + (d.error||''));
  }

  async function togglePin(id, currentlyPinned) {
    const fd = new FormData();
    fd.append('message_id', id);
    fd.append('pin', currentlyPinned ? 0 : 1);
    const res = await fetch('api/pin_message.php', { method: 'POST', body: fd, credentials: 'same-origin' });
    const d = await res.json();
    if (d.success) {
      // quick refresh for simplicity
      fetchMessages();
    } else alert('Pin failed: ' + (d.error||''));
  }

  // send message handler
  chatForm.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const text = chatInput.value.trim();
    if (!text) return;
    const fd = new FormData();
    fd.append('room_id', roomId);
    fd.append('message', text);
    try {
      const res = await fetch('api/send_message.php', { method:'POST', body: fd, credentials:'same-origin' });
      const j = await res.json();
      if (j.success) {
        chatInput.value = '';
        // append immediately if server returns the new message
        if (j.message) {
          appendMessages([j.message]);
        } else {
          // otherwise trigger a fetch
          fetchMessages();
        }
      } else {
        alert(j.error || 'Send failed');
      }
    } catch (err) {
      console.error(err);
      alert('Send failed (network)');
    }
  });

  // initial load and poll loop
  fetchMessages(); // initial
  setInterval(fetchMessages, pollInterval);
});
