<?php
// Simple dashboard front page for RateMyLife
// Shows today's date and a set of questions with agree/disagree sliders
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>RateMyLife — Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../Assets/css/style.css">
</head>
<body>
  <main class="app-container">
    <div id="liveDateTime" class="live-datetime" aria-live="polite" aria-atomic="true"></div>
    

    <section class="index-entry glass-card">
      <label class="index-label">Today's Index:</label>
      <div class="index-input">
        <input type="range" id="todayIndex" min="-10" max="10" value="0" step="1">
        <div class="index-number" id="indexNumber">0</div>
      </div>
      <div class="index-action">
        <button id="saveBtn" class="save-btn" onclick="saveValue()">Save</button>
      </div>
    </section>

    <section class="averages-entry glass-card">
      <div class="avg-label">Current index status</div>
      <div class="avg-panels">
        <div class="avg-panel">
          <div class="avg-title">Week</div>
          <div class="avg-value" id="avgWeek">—</div>
        </div>
        <div class="avg-panel">
          <div class="avg-title">Month</div>
          <div class="avg-value" id="avgMonth">—</div>
        </div>
        <div class="avg-panel">
          <div class="avg-title">Year</div>
          <div class="avg-value" id="avgYear">—</div>
        </div>
      </div>
    </section>

    <!-- previous period bubble removed -->

    <section class="lastyear-entry glass-card">
      <div class="avg-label">This time last year</div>
      <div class="avg-panels">
        <div class="avg-panel">
          <div class="avg-title">Month</div>
          <div class="avg-value" id="ly_avgMonth">—</div>
        </div>
        <div class="avg-panel">
          <div class="avg-title">Week</div>
          <div class="avg-value" id="ly_avgWeek">—</div>
        </div>
        <div class="avg-panel">
          <div class="avg-title">Day</div>
          <div class="avg-value" id="ly_avgDay">—</div>
        </div>
      </div>
    </section>
  </main>

  <script src="../Assets/js/app.js"></script>
</body>
</html>
