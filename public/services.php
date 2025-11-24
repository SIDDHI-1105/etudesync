<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header_public.php';
?>

<section class="page-hero" aria-labelledby="services-title">
  <h1 id="services-title">Services</h1>
  <p class="lead">ÉtudeSync provides tools to help students, teachers and professionals collaborate, learn, and stay focused — all from one tidy study desk.</p>

  <ul class="services-grid" role="list">
    <!-- CollabSphere -->
    <li class="service-card glass-card">
      <div class="card-inner">
        <div class="card-left">
          <h3 class="service-title">CollabSphere (Rooms)</h3>
          <ul class="service-points">
            <li>Create / list / join scheduled rooms (room code)</li>
            <li>Text-centric chat + file sharing (PDFs, images)</li>
            <li>Simple canvas whiteboard (save as PNG)</li>
            <li>Quick polls for host-driven decisions</li>
          </ul>
          <blockquote class="testimonial">“We finished our revision in 30 minutes — the room kept everyone focused.” — <strong>Priya, Student</strong></blockquote>
        </div>

        <div class="card-right">
          <img src="assets/images/icon-collabsphere.png" alt="CollabSphere icon" class="service-icon" />
        </div>
      </div>
    </li>

    <!-- InfoVault -->
    <li class="service-card glass-card">
      <div class="card-inner">
        <div class="card-left">
          <h3 class="service-title">InfoVault (Knowledge Hub)</h3>
          <ul class="service-points">
            <li>Upload/download PDFs & images with tags</li>
            <li>Organize with folders or tags & favorites</li>
            <li>Create flashcards & review mode (flip, mark)</li>
            <li>Fast search by tag / title</li>
          </ul>
          <blockquote class="testimonial">“All my notes in one place — searching is instant.” — <strong>Dr. Iyer, Tutor</strong></blockquote>
        </div>

        <div class="card-right">
          <img src="assets/images/icon-infovault.png" alt="InfoVault icon" class="service-icon" />
        </div>
      </div>
    </li>

    <!-- FocusFlow -->
    <li class="service-card glass-card">
      <div class="card-inner">
        <div class="card-left">
          <h3 class="service-title">FocusFlow (Productivity Zone)</h3>
          <ul class="service-points">
            <li>Pomodoro timer with persistence</li>
            <li>DB-backed to-do list + due dates</li>
            <li>Simple calendar & weekly study planner</li>
            <li>Progress tracker charts for motivation</li>
          </ul>
          <blockquote class="testimonial">“Pomodoro helped me get into flow — my focus improved.” — <strong>Rohit, Developer</strong></blockquote>
        </div>

        <div class="card-right">
          <img src="assets/images/icon-focusflow.png" alt="FocusFlow icon" class="service-icon" />
        </div>
      </div>
    </li>

    <!-- AssessArena -->
    <li class="service-card glass-card">
      <div class="card-inner">
        <div class="card-left">
          <h3 class="service-title">AssessArena (Quizzes)</h3>
          <ul class="service-points">
            <li>Teachers create MCQ quizzes with shareable codes</li>
            <li>Students take quizzes, get instant scores</li>
            <li>Result history + leaderboard per quiz</li>
            <li>Basic stats to track learning progress</li>
          </ul>
          <blockquote class="testimonial">“Quick quizzes helped me brush up before tests.” — <strong>Anya, University</strong></blockquote>
        </div>

        <div class="card-right">
          <img src="assets/images/icon-assessarena.png" alt="AssessArena icon" class="service-icon" />
        </div>
      </div>
    </li>

    <!-- MindSpace (Premium) -->
    <li class="service-card glass-card premium-card">
      <div class="card-inner">
        <div class="card-left">
          <h3 class="service-title">MindSpace (Wellness) <span class="premium-tag" aria-hidden="true">Premium</span></h3>
          <ul class="service-points">
            <li>Daily mood tracker (emoji) and private journal</li>
            <li>Habit tracker with simple calendar view</li>
            <li>Rotate motivational quotes from DB</li>
            <li>Small analytics for streaks (optional)</li>
          </ul>
          <blockquote class="testimonial">“A tiny space to reflect kept my study habits consistent.” — <strong>Maya, Teacher</strong></blockquote>
        </div>

        <div class="card-right">
          <img src="assets/images/icon-mindspace.png" alt="MindSpace icon" class="service-icon" />
        </div>
      </div>

      <div class="premium-badge" aria-hidden="true">🔒 Premium</div>
    </li>

    <!-- SocialHub (Premium) -->
    <li class="service-card glass-card premium-card">
      <div class="card-inner">
        <div class="card-left">
          <h3 class="service-title">SocialHub (Community)</h3>
          <ul class="service-points">
            <li>Join 7–21 day challenges (track progress)</li>
            <li>Earn coins & badges for milestones</li>
            <li>Mini single-player JS games & leaderboards</li>
            <li>Simple challenge join/track features</li>
          </ul>
          <blockquote class="testimonial">“Challenges kept me accountable — I loved the badges!” — <strong>Rahul, Student</strong></blockquote>
        </div>

        <div class="card-right">
          <img src="assets/images/icon-socialhub.png" alt="SocialHub icon" class="service-icon" />
        </div>
      </div>

      <div class="premium-badge" aria-hidden="true">🔒 Premium</div>
    </li>

  </ul>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
