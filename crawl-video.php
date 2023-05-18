<?php

$crawled = 0;
system("php crawl-html.php");

$v = max(intval(file_get_contents('current-id')), 146312);
for (; $v > 0; $v --) {
    $url = sprintf("https://ivod.ly.gov.tw/Play/Clip/1M/%d", $v);
    $html_target = __DIR__ . "/html/{$v}.html";
    if (!file_exists($html_target)) {
        file_put_contents($html_target, file_get_contents($url));
    }
    $content = file_get_contents($html_target);
    if (!preg_match('#readyPlayer\("([^"]*)"#', $content, $matches)) {
        throw new Exception("readyPlayer not found {$url}");
    }
    $video_url = $matches[1];
    if (!preg_match('#<strong>會議時間：</strong>([0-9-: ]+)#', $content, $matches)) {
        throw new Exception("會議時間 not found: $url");
    }
    $date = date('Ymd', strtotime($matches[1]));
    if (!file_exists(__DIR__ . "/output/{$date}")) {
        mkdir(__DIR__ . "/output/{$date}");
    }
    $target = __DIR__ . "/output/" . $date . '/1M_' . $v;
    if (file_exists($target)) {
        continue;
    }
    if (file_exists("output.mp4")) {
        unlink("output.mp4");
    }
    $cmd = sprintf("youtube-dl -o output.mp4 %s", escapeshellarg($video_url));
    system($cmd);
    if (!file_exists("output.mp4")) {
        continue;
        throw new Exception("download mp4 failed: $url");
    }
    if (file_exists("output.wav")) {
        unlink("output.wav");
    }
    $cmd = sprintf("ffmpeg -i output.mp4 -ar 16000 -ac 1 -c:a pcm_s16le output.wav");
    system($cmd);

    $tmp_target = __DIR__ . "/output/tmp-{$date}-1M_{$v}";
    $cmd = sprintf("./whisper.cpp/main -m ./whisper.cpp/models/ggml-medium.bin -l auto output.wav > %s", escapeshellarg($tmp_target));
    system($cmd);
    rename($tmp_target, $target);
    $crawled ++;
    if ($crawled > 5) break;
}
