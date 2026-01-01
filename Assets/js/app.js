// Frontend JS for RateMyLife dashboard
document.addEventListener("DOMContentLoaded", function () {
  const todayIndex = document.getElementById("todayIndex");
  const saveBtn = document.getElementById("saveBtn");
  const indexNumber = document.getElementById("indexNumber");

  // Live date/time display (minutes only)
  function initLiveDateTime() {
    const container = document.getElementById("liveDateTime");
    if (!container) return;

    const datePart = document.createElement("span");
    datePart.className = "date-part";
    const timePart = document.createElement("span");
    timePart.className = "time-part";

    container.appendChild(datePart);
    container.appendChild(timePart);

    function formatDate(d) {
      return d.toLocaleDateString(undefined, {
        weekday: "long",
        month: "long",
        day: "numeric",
        year: "numeric",
      });
    }

    function formatHM(d) {
      // 12-hour clock with AM/PM (e.g. "5:42 PM")
      return d.toLocaleTimeString(undefined, {
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
      });
    }

    function tick() {
      const now = new Date();
      datePart.textContent = formatDate(now);
      timePart.textContent = formatHM(now);
    }

    tick();
    const now = new Date();
    const msToNextMinute =
      (60 - now.getSeconds()) * 1000 - now.getMilliseconds();

    setTimeout(() => {
      tick();
      setInterval(tick, 60000);
    }, msToNextMinute);
  }

  initLiveDateTime();

  function updateIndexDisplay() {
    const val = Number(todayIndex.value);
    if (indexNumber) indexNumber.textContent = String(val);
  }

  todayIndex.addEventListener("input", updateIndexDisplay);

  todayIndex.value = 0;
  updateIndexDisplay();

  saveBtn.addEventListener("click", async () => {
    const payload = { index: Number(todayIndex.value) };

    saveBtn.classList.add("clicked");
    saveBtn.disabled = true;

    const noticeId = "glassNotice";
    let notice = document.getElementById(noticeId);
    if (!notice) {
      notice = document.createElement("div");
      notice.id = noticeId;
      notice.className = "glass-notice";
      notice.setAttribute("role", "status");
      notice.setAttribute("aria-live", "polite");
      document.body.appendChild(notice);
    }

    try {
      const res = await fetch("save_index.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      const data = await res.json();

      if (res.ok && data.success) {
        notice.textContent = `Day recorded — ${payload.index}`;
        try {
          loadMedians();
        } catch (e) {
          /* ignore */
        }
      } else {
        notice.textContent = `Save failed: ${data.message || res.statusText}`;
      }
    } catch (err) {
      notice.textContent = `Save error: ${err.message}`;
    } finally {
      requestAnimationFrame(() => notice.classList.add("show"));
      clearTimeout(notice._hideTimer);
      notice._hideTimer = setTimeout(() => {
        notice.classList.remove("show");
      }, 2400);

      setTimeout(() => {
        saveBtn.classList.remove("clicked");
        saveBtn.disabled = false;
      }, 300);
    }
  });

  // Fetch computed happiness and populate UI
  async function loadMedians() {
    try {
      const res = await fetch("compute_happiness.php");
      if (!res.ok) throw new Error(res.statusText);

      const json = await res.json();
      if (!json.success)
        throw new Error(json.message || "Failed to load happiness");

      const weekly = json.weekly || {};
      const monthly = json.monthly || {};
      const yearly = json.yearly || {};
      const daily = json.daily || {};

      const current = json.current || {};
      const lastYearCurrent = json.lastYearCurrent || {};
      const progressCurrentYear = Array.isArray(json.progressCurrentYear) ? json.progressCurrentYear : [];
      const progressLastYear = Array.isArray(json.progressLastYear) ? json.progressLastYear : [];

      const toDisplay = (v) => {
        if (v === null || v === undefined) return "—";
        const n = Number(v);
        if (!Number.isFinite(n)) return "—";
        return String(Math.round(n));
      };

      const setValue = (id, v) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = toDisplay(v);
      };

      const currentWeekKey = current.weekKey;
      const currentMonthKey = current.monthKey;
      const currentYearKey = current.yearKey;

      const lyWeekKey = lastYearCurrent.weekKey;
      const lyMonthKey = lastYearCurrent.monthKey;
      const lyDateKey = lastYearCurrent.dateKey;

      setValue("avgWeek", weekly[currentWeekKey]);
      setValue("avgMonth", monthly[currentMonthKey]);
      setValue("avgYear", yearly[currentYearKey]);

      setValue("ly_avgWeek", weekly[lyWeekKey]);
      setValue("ly_avgMonth", monthly[lyMonthKey]);
      setValue("ly_avgDay", daily[lyDateKey]);

      // Render segmented progress bars using new arrays from server
      try {
        const curDaysInYear = Number(current.daysInYear) || (daysInYear(Number(current.yearKey)));
        const lyDaysInYear = Number(lastYearCurrent.daysInYear) || (daysInYear(Number(lastYearCurrent.yearKey)));
        const curDayOfYear = Number(current.dayOfYear) || null;

          // Update 'Year stat' labels above each progress bar
          try {
            const pctCurrent = (curDayOfYear && curDaysInYear) ? Math.round((curDayOfYear / curDaysInYear) * 100) : null;
            const pctLast = (curDayOfYear && lyDaysInYear) ? Math.round((curDayOfYear / lyDaysInYear) * 100) : null;

            const curLabelContainer = document.getElementById('yearProgressCurrent');
            if (curLabelContainer && curLabelContainer.previousElementSibling) {
              const lab = curLabelContainer.previousElementSibling;
              lab.textContent = (pctCurrent === null) ? 'Year stat: —' : `Year stat: ${pctCurrent}%`;
            }

            const lastLabelContainer = document.getElementById('yearProgressLastYear');
            if (lastLabelContainer && lastLabelContainer.previousElementSibling) {
              const lab2 = lastLabelContainer.previousElementSibling;
              lab2.textContent = (pctLast === null) ? 'Year stat: —' : `Year stat: ${pctLast}%`;
            }
          } catch (e) {
            /* ignore label errors */
          }

        // Current year bar: values up to today are ints or 0; future days are null
        // also show pin marker at today's day-of-year
        renderSegmentedBar('yearProgressCurrent', progressCurrentYear, curDaysInYear, { showFutureAsEmpty: true, markerDay: curDayOfYear });

        // Last year bar: full-year values, draw marker and pin for today's equivalent day-of-year
        renderSegmentedBar('yearProgressLastYear', progressLastYear, lyDaysInYear, { markerDay: curDayOfYear });
      
        // Populate 'More stats' card if available (formatted to match attachment)
        try {
          const stats = json.stats || null;
          if (stats) {
            const el = (id) => document.getElementById(id);

            // Helper for arrow and sign formatting
            const fmtDelta = (d, withSign = false) => {
              if (d === null || d === undefined) return '—';
              const num = Number(d);
              const sign = num > 0 ? '+' : (num < 0 ? '−' : (withSign ? '+' : ''));
              // use one decimal for momentum deltas, integer for day delta
              return (withSign ? (num > 0 ? '+' : (num < 0 ? '' : '+')) : '') + String(num);
            };

            // Trend headline MUST be derived ONLY from 7-day delta (avg7 - prevAvg7)
            // Use stats.week.delta as the single source of truth (delta7)
            const overallEl = el('stat_overall');
            if (overallEl) {
              const delta7 = (stats.week && typeof stats.week.delta === 'number') ? stats.week.delta : null;
              if (delta7 === null) {
                overallEl.textContent = '—';
                overallEl.className = 'trend-main flat';
              } else {
                // round to 1 decimal for display
                const rounded = Math.round(delta7 * 10) / 10;
                const disp = rounded.toFixed(1);
                if (rounded > 0) {
                  overallEl.textContent = `▲ Improving`;
                  overallEl.className = 'trend-main up';
                } else if (rounded < 0) {
                  overallEl.textContent = `▼ Deteriorating`;
                  overallEl.className = 'trend-main down';
                } else {
                  overallEl.textContent = `→ Stable`;
                  overallEl.className = 'trend-main flat';
                }
              }
            }

            // week
            const weekAvg = stats.week && stats.week.avg !== null ? stats.week.avg : null;
            const weekDelta = stats.week && typeof stats.week.delta === 'number' ? stats.week.delta : null;
            if (el('stat_week_avg')) el('stat_week_avg').textContent = weekAvg === null ? '—' : String(weekAvg);
            if (el('stat_week_delta')) {
              if (weekDelta === null) el('stat_week_delta').textContent = '—';
              else {
                const arrow = weekDelta > 0 ? '▲' : (weekDelta < 0 ? '▼' : '→');
                el('stat_week_delta').textContent = `${arrow} ${weekDelta > 0 ? '+' : ''}${weekDelta}`;
                el('stat_week_delta').className = 'trend-indicator ' + (weekDelta > 0 ? 'up' : (weekDelta < 0 ? 'down' : 'flat'));
              }
            }

            // month
            const monthAvg = stats.month && stats.month.avg !== null ? stats.month.avg : null;
            const monthDelta = stats.month && typeof stats.month.delta === 'number' ? stats.month.delta : null;
            if (el('stat_month_avg')) el('stat_month_avg').textContent = monthAvg === null ? '—' : String(monthAvg);
            if (el('stat_month_delta')) {
              if (monthDelta === null) el('stat_month_delta').textContent = '—';
              else {
                const arrow = monthDelta > 0 ? '▲' : (monthDelta < 0 ? '▼' : '→');
                el('stat_month_delta').textContent = `${arrow} ${monthDelta > 0 ? '+' : ''}${monthDelta}`;
                el('stat_month_delta').className = 'trend-indicator ' + (monthDelta > 0 ? 'up' : (monthDelta < 0 ? 'down' : 'flat'));
              }
            }

            // today / yesterday line: format "Yesterday: +1 → Today: -1 (-2)"
            const tyEl = el('stat_yesterday_today');
            if (tyEl) {
              const t = stats.today && (typeof stats.today.value === 'number' || typeof stats.today.value === 'string') ? stats.today.value : null;
              const y = stats.today && (typeof stats.today.yesterday === 'number' || typeof stats.today.yesterday === 'string') ? stats.today.yesterday : null;
              const delta = stats.today && typeof stats.today.delta === 'number' ? stats.today.delta : null;
              const fmt = (n) => (n === null ? '—' : (n > 0 ? '+' + n : String(n)));
              const fmtParen = (n) => (n === null ? '—' : (n > 0 ? '+' + n : String(n)));
              tyEl.innerHTML = `Yesterday: <span class="${(y>0)?'pos':(y<0)?'neg':'neutral'}">${fmt(y)}</span> → Today: <span class="${(t>0)?'pos':(t<0)?'neg':'neutral'}">${fmt(t)}</span> (<span class="${(delta>0)?'pos':(delta<0)?'neg':'neutral'}">${fmtParen(delta)}</span>)`;
            }

            // best / worst
            if (stats.bestDay) {
              if (el('stat_best_label')) el('stat_best_label').textContent = stats.bestDay.label || stats.bestDay.date;
              if (el('stat_best_value')) el('stat_best_value').textContent = (stats.bestDay.value>0?'+':'') + String(stats.bestDay.value);
            }
            if (stats.worstDay) {
              if (el('stat_worst_label')) el('stat_worst_label').textContent = stats.worstDay.label || stats.worstDay.date;
              if (el('stat_worst_value')) el('stat_worst_value').textContent = (stats.worstDay.value>0?'+':'') + String(stats.worstDay.value);
            }
          }
        } catch (e) {
          /* ignore more-stats rendering errors */
        }
      } catch (e) {
        if (typeof DEBUG !== 'undefined' && DEBUG) console.warn('Unable to render segmented progress bars', e);
      }
    } catch (err) {
      [
        "avgWeek",
        "avgMonth",
        "avgYear",
        "ly_avgWeek",
        "ly_avgMonth",
        "ly_avgDay",
      ].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.textContent = "—";
      });

      console.warn("Unable to load happiness:", err);
    }
  }

  loadMedians();
});

// global helper to trigger save (used by inline onclick on Save button)
function saveValue() {
  const btn = document.getElementById("saveBtn");
  if (btn) btn.click();
}

/**
 * Render a year bar where each day of the year is a single segment.
 * - containerId: id of the container element
 * - dailyData: map of 'YYYY-MM-DD' => numeric value
 * - year: numeric year (e.g. 2025)
 */
function renderYearBar(containerId, dailyData, year) {
  const container = document.getElementById(containerId);
  if (!container) return;
  // clear
  container.innerHTML = "";

  // days in year
  const days = Math.round((Date.UTC(year + 1, 0, 1) - Date.UTC(year, 0, 1)) / 86400000);

  // color interpolation helpers
  const lerp = (a, b, t) => Math.round(a + (b - a) * t);
  const rgbToCss = (r, g, b) => `rgb(${r}, ${g}, ${b})`;
  const valueToColor = (v) => {
    // clamp
    const val = Math.max(-10, Math.min(10, Number(v)));
    const red = [237, 50, 55]; // -10
    const yellow = [255, 200, 30]; // 0
    const green = [60, 180, 75]; // +10
    if (val <= 0) {
      const t = (val + 10) / 10; // 0..1
      const r = lerp(red[0], yellow[0], t);
      const g = lerp(red[1], yellow[1], t);
      const b = lerp(red[2], yellow[2], t);
      return rgbToCss(r, g, b);
    } else {
      const t = val / 10; // 0..1
      const r = lerp(yellow[0], green[0], t);
      const g = lerp(yellow[1], green[1], t);
      const b = lerp(yellow[2], green[2], t);
      return rgbToCss(r, g, b);
    }
  };

  // build segments
  const base = Date.UTC(year, 0, 1);
  for (let i = 0; i < days; i++) {
    const seg = document.createElement('div');
    seg.className = 'year-bar-seg';
    seg.style.width = (100 / days) + '%';
    seg.style.flex = '0 0 ' + (100 / days) + '%';
    seg.style.minWidth = '0';
    seg.style.boxSizing = 'border-box';

    const dt = new Date(base + i * 86400000);
    const y = dt.getUTCFullYear();
    const m = String(dt.getUTCMonth() + 1).padStart(2, '0');
    const d = String(dt.getUTCDate()).padStart(2, '0');
    const key = `${y}-${m}-${d}`;

    if (dailyData && Object.prototype.hasOwnProperty.call(dailyData, key)) {
      const color = valueToColor(dailyData[key]);
      seg.style.background = color;
    } else {
      seg.style.background = '#ececec';
    }

    container.appendChild(seg);
  }
}

// days in year helper
function daysInYear(year) {
  return Math.round((Date.UTC(year + 1, 0, 1) - Date.UTC(year, 0, 1)) / 86400000);
}

// Render a grid-based year progress bar with one segment per day.
// - containerId: id of container
// - dailyData: map 'YYYY-MM-DD' => value
// - year: numeric year
function renderYearProgress(containerId, dailyData, year) {
  const container = document.getElementById(containerId);
  if (!container) return;
  container.innerHTML = '';

  const days = daysInYear(year);
  // set grid columns
  container.style.display = 'grid';
  container.style.gridTemplateColumns = `repeat(${days}, 1fr)`;
  container.style.gap = '0';

  // quick check if any data exists for the provided map
  const hasAny = Object.keys(dailyData || {}).length > 0;

  const base = Date.UTC(year, 0, 1);
  for (let i = 0; i < days; i++) {
    const seg = document.createElement('div');
    seg.className = 'year-seg';

    const dt = new Date(base + i * 86400000);
    const y = dt.getUTCFullYear();
    const m = String(dt.getUTCMonth() + 1).padStart(2, '0');
    const d = String(dt.getUTCDate()).padStart(2, '0');
    const key = `${y}-${m}-${d}`;

    if (hasAny && Object.prototype.hasOwnProperty.call(dailyData, key)) {
      const val = Number(dailyData[key]);
      if (Number.isFinite(val)) {
        // map [-10..10] to t in [0..1]
        const t = (val + 10) / 20;
        const hue = 0 + 120 * t; // 0 (red) -> 120 (green)
        seg.style.backgroundColor = `hsl(${hue}, 75%, 55%)`;
      } else {
        seg.style.backgroundColor = 'rgba(220,220,220,0.35)';
      }
    } else {
      // neutral segment
      seg.style.backgroundColor = 'rgba(220,220,220,0.35)';
    }

    container.appendChild(seg);
  }
}

/**
 * Render segmented progress bar from a values array.
 * - values: array length = daysInYear. Each item: number (int) or null.
 * - options: { showFutureAsEmpty: true, markerDay: <1-based dayOfYear> }
 */
function renderSegmentedBar(containerId, values, daysInYear, options = {}) {
  const container = document.getElementById(containerId);
  if (!container) return;
  container.innerHTML = '';
  container.classList.add('progress-bar');
  container.style.position = 'relative';
  container.style.display = 'grid';
  container.style.gridTemplateColumns = `repeat(${daysInYear}, 1fr)`;
  container.style.gap = '0';

  const markerDay = options.markerDay || null;

  // helper: map value [-10..10] -> HSL hue 0..120
  function valueToHsl(v) {
    // continuous HSL interpolation across three anchors:
    // -10 -> hsl(0,80%,55%), 0 -> hsl(50,85%,55%), +10 -> hsl(120,70%,45%)
    const val = Math.max(-10, Math.min(10, Number(v)));
    const lerp = (a, b, t) => a + (b - a) * t;
    let h, s, l;
    if (val <= 0) {
      const t = (val + 10) / 10; // 0..1 from -10..0
      h = lerp(0, 50, t);
      s = lerp(80, 85, t);
      l = lerp(55, 55, t);
    } else {
      const t = val / 10; // 0..1 from 0..+10
      h = lerp(50, 120, t);
      s = lerp(85, 70, t);
      l = lerp(55, 45, t);
    }
    return `hsl(${Math.round(h)}, ${Math.round(s)}%, ${Math.round(l)}%)`;
  }

  for (let i = 0; i < daysInYear; i++) {
    const seg = document.createElement('div');
    seg.className = 'progress-segment';
    // decide value
    const v = Array.isArray(values) && i < values.length ? values[i] : null;

    if (v === null || v === undefined) {
      // show neutral/empty (uncolored)
      seg.style.backgroundColor = 'rgba(220,220,220,0.35)';
      seg.setAttribute('data-filled', '0');
    } else {
      // compute 3-point moving average using neighbors (fallback to center value)
      const prev = (i > 0 && Array.isArray(values) && (i - 1) < values.length && values[i - 1] != null) ? values[i - 1] : v;
      const next = (i < daysInYear - 1 && Array.isArray(values) && (i + 1) < values.length && values[i + 1] != null) ? values[i + 1] : v;
      const smoothed = (Number(prev) + Number(v) + Number(next)) / 3.0;
      seg.style.backgroundColor = valueToHsl(smoothed);
      seg.setAttribute('data-filled', '1');
    }

    container.appendChild(seg);
  }

  // marker for last-year bar
  if (markerDay && Number.isFinite(markerDay)) {
    // vertical marker line
    const m = document.createElement('div');
    m.className = 'progress-marker-line';
    // center marker on the given day index (1-based)
    const leftPct = ((markerDay - 0.5) / daysInYear) * 100;
    m.style.left = `calc(${leftPct}% )`;
    container.appendChild(m);

    // inverted circle pin above the bar
    const p = document.createElement('div');
    p.className = 'progress-pin';
    p.style.left = `calc(${leftPct}% )`;
    container.appendChild(p);
  }
}
