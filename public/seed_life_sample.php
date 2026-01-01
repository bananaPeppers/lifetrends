<?php
/**
 * seed_life_sample.php
 *
 * Seeds sample data into DB table `life`:
 *  - 60 days of current year starting Jan 1
 *  - full year of last year (Jan 1..Dec 31)
 *
 * Assumes table: life(date, `index`) and config at ../config/db.php
 *
 * Run:
 *  - Browser:   http://localhost/yourapp/public/seed_life_sample.php
 *  - CLI:       php seed_life_sample.php
 */

header('Content-Type: text/plain; charset=utf-8');

date_default_timezone_set('America/New_York');

$pdo = require __DIR__ . '/../config/db.php';

// ===== SETTINGS =====
$DELETE_EXISTING_IN_RANGES = true; // set false if you want to keep existing rows

$currentYear = (int)date('Y');
$lastYear    = $currentYear - 1;

$currentStart = new DateTime("$currentYear-01-01");
$currentDays  = 60;

$lastStart    = new DateTime("$lastYear-01-01");
$lastEnd      = new DateTime("$lastYear-12-31");

// Clamp helper
function clampInt($v, $min, $max) {
  $v = (int)round($v);
  if ($v < $min) return $min;
  if ($v > $max) return $max;
  return $v;
}

// Deterministic-ish generator (smooth waves + tiny deterministic noise)
function genIndexForDay(int $dayNumber, int $year): int {
  // dayNumber is 1-based
  $x = $dayNumber;

  // smooth components
  $wave1 = 6 * sin($x / 9.0);
  $wave2 = 3 * cos($x / 17.0);

  // small deterministic "noise" based on year/day
  $noiseSeed = ($year * 1000 + $dayNumber) % 11; // 0..10
  $noise = ($noiseSeed - 5) * 0.25; // -1.25 .. +1.25

  $raw = $wave1 + $wave2 + $noise;
  return clampInt($raw, -10, 10);
}

try {
  $pdo->beginTransaction();

  // Optional delete
  if ($DELETE_EXISTING_IN_RANGES) {
    $curEnd = (clone $currentStart)->modify('+' . ($currentDays - 1) . ' days')->format('Y-m-d');

    $del = $pdo->prepare("
      DELETE FROM `life`
      WHERE (`date` BETWEEN :curStart AND :curEnd)
         OR (`date` BETWEEN :lyStart AND :lyEnd)
    ");
    $del->execute([
      ':curStart' => $currentStart->format('Y-m-d'),
      ':curEnd'   => $curEnd,
      ':lyStart'  => $lastStart->format('Y-m-d'),
      ':lyEnd'    => $lastEnd->format('Y-m-d'),
    ]);

    echo "Deleted existing rows in ranges.\n";
  }

  // Insert statement
  $ins = $pdo->prepare("INSERT INTO `life` (`date`, `index`) VALUES (:date, :idx)");

  // --- Insert 60 days current year ---
  $dt = clone $currentStart;
  for ($i = 0; $i < $currentDays; $i++) {
    $dayNum = $i + 1; // 1-based since Jan 1
    $idx = genIndexForDay($dayNum, $currentYear);

    $ins->execute([
      ':date' => $dt->format('Y-m-d'),
      ':idx'  => $idx,
    ]);

    $dt->modify('+1 day');
  }
  echo "Inserted {$currentDays} days for current year {$currentYear} starting {$currentStart->format('Y-m-d')}.\n";

  // --- Insert full last year ---
  $dt = clone $lastStart;
  $countLY = 0;

  // iterate inclusive
  while ($dt <= $lastEnd) {
    $dayNum = (int)$dt->format('z') + 1; // day-of-year 1..365/366
    $idx = genIndexForDay($dayNum, $lastYear);

    $ins->execute([
      ':date' => $dt->format('Y-m-d'),
      ':idx'  => $idx,
    ]);

    $countLY++;
    $dt->modify('+1 day');
  }

  echo "Inserted {$countLY} days for last year {$lastYear} (full year).\n";

  $pdo->commit();
  echo "DONE ✅\n";

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo "ERROR: " . $e->getMessage() . "\n";
}
