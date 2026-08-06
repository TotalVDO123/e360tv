<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $redirect_id = $input['redirect_id'] ?? '';
    $action = $input['action'] ?? '';
   
    if ($redirect_id && $action) {
        $stats_file = $redirect_id . '_stats.json';
        if (file_exists($stats_file)) {
            $stats = json_decode(file_get_contents($stats_file), true);
            $current_date = date('Y-m-d');
            $current_hour = date('H');
           
            if ($action === 'visit') {
                $stats['total_visits']++;
                if (!isset($stats['daily_stats'][$current_date])) {
                    $stats['daily_stats'][$current_date] = ['visits' => 0, 'redirects' => 0];
                }
                $stats['daily_stats'][$current_date]['visits']++;
               
                $hour_key = $current_date . '_' . $current_hour;
                if (!isset($stats['hourly_stats'][$hour_key])) {
                    $stats['hourly_stats'][$hour_key] = ['visits' => 0, 'redirects' => 0];
                }
                $stats['hourly_stats'][$hour_key]['visits']++;
            } elseif ($action === 'redirect') {
                $stats['redirects']++;
                if (!isset($stats['daily_stats'][$current_date])) {
                    $stats['daily_stats'][$current_date] = ['visits' => 0, 'redirects' => 0];
                }
                $stats['daily_stats'][$current_date]['redirects']++;
               
                $hour_key = $current_date . '_' . $current_hour;
                if (!isset($stats['hourly_stats'][$hour_key])) {
                    $stats['hourly_stats'][$hour_key] = ['visits' => 0, 'redirects' => 0];
                }
                $stats['hourly_stats'][$hour_key]['redirects']++;
            }
           
            file_put_contents($stats_file, json_encode($stats, JSON_PRETTY_PRINT), LOCK_EX);
            echo json_encode(['status' => 'success']);
        }
    }
}
?>