<?php
/**
 * Created by PhpStorm.
 * @file   Process.php
 * @author 李锦 <jin.li@vhall.com>
 * @date   2021/1/3 下午6:30
 * @desc   Process.php
 */

namespace Utils\process;

/**
 * 多进程
 * Class Process
 */
class Process
{
    private $processList = [];
    /**
     *
     * @var ShareMemory
     */
    private $memory = null;
    /**
     * @var \Closure
     */
    public $callback = null;

    /**
     * @var int
     */
    public $processCount = 10;
    /**
     * 进度条总数
     * @var int
     */
    public $totalProgress = 0;

    /**
     * 初始化参数
     * Process constructor.
     */
    public function __construct()
    {
        $this->memory = new ShareMemory();
        return true;
    }

    /**
     * show process
     * @param $current
     */
    public function renderProgress($current)
    {
        $middleNumber = 50 / $this->totalProgress;
        $width = $this->totalProgress * $middleNumber;
        printf("progress: [%-{$width}s] %d%% Done\r", str_repeat('#', $current * $middleNumber), $current / $this->totalProgress * 100);
    }

    /**
     * 启动进程
     */
    public function run()
    {
        $this->memory->initShareMemoryLock();
        for ($i = 0; $i < $this->processCount; $i++) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                die("创建子进程失败");
            } elseif ($pid > 0) {
                $this->processList[$pid] = $pid;
            } else {
                $this->executeAction($i);
                exit();
            }
        }
        $this->waitProcess();
        $this->memory->closeShareMemoryLock();
    }

    /**
     * child action execute
     * @param $sign
     * @return bool
     */
    public function executeAction($sign)
    {
        $function = $this->callback;
        $function($this, $sign);
//        $this->setProcess();
        return true;
    }

    /**
     * set process
     */
    public function setProcess()
    {
        $this->incr("consume");
        $num = (int)$this->memory->get('consume');
        $this->renderProgress($num);
    }

    /**
     * @return bool
     */
    public function waitProcess()
    {
        while (count($this->processList)) {
            $childPid = pcntl_wait($status);
            if ($childPid > 0) {
                unset($this->processList[$childPid]);
            }
        }
        return true;
    }

    /**
     * 计数器
     * @param $key
     * @param int $incr
     */
    public function incr($key, $incr = 1)
    {
        $num = (int)$this->memory->get($key);
        $this->memory->set($key, $num + $incr);
    }

    /**
     * @return int
     */
    public function getProcessCount()
    {
        return count($this->processList);
    }
}

