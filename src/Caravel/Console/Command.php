<?php

namespace Caravel\Console;

use Symfony\Component\Console\Command\Command as SCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends SCommand
{
    protected $input;  // InputInterface
    protected $output; // OutputInterface

    public function __construct()
    {
        parent::__construct();

        $this->configure();
    }

    protected function configure()
    {
        $this->setName($this->name);
        $this->setDescription($this->description)
        $this->caddArguments();
        $this->caddOptions();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        $this->run();
    }

    abstract public function run();

    abstract protected function getArguments();

    abstract protected function getOptions();

    public function produceHour($startTime, $endTime, $add = false)
    {
        $appDownloadHour = new AppDownloadHour();

        $startTs = strtotime($startTime);
        $endTs   = strtotime($endTime);

        $allRecords = array();
        for ($ts = $startTs; $ts <= $endTs; $ts += 3600) {
            $dataTime = date('Y-m-d H:00:00', $ts);

            $records = $this->getHourDownloadSource($dataTime);
            $records = $this->filter($records);
            foreach ($records as $v) {
                $appDownloadHour->saveOne($v, $dataTime);
            }

            $allRecords = array_merge($allRecords, $records);
        }

        return $allRecords;
    }

    public function produceDay($startTime, $endTime)
    {
        $appDownloadHour = new AppDownloadHour();
        $appDownloadDay  = new AppDownloadDay();

        $startTs = strtotime($startTime);
        $endTs   = strtotime($endTime);

        for ($ts = $startTs; $ts <= $endTs; $ts += 86400) {
            $dataTime = date('Y-m-d 00:00:00', $ts);
            $records = $appDownloadHour->getDayDownload($dataTime);
            $chunk = array_chunk($records, 100);
            foreach ($chunk as $v) {
                $appDownloadDay->saveBatch($v, $dataTime, 1);
            }
        }
    }

    public function produceWeek($startTime)
    {
        $appDownloadDay     = new AppDownloadDay();
        $appDownloadArchive = new AppDownloadArchive();

        $startTime = date('Y-m-d 00:00:00', strtotime($startTime));
        $endTime   = date('Y-m-d 00:00:00', strtotime($startTime) + 86400 * 6);
        $records = $appDownloadDay->getPeriodDownload($startTime, $endTime);
        $chunk = array_chunk($records, 100);
        foreach ($chunk as $v) {
            $appDownloadArchive->saveBatch($v, $startTime, 1);
        }
    }

    public function produceMonth($startTime)
    {
        $appDownloadDay     = new AppDownloadDay();
        $appDownloadArchive = new AppDownloadArchive();

        $startTime = date('Y-m-1 00:00:00', strtotime($startTime));
        $endTime   = date('Y-m-1 00:00:00', strtotime($startTime) + 86400 * 40);// 以上个月1号为起始，它的40天之后肯定落在第二个月
        $records = $appDownloadDay->getPeriodDownload($startTime, $endTime);
        $chunk = array_chunk($records, 100);
        foreach ($chunk as $v) {
            $appDownloadArchive->saveBatch($v, $startTime, 2);
        }
    }

    public function addHour($records)
    {
        $openSoft = new OpenSoft();

        foreach ($records as $v) {
            $openSoft->addDownloadTimes($v);
        }
    }

    /**
     * 小时数据入库过程中的处理
     */
    public function filter($records)
    {
        $appConfig  = new AppConfig();
        $weightList = $appConfig->getConfigWeight();

        foreach ($records as $k => $v) {
            $records[$k] = $this->weight($v, $weightList);
        }

        return $records;
    }

    /**
     * 小时数据入库过程中的权重处理
     */
    public function weight($record, $weightList)
    {
        if (!array_key_exists($record['soft_id'], $weightList)) {
            return $record;
        }

        $record['mobile_total'] = ceil($record['mobile_total'] * $weightList[$record['soft_id']]);
        $record['pc_total'] = ceil($record['pc_total'] * $weightList[$record['soft_id']]);
        $record['mobile_pure'] = ceil($record['mobile_pure'] * $weightList[$record['soft_id']]);
        $record['pc_pure'] = ceil($record['pc_pure'] * $weightList[$record['soft_id']]);
        $record['mobile_cut_total'] = ceil($record['mobile_cut_total'] * $weightList[$record['soft_id']]);
        $record['pc_cut_total'] = ceil($record['pc_cut_total'] * $weightList[$record['soft_id']]);
        $record['mobile_cut_pure'] = ceil($record['mobile_cut_pure'] * $weightList[$record['soft_id']]);
        $record['pc_cut_pure'] = ceil($record['pc_cut_pure'] * $weightList[$record['soft_id']]);
        $comment = json_decode($record['comment'], true);
        $comment[] = "*{$weightList[$record['soft_id']]}";
        $record['comment'] = json_encode($comment);

        return $record;
    }

    /**
     * 从数据分析组获得小时下载量以及刷量数据
     */
    public static function getHourDownloadSource($dataTime)
    {
        $tableDate = date('Ym', strtotime($dataTime));

        //$sql = "select * from sjfxh_download_control_hourstat_{$tableDate} WHERE time='$dataTime'";
        //$sql = "select * from app_d_{$tableDate} WHERE data_time='$dataTime'";
        $sql = "select * from app_d_201408 WHERE data_time='$dataTime'";

        $retry = 0;
        while (empty($result)) {
            try {
                //$store  = Helper\Store::getStore('sqoop_read');
                $store  = Helper\Store::getStore('app_download');
                $sth    = $store->prepare($sql);
                $sth->execute();
                $result = $sth->fetchAll(PDO::FETCH_ASSOC);

                if (empty($result)) {
                    Log::warning(implode("|", array(__METHOD__, "retry[{$retry}]", "源数据空:{$sql}")));
                    Helper\Store::retry($retry, 'backend:download', "{$dataTime}所在时段下载量未在预定时间推送", array(
                        'chenhong3@360.cn',
                        //'fangxinghe@360.cn',
                        //'weiqin@360.cn',
                    ), array(
                        //'18600740924', //胡聪
                        //'18650396669', //方兴和
                        //'18611552377', //陈竑
                        //'18201013793', //魏琴
                        //'13810639660', //田野
                        //'13811074478', //刘凯寅
                        //'18611991418', //周明宸
                        //'13269283144', //卢志鹏
                        //'15810086481', //范文韬
                        //'18611699856', //蔺蔺
                        //'13811365648', //王晨
                        //'13810106135', //王卫平
                        //'18611689201', //韩三普
                    ));
                } else {
                    sleep(1);
                    $sth->execute();
                    $result2 = $sth->fetchAll(PDO::FETCH_ASSOC);
                    // 检查数据是否仍在生成中
                    if (count($result) != count($result2)) {
                        Log::warning(implode("|", array(__METHOD__, "countNotMatch", "正在插入:{$sql}")));
                        unset($result, $result2);
                    }
                }
            } catch (Exception $e) {
                Log::warning(implode("|", array(__METHOD__, "retry[{$retry}]", "连接异常:{$e->getMessage()}:$sql")));
                Helper\Store::retry($retry, 'command:download');
            }
        }

        $records = array();
        foreach ($result as $v) {
            $records[] = array(
                'soft_id'          => $v['soft_id'],

                //'mobile_total'     => $v['sjdown'],
                //'pc_total'         => $v['pcdown'],
                //'mobile_cut_total' => $v['sjdown_cheat'],
                //'pc_cut_total'     => $v['pcdown_cheat'],
                //'mobile_pure'      => $v['sjdown_net'],
                //'pc_pure'          => $v['pcdown_net'],
                //'mobile_cut_pure'  => $v['sjdown_cheat_net'],
                //'pc_cut_pure'      => $v['pcdown_cheat_net'],

                'mobile_total'     => $v['mobile_total'],
                'pc_total'         => $v['pc_total'],
                'mobile_cut_total' => $v['mobile_cut_total'],
                'pc_cut_total'     => $v['pc_cut_total'],
                'mobile_pure'      => $v['mobile_pure'],
                'pc_pure'          => $v['pc_pure'],
                'mobile_cut_pure'  => $v['mobile_cut_pure'],
                'pc_cut_pure'      => $v['pc_cut_pure'],

                'data_time'        => $dataTime,
                'comment'          => json_encode(array()),
            );
        }

        return $records;
    }
    public function produceHour($startTime, $endTime, $add = false)
    {
        $appDownloadHour = new AppDownloadHour();

        $startTs = strtotime($startTime);
        $endTs   = strtotime($endTime);

        $allRecords = array();
        for ($ts = $startTs; $ts <= $endTs; $ts += 3600) {
            $dataTime = date('Y-m-d H:00:00', $ts);

            $records = $this->getHourDownloadSource($dataTime);
            $records = $this->filter($records);
            foreach ($records as $v) {
                $appDownloadHour->saveOne($v, $dataTime);
            }

            $allRecords = array_merge($allRecords, $records);
        }

        return $allRecords;
    }

    public function produceDay($startTime, $endTime)
    {
        $appDownloadHour = new AppDownloadHour();
        $appDownloadDay  = new AppDownloadDay();

        $startTs = strtotime($startTime);
        $endTs   = strtotime($endTime);

        for ($ts = $startTs; $ts <= $endTs; $ts += 86400) {
            $dataTime = date('Y-m-d 00:00:00', $ts);
            $records = $appDownloadHour->getDayDownload($dataTime);
            $chunk = array_chunk($records, 100);
            foreach ($chunk as $v) {
                $appDownloadDay->saveBatch($v, $dataTime, 1);
            }
        }
    }

    public function produceWeek($startTime)
    {
        $appDownloadDay     = new AppDownloadDay();
        $appDownloadArchive = new AppDownloadArchive();

        $startTime = date('Y-m-d 00:00:00', strtotime($startTime));
        $endTime   = date('Y-m-d 00:00:00', strtotime($startTime) + 86400 * 6);
        $records = $appDownloadDay->getPeriodDownload($startTime, $endTime);
        $chunk = array_chunk($records, 100);
        foreach ($chunk as $v) {
            $appDownloadArchive->saveBatch($v, $startTime, 1);
        }
    }

    public function produceMonth($startTime)
    {
        $appDownloadDay     = new AppDownloadDay();
        $appDownloadArchive = new AppDownloadArchive();

        $startTime = date('Y-m-1 00:00:00', strtotime($startTime));
        $endTime   = date('Y-m-1 00:00:00', strtotime($startTime) + 86400 * 40);// 以上个月1号为起始，它的40天之后肯定落在第二个月
        $records = $appDownloadDay->getPeriodDownload($startTime, $endTime);
        $chunk = array_chunk($records, 100);
        foreach ($chunk as $v) {
            $appDownloadArchive->saveBatch($v, $startTime, 2);
        }
    }

    public function addHour($records)
    {
        $openSoft = new OpenSoft();

        foreach ($records as $v) {
            $openSoft->addDownloadTimes($v);
        }
    }

    /**
     * 小时数据入库过程中的处理
     */
    public function filter($records)
    {
        $appConfig  = new AppConfig();
        $weightList = $appConfig->getConfigWeight();

        foreach ($records as $k => $v) {
            $records[$k] = $this->weight($v, $weightList);
        }

        return $records;
    }

    /**
     * 小时数据入库过程中的权重处理
     */
    public function weight($record, $weightList)
    {
        if (!array_key_exists($record['soft_id'], $weightList)) {
            return $record;
        }

        $record['mobile_total'] = ceil($record['mobile_total'] * $weightList[$record['soft_id']]);
        $record['pc_total'] = ceil($record['pc_total'] * $weightList[$record['soft_id']]);
        $record['mobile_pure'] = ceil($record['mobile_pure'] * $weightList[$record['soft_id']]);
        $record['pc_pure'] = ceil($record['pc_pure'] * $weightList[$record['soft_id']]);
        $record['mobile_cut_total'] = ceil($record['mobile_cut_total'] * $weightList[$record['soft_id']]);
        $record['pc_cut_total'] = ceil($record['pc_cut_total'] * $weightList[$record['soft_id']]);
        $record['mobile_cut_pure'] = ceil($record['mobile_cut_pure'] * $weightList[$record['soft_id']]);
        $record['pc_cut_pure'] = ceil($record['pc_cut_pure'] * $weightList[$record['soft_id']]);
        $comment = json_decode($record['comment'], true);
        $comment[] = "*{$weightList[$record['soft_id']]}";
        $record['comment'] = json_encode($comment);

        return $record;
    }

    /**
     * 从数据分析组获得小时下载量以及刷量数据
     */
    public static function getHourDownloadSource($dataTime)
    {
        $tableDate = date('Ym', strtotime($dataTime));

        //$sql = "select * from sjfxh_download_control_hourstat_{$tableDate} WHERE time='$dataTime'";
        //$sql = "select * from app_d_{$tableDate} WHERE data_time='$dataTime'";
        $sql = "select * from app_d_201408 WHERE data_time='$dataTime'";

        $retry = 0;
        while (empty($result)) {
            try {
                //$store  = Helper\Store::getStore('sqoop_read');
                $store  = Helper\Store::getStore('app_download');
                $sth    = $store->prepare($sql);
                $sth->execute();
                $result = $sth->fetchAll(PDO::FETCH_ASSOC);

                if (empty($result)) {
                    Log::warning(implode("|", array(__METHOD__, "retry[{$retry}]", "源数据空:{$sql}")));
                    Helper\Store::retry($retry, 'backend:download', "{$dataTime}所在时段下载量未在预定时间推送", array(
                        'chenhong3@360.cn',
                        //'fangxinghe@360.cn',
                        //'weiqin@360.cn',
                    ), array(
                        //'18600740924', //胡聪
                        //'18650396669', //方兴和
                        //'18611552377', //陈竑
                        //'18201013793', //魏琴
                        //'13810639660', //田野
                        //'13811074478', //刘凯寅
                        //'18611991418', //周明宸
                        //'13269283144', //卢志鹏
                        //'15810086481', //范文韬
                        //'18611699856', //蔺蔺
                        //'13811365648', //王晨
                        //'13810106135', //王卫平
                        //'18611689201', //韩三普
                    ));
                } else {
                    sleep(1);
                    $sth->execute();
                    $result2 = $sth->fetchAll(PDO::FETCH_ASSOC);
                    // 检查数据是否仍在生成中
                    if (count($result) != count($result2)) {
                        Log::warning(implode("|", array(__METHOD__, "countNotMatch", "正在插入:{$sql}")));
                        unset($result, $result2);
                    }
                }
            } catch (Exception $e) {
                Log::warning(implode("|", array(__METHOD__, "retry[{$retry}]", "连接异常:{$e->getMessage()}:$sql")));
                Helper\Store::retry($retry, 'command:download');
            }
        }

        $records = array();
        foreach ($result as $v) {
            $records[] = array(
                'soft_id'          => $v['soft_id'],

                //'mobile_total'     => $v['sjdown'],
                //'pc_total'         => $v['pcdown'],
                //'mobile_cut_total' => $v['sjdown_cheat'],
                //'pc_cut_total'     => $v['pcdown_cheat'],
                //'mobile_pure'      => $v['sjdown_net'],
                //'pc_pure'          => $v['pcdown_net'],
                //'mobile_cut_pure'  => $v['sjdown_cheat_net'],
                //'pc_cut_pure'      => $v['pcdown_cheat_net'],

                'mobile_total'     => $v['mobile_total'],
                'pc_total'         => $v['pc_total'],
                'mobile_cut_total' => $v['mobile_cut_total'],
                'pc_cut_total'     => $v['pc_cut_total'],
                'mobile_pure'      => $v['mobile_pure'],
                'pc_pure'          => $v['pc_pure'],
                'mobile_cut_pure'  => $v['mobile_cut_pure'],
                'pc_cut_pure'      => $v['pc_cut_pure'],

                'data_time'        => $dataTime,
                'comment'          => json_encode(array()),
            );
        }

        return $records;
    }
    public function produceHour($startTime, $endTime, $add = false)
    {
        $appDownloadHour = new AppDownloadHour();

        $startTs = strtotime($startTime);
        $endTs   = strtotime($endTime);

        $allRecords = array();
        for ($ts = $startTs; $ts <= $endTs; $ts += 3600) {
            $dataTime = date('Y-m-d H:00:00', $ts);

            $records = $this->getHourDownloadSource($dataTime);
            $records = $this->filter($records);
            foreach ($records as $v) {
                $appDownloadHour->saveOne($v, $dataTime);
            }

            $allRecords = array_merge($allRecords, $records);
        }

        return $allRecords;
    }

    public function produceDay($startTime, $endTime)
    {
        $appDownloadHour = new AppDownloadHour();
        $appDownloadDay  = new AppDownloadDay();

        $startTs = strtotime($startTime);
        $endTs   = strtotime($endTime);

        for ($ts = $startTs; $ts <= $endTs; $ts += 86400) {
            $dataTime = date('Y-m-d 00:00:00', $ts);
            $records = $appDownloadHour->getDayDownload($dataTime);
            $chunk = array_chunk($records, 100);
            foreach ($chunk as $v) {
                $appDownloadDay->saveBatch($v, $dataTime, 1);
            }
        }
    }

    public function produceWeek($startTime)
    {
        $appDownloadDay     = new AppDownloadDay();
        $appDownloadArchive = new AppDownloadArchive();

        $startTime = date('Y-m-d 00:00:00', strtotime($startTime));
        $endTime   = date('Y-m-d 00:00:00', strtotime($startTime) + 86400 * 6);
        $records = $appDownloadDay->getPeriodDownload($startTime, $endTime);
        $chunk = array_chunk($records, 100);
        foreach ($chunk as $v) {
            $appDownloadArchive->saveBatch($v, $startTime, 1);
        }
    }

    public function produceMonth($startTime)
    {
        $appDownloadDay     = new AppDownloadDay();
        $appDownloadArchive = new AppDownloadArchive();

        $startTime = date('Y-m-1 00:00:00', strtotime($startTime));
        $endTime   = date('Y-m-1 00:00:00', strtotime($startTime) + 86400 * 40);// 以上个月1号为起始，它的40天之后肯定落在第二个月
        $records = $appDownloadDay->getPeriodDownload($startTime, $endTime);
        $chunk = array_chunk($records, 100);
        foreach ($chunk as $v) {
            $appDownloadArchive->saveBatch($v, $startTime, 2);
        }
    }

    public function addHour($records)
    {
        $openSoft = new OpenSoft();

        foreach ($records as $v) {
            $openSoft->addDownloadTimes($v);
        }
    }

    /**
     * 小时数据入库过程中的处理
     */
    public function filter($records)
    {
        $appConfig  = new AppConfig();
        $weightList = $appConfig->getConfigWeight();

        foreach ($records as $k => $v) {
            $records[$k] = $this->weight($v, $weightList);
        }

        return $records;
    }

    /**
     * 小时数据入库过程中的权重处理
     */
    public function weight($record, $weightList)
    {
        if (!array_key_exists($record['soft_id'], $weightList)) {
            return $record;
        }

        $record['mobile_total'] = ceil($record['mobile_total'] * $weightList[$record['soft_id']]);
        $record['pc_total'] = ceil($record['pc_total'] * $weightList[$record['soft_id']]);
        $record['mobile_pure'] = ceil($record['mobile_pure'] * $weightList[$record['soft_id']]);
        $record['pc_pure'] = ceil($record['pc_pure'] * $weightList[$record['soft_id']]);
        $record['mobile_cut_total'] = ceil($record['mobile_cut_total'] * $weightList[$record['soft_id']]);
        $record['pc_cut_total'] = ceil($record['pc_cut_total'] * $weightList[$record['soft_id']]);
        $record['mobile_cut_pure'] = ceil($record['mobile_cut_pure'] * $weightList[$record['soft_id']]);
        $record['pc_cut_pure'] = ceil($record['pc_cut_pure'] * $weightList[$record['soft_id']]);
        $comment = json_decode($record['comment'], true);
        $comment[] = "*{$weightList[$record['soft_id']]}";
        $record['comment'] = json_encode($comment);

        return $record;
    }

    /**
     * 从数据分析组获得小时下载量以及刷量数据
     */
    public static function getHourDownloadSource($dataTime)
    {
        $tableDate = date('Ym', strtotime($dataTime));

        //$sql = "select * from sjfxh_download_control_hourstat_{$tableDate} WHERE time='$dataTime'";
        //$sql = "select * from app_d_{$tableDate} WHERE data_time='$dataTime'";
        $sql = "select * from app_d_201408 WHERE data_time='$dataTime'";

        $retry = 0;
        while (empty($result)) {
            try {
                //$store  = Helper\Store::getStore('sqoop_read');
                $store  = Helper\Store::getStore('app_download');
                $sth    = $store->prepare($sql);
                $sth->execute();
                $result = $sth->fetchAll(PDO::FETCH_ASSOC);

                if (empty($result)) {
                    Log::warning(implode("|", array(__METHOD__, "retry[{$retry}]", "源数据空:{$sql}")));
                    Helper\Store::retry($retry, 'backend:download', "{$dataTime}所在时段下载量未在预定时间推送", array(
                        'chenhong3@360.cn',
                        //'fangxinghe@360.cn',
                        //'weiqin@360.cn',
                    ), array(
                        //'18600740924', //胡聪
                        //'18650396669', //方兴和
                        //'18611552377', //陈竑
                        //'18201013793', //魏琴
                        //'13810639660', //田野
                        //'13811074478', //刘凯寅
                        //'18611991418', //周明宸
                        //'13269283144', //卢志鹏
                        //'15810086481', //范文韬
                        //'18611699856', //蔺蔺
                        //'13811365648', //王晨
                        //'13810106135', //王卫平
                        //'18611689201', //韩三普
                    ));
                } else {
                    sleep(1);
                    $sth->execute();
                    $result2 = $sth->fetchAll(PDO::FETCH_ASSOC);
                    // 检查数据是否仍在生成中
                    if (count($result) != count($result2)) {
                        Log::warning(implode("|", array(__METHOD__, "countNotMatch", "正在插入:{$sql}")));
                        unset($result, $result2);
                    }
                }
            } catch (Exception $e) {
                Log::warning(implode("|", array(__METHOD__, "retry[{$retry}]", "连接异常:{$e->getMessage()}:$sql")));
                Helper\Store::retry($retry, 'command:download');
            }
        }

        $records = array();
        foreach ($result as $v) {
            $records[] = array(
                'soft_id'          => $v['soft_id'],

                //'mobile_total'     => $v['sjdown'],
                //'pc_total'         => $v['pcdown'],
                //'mobile_cut_total' => $v['sjdown_cheat'],
                //'pc_cut_total'     => $v['pcdown_cheat'],
                //'mobile_pure'      => $v['sjdown_net'],
                //'pc_pure'          => $v['pcdown_net'],
                //'mobile_cut_pure'  => $v['sjdown_cheat_net'],
                //'pc_cut_pure'      => $v['pcdown_cheat_net'],

                'mobile_total'     => $v['mobile_total'],
                'pc_total'         => $v['pc_total'],
                'mobile_cut_total' => $v['mobile_cut_total'],
                'pc_cut_total'     => $v['pc_cut_total'],
                'mobile_pure'      => $v['mobile_pure'],
                'pc_pure'          => $v['pc_pure'],
                'mobile_cut_pure'  => $v['mobile_cut_pure'],
                'pc_cut_pure'      => $v['pc_cut_pure'],

                'data_time'        => $dataTime,
                'comment'          => json_encode(array()),
            );
        }

        return $records;
    }

    protected function caddArguments()
    {
        foreach ($this->getArguments() as $v) {
            $this->addArgument(
                $v[0], // name
                $v[1], // mode
                $v[2], // description
                $v[3]  // defaultValue
            );
        }
    }

    protected function caddOptions()
    {
        foreach ($this->getOptions() as $v) {
            $this->addOption(
                $v[0], // name
                $v[1], // shortcut
                $v[2], // mode
                $v[3], // description
                $v[4]  // defaultValue
            );
        }
    }
}
