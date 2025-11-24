// polls.js
document.addEventListener("DOMContentLoaded", () => {
  const pollBox = document.getElementById("pollArea");
  if (!pollBox) return;

  const roomId = pollBox.dataset.room;

  // Fetch polls
  function loadPolls() {
    fetch(`api/fetch_polls.php?room_id=${roomId}`)
      .then(r => r.json())
      .then(d => {
        if (!d.success) return;
        renderPolls(d.polls);
      });
  }

  function renderPolls(polls) {
    pollBox.innerHTML = "";
    polls.forEach(p => {
      const card = document.createElement("div");
      card.className = "poll-card glass";

      const q = document.createElement("h3");
      q.textContent = p.question;

      const list = document.createElement("div");
      p.results.forEach((o, i) => {
        const row = document.createElement("div");
        row.className = "poll-option";

        row.innerHTML = `
            <button data-poll="${p.poll_id}" data-index="${i}" class="vote-btn">
                ${o.text}
            </button>
            <span class="vote-count">${o.votes} votes</span>
        `;
        list.appendChild(row);
      });

      card.appendChild(q);
      card.appendChild(list);
      pollBox.appendChild(card);
    });
  }

  // Handle vote clicks
  document.body.addEventListener("click", (e) => {
    if (e.target.classList.contains("vote-btn")) {
      let pollId = e.target.dataset.poll;
      let idx = e.target.dataset.index;

      fetch("api/vote_poll.php", {
        method: "POST",
        body: new FormData(Object.assign(new FormData(), {
          poll_id: pollId,
          option_index: idx
        }))
      }).then(() => loadPolls());
    }
  });

  // Poll every 5 seconds
  setInterval(loadPolls, 5000);
  loadPolls();
});
