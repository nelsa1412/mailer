<?php

/**
 * QuotaTracker class.
 *
 * Provide a data structure for storing and measure quota
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

abstract class QuotaTracker
{
    protected $series; 
    protected $interval;
    protected $quota; // in seconds

    const START_POINT = 0;
    const UNLIMITED = -1;

    /**
     * Constructor, modeling data from a JSON array 
     *
     * @param Array $series
     *
     * @return void
     */
    public function __construct($interval, $quota, $series = [])
    {
        $this->series = $series;
        $this->interval = \DateInterval::createFromDateString($interval);
        $this->quota = $quota;
    }
}
