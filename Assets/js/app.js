// Frontend JS for RateMyLife dashboard (no DB yet)
document.addEventListener("DOMContentLoaded", function () {
  const todayIndex = document.getElementById("todayIndex");
  const saveBtn = document.getElementById("saveBtn");
  const indexNumber = document.getElementById("indexNumber");
  const rangeMin = Number(todayIndex.min);
  const rangeMax = Number(todayIndex.max);

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
      return d
        .toLocaleTimeString(undefined, {
          hour: "2-digit",
          minute: "2-digit",
          hour12: false,
        })
        .slice(0, 5);
    }

    function tick() {
      const now = new Date();
      datePart.textContent = formatDate(now);
      timePart.textContent = formatHM(now);
    }

    // init and align updates to minute boundary for efficiency
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

  // update index display helper
  function updateIndexDisplay() {
    const val = Number(todayIndex.value);
    if (indexNumber) indexNumber.textContent = String(val);
  }

  todayIndex.addEventListener("input", () => {
    updateIndexDisplay();
  });

  // ensure default resting position is 0 and set initial display
  todayIndex.value = 0;
  updateIndexDisplay();

  saveBtn.addEventListener("click", async () => {
    const payload = {
      index: Number(todayIndex.value),
    };

    // Button click animation + disable while saving
    saveBtn.classList.add("clicked");
    saveBtn.disabled = true;

    // ensure notice exists
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
        // refresh medians after a successful save
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
      // show notice and reset button after short animation
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

  // No dynamic track fill — thumb is an outlined circle that slides over the static gradient track.

  // fetch medians (daily/weekly/monthly/yearly) and populate UI
  async function loadMedians() {
    try {
      const res = await fetch("compute_happiness.php");
      if (!res.ok) throw new Error(res.statusText);
      const json = await res.json();
      if (!json.success)
        throw new Error(json.message || "Failed to load medians");

      const weekly = json.weekly || {};
      const monthly = json.monthly || {};
      const yearly = json.yearly || {};
      const daily = json.daily || {};

      const setOrDash = (elId, val) => {
        const el = document.getElementById(elId);
        if (!el) return;
        el.textContent = val === null || val === undefined ? "—" : String(val);
      };

      // helpers to format keys
      function isoWeekKey(d) {
        const tmp = new Date(
          Date.UTC(d.getFullYear(), d.getMonth(), d.getDate())
        );
        const dayNum = tmp.getUTCDay() || 7;
        tmp.setUTCDate(tmp.getUTCDate() + 4 - dayNum);
        const isoYear = tmp.getUTCFullYear();
        const yearStart = new Date(Date.UTC(isoYear, 0, 1));
        const weekNo = Math.ceil(((tmp - yearStart) / 86400000 + 1) / 7);
        return `${isoYear}-W${String(weekNo).padStart(2, "0")}`;
      }
      function monthKey(d) {
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`;
      }
      function dateKey(d) {
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
      }

      const today = new Date();
      const currentWeekKey = isoWeekKey(today);
      const currentMonthKey = monthKey(today);
      const currentYearKey = String(today.getFullYear());

      // this time last year
      const lastYearDate = new Date(today);
      lastYearDate.setFullYear(lastYearDate.getFullYear() - 1);
      const lyWeekKey = isoWeekKey(lastYearDate);
      const lyMonthKey = monthKey(lastYearDate);
      const lyDateKey = dateKey(lastYearDate);

      setOrDash("avgWeek", weekly[currentWeekKey] ?? null);
      setOrDash("avgMonth", monthly[currentMonthKey] ?? null);
      setOrDash("avgYear", yearly[currentYearKey] ?? null);

      // previous period removed from UI

      // populate "This time last year" using the medians maps
      setOrDash("ly_avgWeek", weekly[lyWeekKey] ?? null);
      setOrDash("ly_avgMonth", monthly[lyMonthKey] ?? null);
      setOrDash("ly_avgDay", daily[lyDateKey] ?? null);
    } catch (err) {
      // reset all targets
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
      console.warn("Unable to load medians:", err);
    }
  }

  // load medians on startup
  loadMedians();
});

// global helper to trigger save (used by inline onclick on Save button)
function saveValue() {
  const btn = document.getElementById("saveBtn");
  if (btn) btn.click();
}
