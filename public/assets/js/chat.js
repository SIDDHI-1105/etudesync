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

  async function fetchMessages() {
    if (fetching) return;
    fetching = true;
    try {
      const url = `api/fetch_messages.php?room_id=${roomId}&since_id=${lastMessageId}`;
      const res = await fetch(url, { credentials: 'same-origin' });
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
  // msgs is ascending
  const pinnedArea = document.getElementById('pinnedArea');
  const pinnedList = document.getElementById('pinnedList');
  const newPinned = [];

  msgs.forEach(m => {
    const id = Number(m.message_id);
    if (id <= lastMessageId) return;
    lastMessageId = id;

    if (m.is_pinned == 1) {
      newPinned.push(m);
    }

    const row = document.createElement('div');
    row.className = 'chat-row' + (m.is_pinned ? ' pinned' : '');
    row.dataset.id = id;

    const avatar = document.createElement('img');
    avatar.className = 'chat-avatar';
    avatar.src = m.avatar_path || 'assets/images/profile-placeholder.png';

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
    if (Number(m.user_id) === Number(userId)) {
      const editBtn = document.createElement('button');
      editBtn.className = 'btn small outline chat-edit';
      editBtn.textContent = 'Edit';
      editBtn.addEventListener('click', () => startEdit(row, m));
      actions.appendChild(editBtn);

      const delBtn = document.createElement('button');
      delBtn.className = 'btn small outline';
      delBtn.textContent = 'Delete';
      delBtn.addEventListener('click', () => deleteMessage(id));
      actions.appendChild(delBtn);
    }

    if (Number(m.user_id) === Number(userId)) {
      const pinBtn = document.createElement('button');
      pinBtn.className = 'btn small';
      pinBtn.textContent = m.is_pinned ? 'Unpin' : 'Pin';
      pinBtn.addEventListener('click', () => togglePin(id, m.is_pinned));
      actions.appendChild(pinBtn);
    }

    row.appendChild(avatar);
    row.appendChild(body);
    row.appendChild(actions);

    chatList.appendChild(row);
  });

  // handle pinned messages area
  if (pinnedList && pinnedArea) {
    // add new pinned to top (avoid duplicates)
    const existingIds = Array.from(pinnedList.querySelectorAll('[data-id]')).map(n => n.dataset.id);
    newPinned.forEach(p => {
      if (existingIds.includes(String(p.message_id))) return;
      const el = document.createElement('div');
      el.className = 'pinned-msg';
      el.dataset.id = p.message_id;
      el.innerHTML = `<div class="pm-text">${escapeHtml(p.message)}</div>
                      <div class="pm-meta">${escapeHtml(p.user_name || '')} • ${new Date(p.created_at).toLocaleTimeString()}</div>`;
      // allow unpin if owner
      if (Number(p.user_id) === Number(userId)) {
        const unpin = document.createElement('button');
        unpin.className = 'btn small outline';
        unpin.textContent = 'Unpin';
        unpin.addEventListener('click', async () => {
          const fd = new FormData(); fd.append('message_id', p.message_id); fd.append('pin', 0);
          const r = await fetch('api/pin_message.php', { method:'POST', body:fd, credentials:'same-origin' });
          const j = await r.json();
          if (j.success) {
            el.remove();
            // also unpin message in main list
            const mainEl = document.querySelector(`.chat-row[data-id="${p.message_id}"]`);
            if (mainEl) mainEl.classList.remove('pinned');
          } else alert(j.error || 'Unpin failed');
        });
        el.appendChild(unpin);
      }
      pinnedList.appendChild(el);
      pinnedArea.style.display = 'block';
    });
  }

  // smooth-scroll to bottom when new messages appended, only if user is near bottom
  const nearBottom = chatList.scrollTop + chatList.clientHeight + 120 >= chatList.scrollHeight;
  if (nearBottom) chatList.scrollTo({ top: chatList.scrollHeight, behavior: 'smooth' });
}


  async function startEdit(rowEl, msg) {
    const textDiv = rowEl.querySelector('.chat-text');
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
      // refresh messages
      fetchMessages();
    } else alert('Pin failed: ' + (d.error||''));
  }

  // poll loop
  fetchMessages(); // initial
  setInterval(fetchMessages, pollInterval);
});
