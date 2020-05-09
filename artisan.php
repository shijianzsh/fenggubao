<?php
$host = 'http://apigrb.58dzt.com';
$logFolder = 'zhongwy';

$opts = [
    'http' => [
        'method' => "GET",
        'timeout' => 10,
    ]
];
$context = stream_context_create($opts);
$content = file_get_contents(sprintf('%s/ZhongWY/price', $host), false, $context) . PHP_EOL;


$logFile = sprintf('record/%s/%s.log.php', $logFolder, date('Y-m-d'));

//第一次生成文件时,执行特殊的写入
if (!file_exists($logFile)) {
    $content = '<?php exit; ?>' . PHP_EOL . $content;
}

//写入日志
$dir = dirname($logFile);
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

file_put_contents($logFile, sprintf('[%s] %s', date('Y-m-d H:i:s'), $content), FILE_APPEND);