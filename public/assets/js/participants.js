// public/assets/js/participants.js
document.addEventListener('DOMContentLoaded', () => {
  const panel = document.getElementById('participantsPanel');
  if (!panel) return;

  const roomId = panel.dataset.room;
  const currentUserId = Number(panel.dataset-me);

  const listEl = document.getElementById('participantsList');
  const refreshInterval = 6000;
  const heartbeatInterval = 20000;

  async function announcePresence() {
    try {
      const fd = new FormData();
      fd.append('room_id', roomId);
      await fetch('api/announce_presence.php', { method: 'POST', body: fd, credentials: 'same-origin' });
    } catch (e) { /* ignore */ }
  }

  async function loadParticipants() {
    try {
      const res = await fetch(`api/fetch_participants.php?room_id=${roomId}`, { credentials: 'same-origin' });
      const data = await res.json();
      if (!data.success) return;
      renderParticipants(data.participants || []);
    } catch (e) { console.error(e); }
  }

  function renderParticipants(parts) {
    listEl.innerHTML = '';
    parts.forEach(p => {
      const li = document.createElement('div');
      li.className = 'participant-row';

      const avatar = document.createElement('img');
      avatar.className = 'participant-avatar';
      avatar.src = p.avatar ? p.avatar : '/assets/images/profile-placeholder.png';
      avatar.alt = p.name || 'User';

      const info = document.createElement('div');
      info.className = 'participant-info';
      info.innerHTML = `<div class="pname">${escapeHtml(p.name)}</div>
                        <div class="prole">${p.role} ${p.active ? ' • online' : ''}</div>`;

      li.appendChild(avatar);
      li.appendChild(info);

      // actions (if current user is host or moderator show actions for others)
      if (panel.dataset.canManage === '1' && Number(currentUserId) !== Number(p.user_id)) {
        const actions = document.createElement('div');
        actions.className = 'participant-actions';

        // Kick button
        const kickBtn = document.createElement('button');
        kickBtn.className = 'btn small outline';
        kickBtn.textContent = 'Kick';
        kickBtn.addEventListener('click', () => doKick(p.user_id));
        actions.appendChild(kickBtn);

        // Role dropdown
        const roleSel = document.createElement('select');
        roleSel.innerHTML = `<option value="participant">Participant</option>
                             <option value="moderator">Moderator</option>
                             <option value="host">Host</option>`;
        roleSel.value = p.role || 'participant';
        roleSel.addEventListener('change', () => changeRole(p.user_id, roleSel.value));
        actions.appendChild(roleSel);

        li.appendChild(actions);
      }

      listEl.appendChild(li);
    });
  }

  async function doKick(userId) {
    if (!confirm('Kick this participant?')) return;
    const fd = new FormData();
    fd.append('room_id', roomId);
    fd.append('user_id', userId);
    const res = await fetch('api/kick_participant.php', { method: 'POST', body: fd, credentials: 'same-origin' });
    const data = await res.json();
    if (data.success) loadParticipants();
    else alert('Kick failed: ' + (data.error||''));
  }

  async function changeRole(userId, newRole) {
    if (!confirm('Change role for this participant to '+newRole+'?')) return;
    const fd = new FormData();
    fd.append('room_id', roomId);
    fd.append('user_id', userId);
    fd.append('role', newRole);
    const res = await fetch('api/promote_participant.php', { method: 'POST', body: fd, credentials: 'same-origin' });
    const data = await res.json();
    if (data.success) loadParticipants();
    else alert('Role change failed: ' + (data.error||''));
  }

  // small helper to escape HTML
  function escapeHtml(s) {
    if (!s) return '';
    return String(s).replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  }

  // schedule polling & heartbeat
  announcePresence();
  loadParticipants();
  setInterval(loadParticipants, refreshInterval);
  setInterval(announcePresence, heartbeatInterval);
});
