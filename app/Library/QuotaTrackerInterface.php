<?php

namespace Acelle\Library;
use Carbon\Carbon;

interface QuotaTrackerInterface
{
    /**
     * Check if over quota, add a time point
     *
     * @param Timestamps $timePoint
     * @return void
     */
    public function add(Carbon $timePoint = NULL);

    /**
     * Check if over quota
     *
     * @param Timestamps $timePoint
     * @return void
     */
    public function check(Carbon $timePoint = NULL);

    /**
     * Get the first data point of the time series
     * @return Mixed data point
     */
    public function first();

    /**
     * Get the last data point of the time series
     * @return Mixed data point
     */
    public function last();

    /**
     * Count the time series length
     * @return Integer count
     */
    public function usage();

    /**
     * Get usage percentage
     * @return float
     */
    public function usagePercentage();

    /**
     * Get the quota data series
     * @return float
     */
    public function getSeries();
}
