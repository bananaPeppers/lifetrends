<?php
/**
 * compute_happiness.php
 *
 * Option 2: Daily AVG first -> then period AVG
 *
 * Returns JSON:
 * - currentValue: { week, month, year } (integers; rounded up)
 * - lastYearValue: { day, week, month } (integers; rounded up)
 * - progressCurrentYear: array length daysInYear (1..today filled, future = null)
 * - progressLastYear: array length daysInLastYear (entire year filled, missing=0)
 * - current: { today, year, dayOfYear, daysInYear }
 * - lastYearCurrent: { year, dateKey, dayOfYear, daysInYear }
 */

header('Content-Type: application/json; charset=utf-8');

$pdo = require __DIR__ . '/../config/db.php';

function ceil_int(?float $v): ?int {
    if ($v === null) return null;
    return (int)ceil($v);
}

function round_int_or_null(?float $v): ?int {
    if ($v === null) return null;
    return (int)round($v, 0, PHP_ROUND_HALF_UP);
}

function avg_array(array $vals): ?float {
    $n = count($vals);
    if ($n === 0) return null;
    return array_sum($vals) / $n;
}

try {
    date_default_timezone_set('America/New_York');

    $todayObj = new DateTime('now');
    $todayStr = $todayObj->format('Y-m-d');

    $currentYear = (int)$todayObj->format('Y');
    $dayOfYear = (int)$todayObj->format('z') + 1; // 1-based
    $daysInYear = ((int)$todayObj->format('L') === 1) ? 366 : 365;

    $currentIsoYear = (int)$todayObj->format('o');
    $currentIsoWeek = (int)$todayObj->format('W');
    $currentWeekKey  = sprintf('%04d-W%02d', $currentIsoYear, $currentIsoWeek);
    $currentMonthKey = $todayObj->format('Y-m');

    // Last year reference date (same calendar date - 1 year)
    $lastYearObj = (clone $todayObj)->modify('-1 year');
    $lastYearStr = $lastYearObj->format('Y-m-d');
    $lastYear = (int)$lastYearObj->format('Y');
    $lastYearDays = ((int)$lastYearObj->format('L') === 1) ? 366 : 365;
    $lastYearDayOfYear = min($dayOfYear, $lastYearDays); // clamp if leap mismatch

    // Pull per-day averages for:
    // - last year full year
    // - current year up to today only
    $sql = "
      SELECT DATE(`date`) AS dt, AVG(`index`) AS day_avg
      FROM `life`
      WHERE `index` BETWEEN -10 AND 10
        AND (
          (`date` BETWEEN :ly_start AND :ly_end)
          OR
          (`date` BETWEEN :cy_start AND :cy_today)
        )
      GROUP BY dt
      ORDER BY dt ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':ly_start' => $lastYear . '-01-01',
        ':ly_end'   => $lastYear . '-12-31',
        ':cy_start' => $currentYear . '-01-01',
        ':cy_today' => $todayStr,
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // dailyAvg['YYYY-MM-DD'] = float day average
    $dailyAvg = [];
    foreach ($rows as $r) {
        if (!isset($r['dt'])) continue;
        $dt = $r['dt'];
        $v  = ($r['day_avg'] !== null) ? (float)$r['day_avg'] : null;
        if ($v === null) continue;
        $dailyAvg[$dt] = $v;
    }

    // Build progress arrays
    $progressCurrentYear = [];
    for ($i = 1; $i <= $daysInYear; $i++) {
        $d = new DateTime($currentYear . '-01-01');
        $d->modify('+' . ($i - 1) . ' days');
        $key = $d->format('Y-m-d');

        if ($i > $dayOfYear) {
            $progressCurrentYear[] = null;     // future (uncolored)
        } else {
            $progressCurrentYear[] = ceil_int($dailyAvg[$key] ?? 0.0); // missing = 0
        }
    }

    $progressLastYear = [];
    for ($i = 1; $i <= $lastYearDays; $i++) {
        $d = new DateTime($lastYear . '-01-01');
        $d->modify('+' . ($i - 1) . ' days');
        $key = $d->format('Y-m-d');
        $progressLastYear[] = ceil_int($dailyAvg[$key] ?? 0.0); // missing = 0
    }

    // ------------------ Stats computations ------------------
    // Helper to get a filled window of length $len ending at $endDate (Y-m-d)
    $getWindow = function(string $endDate, int $len) use ($dailyAvg) {
        $out = [];
        $d = new DateTime($endDate);
        for ($i = 0; $i < $len; $i++) {
            $key = $d->format('Y-m-d');
            $out[] = isset($dailyAvg[$key]) ? (float)$dailyAvg[$key] : 0.0;
            $d->modify('-1 day');
        }
        return array_reverse($out); // earliest..latest
    };

    // Today and yesterday values (rounded integers)
    $todayValue = array_key_exists($todayStr, $dailyAvg) ? round($dailyAvg[$todayStr], 0, PHP_ROUND_HALF_UP) : null;
    $yesterdayObj = (new DateTime($todayStr))->modify('-1 day');
    $yesterdayStr = $yesterdayObj->format('Y-m-d');
    $yesterdayValue = array_key_exists($yesterdayStr, $dailyAvg) ? round($dailyAvg[$yesterdayStr], 0, PHP_ROUND_HALF_UP) : null;
    // treat missing as 0 for delta display per requirements
    $tv = $todayValue === null ? 0 : $todayValue;
    $yv = $yesterdayValue === null ? 0 : $yesterdayValue;
    $todayDelta = $tv - $yv;

    // Week (7) momentum
    $w_cur = $getWindow($todayStr, 7);
    $w_prev_end = (new DateTime($todayStr))->modify('-7 days')->format('Y-m-d');
    $w_prev = $getWindow($w_prev_end, 7);
    $weekAvg7 = round(avg_array($w_cur), 0);
    $weekPrev7 = round(avg_array($w_prev), 0);
    $weekDelta = round((avg_array($w_cur) - avg_array($w_prev)), 1);
    $weekArrow = ($weekDelta >= 1.0) ? 'up' : (($weekDelta <= -1.0) ? 'down' : 'flat');

    // Month (30) momentum
    $m_cur = $getWindow($todayStr, 30);
    $m_prev_end = (new DateTime($todayStr))->modify('-30 days')->format('Y-m-d');
    $m_prev = $getWindow($m_prev_end, 30);
    $monthAvg30 = round(avg_array($m_cur), 0);
    $monthPrev30 = round(avg_array($m_prev), 0);
    $monthDelta = round((avg_array($m_cur) - avg_array($m_prev)), 1);
    $monthArrow = ($monthDelta >= 1.0) ? 'up' : (($monthDelta <= -1.0) ? 'down' : 'flat');

    // Best/worst day in current year-to-date (include 0-filled days but prefer real recorded days)
    $best = null; $worst = null;
    $iter = new DateTime($currentYear . '-01-01');
    while ($iter->format('Y-m-d') <= $todayStr) {
        $k = $iter->format('Y-m-d');
        $isReal = array_key_exists($k, $dailyAvg);
        $valFloat = $dailyAvg[$k] ?? 0.0;
        $val = (int)round($valFloat, 0, PHP_ROUND_HALF_UP);

        if ($best === null) $best = ['date' => $k, 'val' => $val, 'real' => $isReal];
        if ($worst === null) $worst = ['date' => $k, 'val' => $val, 'real' => $isReal];

        // best: prefer higher val; on tie prefer real
        if ($val > $best['val'] || ($val === $best['val'] && $isReal && !$best['real'])) {
            $best = ['date' => $k, 'val' => $val, 'real' => $isReal];
        }
        if ($val < $worst['val'] || ($val === $worst['val'] && $isReal && !$worst['real'])) {
            $worst = ['date' => $k, 'val' => $val, 'real' => $isReal];
        }

        $iter->modify('+1 day');
    }

    $bestDay = $best ? ['date' => $best['date'], 'label' => (new DateTime($best['date']))->format('M j'), 'value' => (int)$best['val']] : null;
    $worstDay = $worst ? ['date' => $worst['date'], 'label' => (new DateTime($worst['date']))->format('M j'), 'value' => (int)$worst['val']] : null;

    // ---- Current period-to-date values using daily (missing filled 0) ----
    $curWeekVals = [];
    $curMonthVals = [];
    $curYearVals = [];

    $iter = new DateTime($currentYear . '-01-01');
    while ($iter->format('Y-m-d') <= $todayStr) {
        $k = $iter->format('Y-m-d');
        $val = ceil_int($dailyAvg[$k] ?? 0.0);

        $isoY = (int)$iter->format('o');
        $isoW = (int)$iter->format('W');
        $wkKey = sprintf('%04d-W%02d', $isoY, $isoW);
        if ($wkKey === $currentWeekKey) $curWeekVals[] = $val;

        if ($iter->format('Y-m') === $currentMonthKey) $curMonthVals[] = $val;

        $curYearVals[] = $val;

        $iter->modify('+1 day');
    }

    $currentValue = [
        'week'  => ceil_int(avg_array($curWeekVals)),
        'month' => ceil_int(avg_array($curMonthVals)),
        'year'  => ceil_int(avg_array($curYearVals)),
    ];

    // ---- "This time last year" period-to-date ----
    $lyDay = ceil_int($dailyAvg[$lastYearStr] ?? 0.0);

    // Week: Monday..that date
    $lyWeekVals = [];
    $lyWeekStart = (clone $lastYearObj);
    $lyWeekStart->setISODate((int)$lastYearObj->format('o'), (int)$lastYearObj->format('W'), 1);
    $lyIter = clone $lyWeekStart;
    while ($lyIter->format('Y-m-d') <= $lastYearStr) {
        $k = $lyIter->format('Y-m-d');
        $lyWeekVals[] = ceil_int($dailyAvg[$k] ?? 0.0);
        $lyIter->modify('+1 day');
    }

    // Month: 1st..that date
    $lyMonthVals = [];
    $lyMonthStart = new DateTime($lastYearObj->format('Y-m-01'));
    $lyIter = clone $lyMonthStart;
    while ($lyIter->format('Y-m-d') <= $lastYearStr) {
        $k = $lyIter->format('Y-m-d');
        $lyMonthVals[] = ceil_int($dailyAvg[$k] ?? 0.0);
        $lyIter->modify('+1 day');
    }

    $lastYearValue = [
        'day'   => $lyDay,
        'week'  => ceil_int(avg_array($lyWeekVals)),
        'month' => ceil_int(avg_array($lyMonthVals)),
    ];

    echo json_encode([
        'success' => true,
        'currentValue' => $currentValue,
        'lastYearValue' => $lastYearValue,
        'progressCurrentYear' => $progressCurrentYear,
        'progressLastYear' => $progressLastYear,
        'current' => [
            'today' => $todayStr,
            'year' => $currentYear,
            'dayOfYear' => $dayOfYear,
            'daysInYear' => $daysInYear,
        ],
        'lastYearCurrent' => [
            'year' => $lastYear,
            'dateKey' => $lastYearStr,
            'dayOfYear' => $lastYearDayOfYear, // marker position in last-year bar
            'daysInYear' => $lastYearDays,
        ],
        'stats' => [
            'week' => [
                'avg' => is_null($weekAvg7) ? null : (int)$weekAvg7,
                'prev' => is_null($weekPrev7) ? null : (int)$weekPrev7,
                'delta' => is_null($weekDelta) ? null : (float)$weekDelta,
                'arrow' => $weekArrow,
            ],
            'month' => [
                'avg' => is_null($monthAvg30) ? null : (int)$monthAvg30,
                'prev' => is_null($monthPrev30) ? null : (int)$monthPrev30,
                'delta' => is_null($monthDelta) ? null : (float)$monthDelta,
                'arrow' => $monthArrow,
            ],
            'today' => [
                'value' => $todayValue,
                'yesterday' => $yesterdayValue,
                'delta' => $todayDelta,
            ],
            'bestDay' => $bestDay,
            'worstDay' => $worstDay,
        ],
    ], JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
