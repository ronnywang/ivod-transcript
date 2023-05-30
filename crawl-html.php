<?php

for ($v = max(intval(file_get_contents('current-id')), 146300); ; $v ++) {
    $url = sprintf("https://ivod.ly.gov.tw/Play/Clip/1M/%d", $v);
    $html_target = __DIR__ . "/html/{$v}.html";
    if (file_exists($html_target)) {
        continue;
    }
    error_log($url);
    $content = file_get_contents($url);
    if (!preg_match('#readyPlayer\("([^"]*)"#', $content, $matches)) {
        throw new Exception("readyPlayer not found {$url}");
    }
    file_put_contents('current-id', $v);
    file_put_contents($html_target, $content);
}
