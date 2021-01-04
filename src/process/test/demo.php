<?php
/**
 * Created by PhpStorm.
 * @file   demo.php
 * @author 李锦 <jin.li@vhall.com>
 * @date   2021/1/4 下午4:00
 * @desc   demo.php
 */

include_once "./Process.php";

$dataList = [];
for ($i = 0; $i < 500; $i++) {
    $dataList[$i % 10][] = $i;
}

$demo = new Process();
$demo->processCount = 10;
$demo->totalProgress = 500;
$demo->callback = function (Process $process, $sign) use ($dataList) {
    $process->setProcess();
    foreach ($dataList[$sign] as $item) {
        sleep(rand(0, 3));
        $process->setProcess();
    }
};
$demo->run();
