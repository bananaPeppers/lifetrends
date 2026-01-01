<?php
// Simple dashboard front page for RateMyLife
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
        <div class="index-row">
          <div class="index-number" id="indexNumber">0</div>
          <div class="index-action">
            <button id="saveBtn" class="save-btn" onclick="saveValue()">Save</button>
          </div>
        </div>
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

      <div class="progress-label">Progress</div>
      <div class="year-progress" id="yearProgressCurrent" aria-hidden="false"></div>
    </section>

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

      <div class="progress-label">Progress</div>
      <div class="year-progress" id="yearProgressLastYear" aria-hidden="false"></div>
    </section>

    <section class="morestats-entry glass-card" id="moreStats">
      <div class="morestats-header">More stats</div>

      <div class="trend-block">
        <div class="trend-title">Trend</div>
        <div class="trend-main" id="stat_overall">—</div>
      </div>

      <div class="trend-grid">
        <div class="trend-row">
          <div class="trend-name">7-day</div>
          <div class="trend-value" id="stat_week_avg">—</div>
          <div class="trend-indicator" id="stat_week_delta">—</div>
        </div>

        <div class="trend-row">
          <div class="trend-name">30-day</div>
          <div class="trend-value" id="stat_month_avg">—</div>
          <div class="trend-indicator" id="stat_month_delta">—</div>
        </div>
      </div>

      <div class="today-delta" id="stat_yesterday_today">Yesterday: — → Today: — (—)</div>

      <div class="bestworst">
        <div class="bw-item">
          <div class="bw-title">Best</div>
          <div class="bw-row"><span class="bw-label" id="stat_best_label">—</span>
          <span class="bw-value" id="stat_best_value">—</span></div>
        </div>
        <div class="bw-item">
          <div class="bw-title">Worst</div>
          <div class="bw-row"><span class="bw-label" id="stat_worst_label">—</span>
          <span class="bw-value" id="stat_worst_value">—</span></div>
        </div>
      </div>
    </section>
  </main>

  <script src="../Assets/js/app.js"></script>
</body>
</html>
