<?php

/**
 * QuotaTrackerRedis class.
 *
 * Provide a data structure/wrapper for storing and measure quota using Redis
 *
 * LICENSE: This product includes software developed at
 * the Acelle Co., Ltd. (http://acellemail.com/).
 *
 * @category   Acelle Library
 *
 * @author     N. Pham <n.pham@acellemail.com>
 * @author     L. Pham <l.pham@acellemail.com>
 * @copyright  Acelle Co., Ltd
 * @license    Acelle Co., Ltd
 *
 * @version    1.0
 *
 * @link       http://acellemail.com
 * @todo separate the time-series and the quota stuffs
 */

namespace Acelle\Library;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class QuotaTrackerRedis extends QuotaTracker implements QuotaTrackerInterface
{
    protected $redis;
    const REDIS_KEY = 'acellemail_series';

    /**
     * Constructor, modeling data from a JSON array 
     *
     * @param Array $series
     *
     * @return void
     */
    public function __construct($redis, $interval, $quota)
    {
        parent::__construct($interval, $quota);
        $this->redis = $redis;
        $this->init();
    }

    /**
     * Push initial data to Redis server: only $series needs to be stored
     *
     * @param Array $series
     *
     * @return void
     */
    public function init()
    {
        $this->redis->del(self::REDIS_KEY);
    }
    
    /**
     * Renew the quota data for the tracker
     *
     * @param Array $series
     * @return void
     */
    public function renew($series)
    {
        if (!empty($series)) {
            $this->redis->rpush(self::REDIS_KEY, $series);
        }
    }

    /**
     * Check if over quota, add a time point
     *
     * @param Timestamps $timePoint
     *
     * @return void
     */
    public function add(Carbon $timePoint = NULL) {
        if (!isset($timePoint)) {
            $timePoint = Carbon::now();
        }

        if ($this->check($timePoint)) {
            $this->redis->rpush(self::REDIS_KEY, $timePoint->timestamp);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if over quota
     *
     * @param Timestamps $timePoint
     *
     * @return void
     */
    public function check(Carbon $timePoint = NULL) {
        if ($this->quota == self::UNLIMITED) {
            return true;
        }

        if (!isset($timePoint)) {
            $timePoint = Carbon::now();
        }

        $this->shiftBy($timePoint);
        //echo "Usage " . $this->usage() . "; Quota " . $this->quota . "\n";

        if ($this->usage() >= $this->quota) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get the first data point of the time series
     *
     * @return Mixed data point
     */
    public function first()
    {
        return $this->redis->lindex(self::REDIS_KEY, 0);
    }

    /**
     * Get the last data point of the time series
     *
     * @return Mixed data point
     */
    public function last()
    {
        $size = $this->redis->llen(self::REDIS_KEY);
        return $this->redis->lindex(self::REDIS_KEY, $size - 1);
    }

    /**
     * Count the time series length
     *
     * @return Integer count
     */
    public function usage()
    {
        return $this->redis->llen(self::REDIS_KEY);
    }
    
    /**
     * Shift the time series until its range fits the new time point
     *
     * @param Timestamps $timePoint
     *
     * @return void
     */
    private function shiftBy(Carbon $timePoint)
    {
        if ($this->redis->llen(self::REDIS_KEY) == 0) {
            return;
        }
        
        $cutOff = $timePoint->copy()->sub($this->interval)->timestamp;
        
        $first = $this->redis->lindex(self::REDIS_KEY, 0);
        while(!is_null($first) && $first < $cutOff ) {
            $this->redis->lpop(self::REDIS_KEY);
            $first = $this->redis->lindex(self::REDIS_KEY, 0);
        }
    }

    /**
     * Get the quota data series
     * @return float
     */
    public function getSeries()
    {
        return $this->redis->lrange(self::REDIS_KEY, 0, $this->redis->llen(self::REDIS_KEY));
    }

    /**
     * Get usage percentage
     *
     * @return float
     */
    public function usagePercentage()
    {
        return (float) $this->usage() / $this->quota;
    }

    public function showSeries() {
        echo "\n------------------------------------------\n";
        foreach($this->getSeries() as $t) {
            echo Carbon::createFromTimestamp($t)->formatLocalized('%H:%M:%S') . " | "; 
        }
        echo "  -->  " . Carbon::now()->formatLocalized("%H:%M:%S") . "\n------------------------------------------\n";
    }
}
