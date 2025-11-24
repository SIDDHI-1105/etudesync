// public/assets/js/files.js
document.addEventListener('DOMContentLoaded', () => {
  const uploadForm = document.getElementById('fileUploadForm');
  const fileInput = document.getElementById('fileInput');
  const filesList = document.getElementById('filesList');
  const roomId = uploadForm ? uploadForm.dataset.room : null;
  if (!uploadForm || !filesList || !roomId) return;

  async function fetchFiles() {
    try {
      const res = await fetch(`api/fetch_files.php?room_id=${roomId}`, { credentials: 'same-origin' });
      const data = await res.json();
      if (!data.success) return;
      renderFiles(data.files || []);
    } catch (err) { console.error(err); }
  }

  function renderFiles(files) {
    filesList.innerHTML = '';
    if (!files.length) {
      filesList.innerHTML = '<div style="color:var(--muted)">No files uploaded yet.</div>';
      return;
    }
    files.forEach(f => {
      const row = document.createElement('div');
      row.className = 'file-row glass-card';
      row.innerHTML = `
        <div class="file-meta">
          <div class="file-name">${escapeHtml(f.orig_name)}</div>
          <div class="file-sub">${escapeHtml(f.user_name || 'Unknown')} • ${f.size_readable} • ${new Date(f.uploaded_at).toLocaleString()}</div>
        </div>
        <div class="file-actions">
          <a class="btn small outline download-btn" href="${escapeHtml('/' + f.file_path)}" download="${escapeHtml(f.orig_name)}">Download</a>
          <button class="btn small pin-btn">${f.is_pinned == 1 ? 'Unpin' : 'Pin'}</button>
          <button class="btn small outline delete-btn">Delete</button>
        </div>
      `;
      // actions
      const pinBtn = row.querySelector('.pin-btn');
      pinBtn.addEventListener('click', async () => {
        try {
          const fd = new FormData(); fd.append('file_id', f.file_id); fd.append('pin', f.is_pinned == 1 ? 0 : 1);
          const r = await fetch('api/pin_file.php', { method:'POST', body: fd, credentials: 'same-origin' });
          const j = await r.json();
          if (j.success) fetchFiles();
          else alert(j.error || 'Pin failed');
        } catch(e){console.error(e);}
      });

      const delBtn = row.querySelector('.delete-btn');
      delBtn.addEventListener('click', async () => {
        if (!confirm('Delete this file?')) return;
        try {
          const fd = new FormData(); fd.append('file_id', f.file_id);
          const r = await fetch('api/delete_file.php', { method:'POST', body: fd, credentials:'same-origin' });
          const j = await r.json();
          if (j.success) fetchFiles();
          else alert(j.error || 'Delete failed');
        } catch(e){console.error(e);}
      });

      filesList.appendChild(row);
    });
  }

  uploadForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!fileInput.files.length) return alert('Choose a file');
    const fd = new FormData();
    fd.append('room_id', roomId);
    fd.append('file', fileInput.files[0]);
    const btn = uploadForm.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Uploading…';
    try {
      const res = await fetch('api/upload_file.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      const data = await res.json();
      if (data.success) {
        fileInput.value = '';
        fetchFiles();
      } else {
        alert('Upload failed: ' + (data.error || ''));
      }
    } catch (err) { console.error(err); alert('Upload error'); }
    btn.disabled = false;
    btn.textContent = 'Upload';
  });

  function escapeHtml(s) {
    if (!s) return '';
    return String(s).replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  }

  // initial load
  fetchFiles();
  // refresh every 8s to show new uploads/pins
  setInterval(fetchFiles, 8000);
});
