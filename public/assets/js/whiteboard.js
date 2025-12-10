// public/assets/js/whiteboard.js
// Lightweight collaborative whiteboard (strokes saved to server)
// Requires global ROOM_ID, USER_ID to be defined in room.php (we set these earlier)
(function () {
  if (!window.ROOM_ID) {
    console.warn('Whiteboard: ROOM_ID not defined; whiteboard disabled.');
    return;
  }

  const canvas = document.getElementById('wbCanvas');
  if (!canvas) {
    console.warn('Whiteboard: canvas not found.');
    return;
  }
  const ctx = canvas.getContext('2d', { alpha: true });
  const brushRange = document.getElementById('wbBrushSize');
  const colorInput = document.getElementById('wbColor');
  const undoBtn = document.getElementById('wbUndo');
  const clearBtn = document.getElementById('wbClear');
  const saveBtn = document.getElementById('wbSave');
  const exportBtn = document.getElementById('wbExport');
  const statusEl = document.getElementById('wbStatus');

  // internal state
  let drawing = false;
  let currentStroke = null;
  let strokes = [];      // array of stroke objects {color, size, points: [{x,y}, ...]}
  let undoStack = [];
  let lastSaveAt = 0;     // ms epoch — used for polling
  const autosaveInterval = 10000; // ms
  let autosaveTimer = null;

  // resize canvas to device pixel ratio for sharpness
  function resizeCanvas() {
    const rect = canvas.getBoundingClientRect();
    const ratio = window.devicePixelRatio || 1;

    // preserve existing strokes visually by redrawing after resizing
    canvas.width = Math.round(rect.width * ratio);
    canvas.height = Math.round(rect.height * ratio);

    // map canvas coordinate system so drawing logic can continue to use CSS pixels
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    redrawAll();
  }

  window.addEventListener('resize', resizeCanvas);
  resizeCanvas();

  function redrawAll() {
    // clear using full device pixels (transform is already set, but clearing with CSS pixel dims is fine)
    const rect = canvas.getBoundingClientRect();
    ctx.clearRect(0, 0, rect.width, rect.height);

    for (const s of strokes) {
      drawStroke(ctx, s);
    }
  }

  function drawStroke(ctx, s) {
    if (!s || !s.points || s.points.length === 0) return;
    ctx.lineJoin = 'round';
    ctx.lineCap = 'round';
    ctx.strokeStyle = s.color || '#fff';
    ctx.lineWidth = s.size || 3;
    ctx.beginPath();
    const p0 = s.points[0];
    ctx.moveTo(p0.x, p0.y);
    for (let i = 1; i < s.points.length; i++) {
      const p = s.points[i];
      ctx.lineTo(p.x, p.y);
    }
    ctx.stroke();
  }

  function getPointerPos(e) {
    const rect = canvas.getBoundingClientRect();
    // support pointer and touch events
    let clientX = e.clientX, clientY = e.clientY;
    if (e.touches && e.touches[0]) {
      clientX = e.touches[0].clientX;
      clientY = e.touches[0].clientY;
    }
    // fallback for PointerEvent
    if (typeof clientX === 'undefined' && e instanceof PointerEvent) {
      clientX = e.clientX;
      clientY = e.clientY;
    }
    return { x: clientX - rect.left, y: clientY - rect.top };
  }

  // pointer handlers
  function startPointer(e) {
    // prevent default to avoid scrolling on touch devices
    if (e.cancelable) e.preventDefault();
    drawing = true;
    const pos = getPointerPos(e);
    currentStroke = {
      color: (colorInput && colorInput.value) || '#ffffff',
      size: (brushRange && Number(brushRange.value)) || 3,
      points: [pos]
    };
    strokes.push(currentStroke);
    // starting a new action clears the redo/undo redo stack
    undoStack = [];
    redrawAll();
  }

  function movePointer(e) {
    if (!drawing || !currentStroke) return;
    const pos = getPointerPos(e);
    currentStroke.points.push(pos);
    // incremental drawing for snappy UI
    // draw only the current stroke to avoid redrawing everything
    drawStroke(ctx, currentStroke);
  }

  function endPointer(e) {
    if (!drawing) return;
    drawing = false;
    currentStroke = null;
    scheduleAutosave();
  }

  // Prefer Pointer Events (unified), fallback to touch for older browsers
  canvas.addEventListener('pointerdown', startPointer);
  canvas.addEventListener('pointermove', movePointer);
  canvas.addEventListener('pointerup', endPointer);
  canvas.addEventListener('pointercancel', endPointer);
  canvas.addEventListener('pointerleave', endPointer);

  // touch listeners kept for browsers without pointer events support
  canvas.addEventListener('touchstart', (ev) => {
    // avoid duplicate events on browsers supporting pointer events
    if (window.PointerEvent) return;
    startPointer(ev);
  }, { passive: false });
  canvas.addEventListener('touchmove', (ev) => {
    if (window.PointerEvent) return;
    movePointer(ev);
  }, { passive: false });
  canvas.addEventListener('touchend', (ev) => {
    if (window.PointerEvent) return;
    endPointer(ev);
  });

  // controls (ensure elements exist before wiring)
  if (undoBtn) {
    undoBtn.addEventListener('click', () => {
      if (!strokes.length) return;
      const s = strokes.pop();
      undoStack.push(s);
      redrawAll();
      scheduleAutosave();
    });
  }
  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      if (!confirm('Clear the whiteboard for everyone? This cannot be undone.')) return;
      strokes = [];
      undoStack = [];
      redrawAll();
      scheduleAutosave(true); // forced immediate save
    });
  }
  if (exportBtn) {
    exportBtn.addEventListener('click', () => {
      const dataURL = canvas.toDataURL('image/png');
      const a = document.createElement('a');
      a.href = dataURL;
      a.download = `whiteboard-room-${ROOM_ID}.png`;
      document.body.appendChild(a);
      a.click();
      a.remove();
    });
  }
  if (saveBtn) {
    saveBtn.addEventListener('click', () => { saveWhiteboard(); });
  }

  // autosave logic (simple throttle)
  function scheduleAutosave(force = false) {
    if (autosaveTimer) {
      clearTimeout(autosaveTimer);
      autosaveTimer = null;
    }
    if (force) {
      saveWhiteboard();
      return;
    }
    autosaveTimer = setTimeout(() => {
      saveWhiteboard();
    }, autosaveInterval);
  }

  // send JSON to server
  async function saveWhiteboard() {
    if (!statusEl) return;
    // if nothing to save, still send (maybe to create empty board)
    const payload = {
      room_id: ROOM_ID,
      user_id: USER_ID,
      strokes: strokes,
      meta: { saved_at: new Date().toISOString() }
    };

    statusEl.textContent = 'Saving…';
    try {
      const res = await fetch('api/save_whiteboard.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      // be defensive: ensure valid JSON response
      const text = await res.text();
      let data;
      try {
        data = JSON.parse(text);
      } catch (err) {
        statusEl.textContent = 'Save failed (invalid response)';
        console.error('Invalid JSON from save_whiteboard:', text);
        return;
      }

      if (!data.success) {
        statusEl.textContent = 'Save failed: ' + (data.error || 'Unknown');
        console.error(data);
      } else {
        // prefer server-provided timestamp if present
        if (data.saved_at_ms) {
          lastSaveAt = Number(data.saved_at_ms) || Date.now();
        } else if (data.meta && data.meta.saved_at) {
          const ms = Date.parse(data.meta.saved_at);
          lastSaveAt = isNaN(ms) ? Date.now() : ms;
        } else {
          lastSaveAt = Date.now();
        }
        statusEl.textContent = 'Saved at ' + new Date(lastSaveAt).toLocaleTimeString();
      }
    } catch (err) {
      statusEl.textContent = 'Save error';
      console.error(err);
    }
  }

  async function loadWhiteboard() {
    if (statusEl) statusEl.textContent = 'Loading whiteboard…';
    try {
      const res = await fetch(`api/load_whiteboard.php?room_id=${ROOM_ID}`, { credentials: 'same-origin' });
      const data = await res.json();
      if (!data.success) {
        if (statusEl) statusEl.textContent = 'No whiteboard yet';
        return;
      }
      // data.data is our result (see API)
      const d = data.data;
      if (!d) {
        if (statusEl) statusEl.textContent = 'Whiteboard empty';
        return;
      }
      strokes = Array.isArray(d.strokes) ? d.strokes : [];
      // set lastSaveAt to saved_at_ms if present, else parse saved_at, else now
      if (d.saved_at_ms) {
        lastSaveAt = Number(d.saved_at_ms) || Date.now();
      } else if (d.meta && d.meta.saved_at) {
        const ms = Date.parse(d.meta.saved_at);
        lastSaveAt = isNaN(ms) ? Date.now() : ms;
      } else {
        lastSaveAt = Date.now();
      }
      redrawAll();
      if (statusEl) statusEl.textContent = 'Loaded whiteboard (saved at ' + (d.meta?.saved_at || '-') + ')';
    } catch (err) {
      if (statusEl) statusEl.textContent = 'Load error';
      console.error(err);
    }
  }

  // initial load
  loadWhiteboard();

  // poll for remote updates every 8s
  setInterval(async () => {
    try {
      const res = await fetch(`api/load_whiteboard.php?room_id=${ROOM_ID}&since=${lastSaveAt || 0}`, { credentials: 'same-origin' });
      const data = await res.json();
      // if API opts to return success:true with updated:false when nothing is new
      if (data && data.success && data.updated === false) {
        return;
      }
      if (data && data.success && data.data) {
        const d = data.data;
        const remoteTS = d.saved_at_ms || Date.parse(d.meta?.saved_at || 0);
        if (remoteTS && remoteTS > (lastSaveAt || 0)) {
          strokes = Array.isArray(d.strokes) ? d.strokes : [];
          redrawAll();
          lastSaveAt = remoteTS;
          if (statusEl) statusEl.textContent = 'Updated from remote at ' + (d.meta?.saved_at || '-');
        }
      }
    } catch (err) {
      // ignore polling errors but log for debugging
      console.error('whiteboard poll error', err);
    }
  }, 8000);

})();
