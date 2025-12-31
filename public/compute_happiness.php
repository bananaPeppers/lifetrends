<?php
/**
 * compute_happiness.php
 *
 * Returns JSON with weekly, monthly and yearly happiness values using robust median aggregation.
 * Rules:
 * - Daily scores: average of all entries for the date, excluding values outside [-10,10]
 * - Weekly happiness: median of daily scores in the ISO week
 * - Monthly happiness: median of daily scores in the month
 * - Yearly happiness: median of the monthly happiness values for that year
 *
 * Optional query params:
 *   ?year=2025   - compute yearly happiness for given year (defaults to current year)
 */

header('Content-Type: application/json; charset=utf-8');

$pdo = require __DIR__ . '/../config/db.php';

// reusable median function
function median(array $values) : ?float {
    // filter numeric and finite
    $vals = array_values(array_filter($values, function($v){
        return is_numeric($v) && is_finite($v);
    }));
    $n = count($vals);
    if ($n === 0) return null;
    sort($vals, SORT_NUMERIC);
    $mid = intdiv($n, 2);
    if ($n % 2 === 1) {
        return (float)$vals[$mid];
    } else {
        // average of two middle values
        return ((float)$vals[$mid - 1] + (float)$vals[$mid]) / 2.0;
    }
}

try {
    // fetch raw entries for all dates, ignoring out-of-range values
    $sql = "SELECT DATE(`date`) AS dt, `index` AS idx
            FROM `life`
            WHERE `index` BETWEEN -10 AND 10
            ORDER BY dt ASC";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // group raw values per day to compute per-day medians
    $perDay = []; // dt => [values]
    foreach ($rows as $r) {
        $dt = $r['dt'];
        $v = $r['idx'] !== null ? (float)$r['idx'] : null;
        if ($v === null) continue;
        $perDay[$dt][] = $v;
    }

    $daily_medians = []; // dt => median
    foreach ($perDay as $dt => $vals) {
        $m = median($vals);
        if ($m !== null) $daily_medians[$dt] = round($m, 2);
    }

    $weekly = []; // key: ISO-year-Www => array of day_medians
    $monthly = []; // key: YYYY-MM => array of day_medians
    $monthly_by_year = []; // year => month => monthly median to compute yearly

    foreach ($daily_medians as $dt => $dayMedian) {
        $d = new DateTime($dt);
        $isoYear = (int)$d->format('o'); // ISO-8601 year number
        $isoWeek = (int)$d->format('W');
        $weekKey = sprintf('%04d-W%02d', $isoYear, $isoWeek);
        $monthKey = $d->format('Y-m');
        $yearKey = $d->format('Y');

        $weekly[$weekKey][] = $dayMedian;
        $monthly[$monthKey][] = $dayMedian;
        $monthly_by_year[$yearKey][$d->format('m')][] = $dayMedian;
    }

    // compute medians for weekly and monthly
    $weekly_medians = [];
    foreach ($weekly as $k => $vals) {
        $m = median($vals);
        $weekly_medians[$k] = $m === null ? null : round($m, 2);
    }

    $monthly_medians = [];
    foreach ($monthly as $k => $vals) {
        $m = median($vals);
        $monthly_medians[$k] = $m === null ? null : round($m, 2);
    }

    // compute yearly medians (median of monthly happiness values for each year)
    $yearly = [];
    foreach ($monthly_by_year as $year => $monthsArr) {
        $monthlyVals = [];
        foreach ($monthsArr as $month => $vals) {
            $mv = median($vals);
            if ($mv !== null) $monthlyVals[] = $mv;
        }
        $yv = median($monthlyVals);
        $yearly[$year] = $yv === null ? null : round($yv, 2);
    }

    // optional: limit to a requested year for yearly value
    $requestedYear = isset($_GET['year']) ? preg_replace('/[^0-9]/', '', $_GET['year']) : null;
    $result = [
        'success' => true,
        'daily' => $daily_medians,
        'weekly' => $weekly_medians,
        'monthly' => $monthly_medians,
        'yearly' => $yearly,
    ];

    if ($requestedYear) {
        $result['year'] = array_key_exists($requestedYear, $yearly) ? $yearly[$requestedYear] : null;
    }

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
