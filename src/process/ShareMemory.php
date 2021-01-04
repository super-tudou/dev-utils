<?php
/**
 * Created by PhpStorm.
 * @file   ShareMemory.php
 * @author 李锦 <jin.li@vhall.com>
 * @date   2021/1/3 下午5:27
 * @desc   ShareMemory.php
 */

namespace Utils\process;

/**
 * 多进程共享内存
 * Class ShareMemory
 */
class ShareMemory
{
    private $processArr = [];
    private $shmId = null;
    private $semId = null;
    private $ftokId = null;

    private $projectId = 1;


    /**
     * 共享内存参数数量
     * @var int
     */
    private $paramsCount = 0;


    /**
     * 共享内存配置类似
     * @var array
     */
    private $shareKeyConfig = [];

    /**
     * @param $key
     * @return int[]
     */
    public function initParams($key)
    {
        if (isset($this->shareKeyConfig[$key])) {
            return $this->shareKeyConfig[$key];
        } else {
            $this->shareKeyConfig[$key] = [
                "start" => 128 * $this->paramsCount,
                "end" => 128 * ($this->paramsCount + 1),
            ];
            $this->paramsCount++;
            return $this->shareKeyConfig[$key];
        }
    }

    /**
     * 初始化基于共享内存的锁
     * @return $this
     */
    public function initShareMemoryLock()
    {
        $this->checkFunctionExists();
        $this->createShareMemoryCache($this->projectId);
        $this->createSemaphore($this->projectId);
        return $this;
    }


    /**
     * 检测系统是否开启共享内存与信号量函数
     * @return bool
     */
    private function checkFunctionExists()
    {
        $requireFunc = array(
            "ftok",
            "shmop_open",
            "shmop_write",
            "shmop_read",
            "shmop_delete",
            "shmop_close",
            "shmop_size",
            "sem_get",
            "sem_acquire",
            "sem_release",
            "sem_remove"
        );
        foreach ($requireFunc as $func) {
            if (!function_exists($func)) {
                die("$func 方法不存在");
            }
        }
        return true;
    }

    /**
     * 创建信号量
     * @param $projectId
     * @return bool
     */
    private function createSemaphore($projectId)
    {
        //获取信号灯ID
        $this->semId = sem_get($this->ftokId);
        return true;
    }


    /**
     * 创建共享内存
     * 单个文件多个方法调用，必须使用不同的projectId
     * @param $projectId
     * @param int $size
     * @return bool
     */
    private function createShareMemoryCache($projectId, $size = 2048)
    {
        set_time_limit(0);
        if ($projectId < 1 || $projectId > 255) {
            die("projectId 的取值范围在1-255。" . PHP_EOL);
        }
        $this->ftokId = ftok(__FILE__, $projectId);
        $shmId = @shmop_open($this->ftokId, "c", 0644, $size);
        if (!is_resource($shmId)) {
            die("shmop_open(): unable to attach or create shared memory segment 'Permission denied'" . PHP_EOL);
        }
        $this->shmId = $shmId;
        return true;
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function set(string $key, string $value)
    {
        $this->initParams($key);
        return $this->setValue($key, $value);
    }

    /**
     * @param string $key
     * @return string
     */
    public function get(string $key)
    {
        $this->initParams($key);
        return $this->getValue($key);
    }

    /**
     * 设置共享内存
     * @param string $key
     * @param string $val
     * @return $this
     */
    private function setValue(string $key, string $val)
    {
        if (!isset($this->shareKeyConfig[$key])) {
            die('请先配置共享内存的key!' . PHP_EOL);
        }
        $config = $this->shareKeyConfig[$key];
        shmop_write($this->shmId, $val, $config['start']);
        return $this;
    }

    /**
     * 根据key,获取对应的value
     * @param string $key
     * @return string
     */
    private function getValue(string $key)
    {
        //sem_acquire —获取信号量
        sem_acquire($this->semId);
        if (!isset($this->shareKeyConfig[$key])) {
            die('请先配置共享内存的key!' . PHP_EOL);
        }
        $config = $this->shareKeyConfig[$key];
        $val = shmop_read($this->shmId, $config['start'], ($config['end'] - $config['start']));
        //sem_release —释放信号量
        sem_release($this->semId);
        return trim($val);
    }


    /**
     * destruct
     */
    public function __destruct()
    {
        //$this->closeShareMemoryLock();
    }

    /**
     * 关闭共享内存
     * @return bool
     */
    public function closeShareMemoryLock()
    {
        $this->closeSemaphore();
        $this->closeShareMemory();
        return true;
    }

    /**
     * 关闭信号量
     * @return bool
     */
    private function closeSemaphore()
    {
        sem_remove($this->semId);
        return true;
    }

    /**
     * 关闭共享内存快
     */
    private function closeShareMemory()
    {
        //删除共享内存块
        shmop_delete($this->shmId);
        //关闭共享内存块
        shmop_close($this->shmId);
        return true;
    }


}
