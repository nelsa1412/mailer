<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Acelle\Model\User;
use Carbon\Carbon;

class UserQuotaTest extends TestCase
{
    /**
     * Test User quota usage
     */
    public function testUserQuotaUsage()
    {
        // setup assumptions
        $user = $this->getMockBuilder(Customer::class)
                     ->setMethods(['getQuotaIntervalString', 'getSendingQuota', 'renewQuotaTracker', 'getSendingQuotaLockFile', 'getCurrentSubscription'])
                     ->disableOriginalConstructor()
                     ->disableOriginalClone()
                     ->disableArgumentCloning()
                     ->disallowMockingUnknownTypes()
                     ->getMock();

        // assume that REDIS is not enabled (then QuotaTrackerStd is used)
        // and user quota is: 3 emails every 1 minute
        //\Config::shouldReceive('get')
        //            ->once()
        //            ->with('app.redis_enabled')
        //            ->andReturn(false);

        $user->method('getQuotaIntervalString')->willReturn('1 minute');
        $user->method('getSendingQuota')->willReturn('3');
        $user->method('getSendingQuotaLockFile')->willReturn('/tmp/' . uniqid());
        $user->method('getCurrentSubscription')
             ->willReturn(json_decode(json_encode(['start' => (new Carbon('3 days ago'))->timestamp, 'max' => 50000)));

        // TEST IF QUOTA IS CORRECTLY ENFORCED
        // no usage yet
        $this->assertFalse($user->overQuota());

        // use 3 slots out of 3 slots allowed -> ok
        $this->assertTrue($user->countUsage());
        $this->assertTrue($user->countUsage());
        $this->assertTrue($user->countUsage());

        // use extra slot -> failed
        $this->assertTrue($user->overQuota());
        $this->assertFalse($user->countUsage());

        sleep(61);
        // more than 1 minute has passed, now OKIE
        $this->assertFalse($user->overQuota());

        // TEST IF THE TIME SERIES IS CORRECTLY RECORDED
        $t1 = Carbon::now();
        $user->countUsage($t1);
        sleep(20);
        $t2 = Carbon::now();
        $user->countUsage($t2);

        // now $t1 is the first point of the time series
        $this->assertTrue($user->getQuotaTracker()->getSeries()[0] == $t1->timestamp);

        sleep(50);
        // now $t2 becomes the first point of the time series
        $user->overQuota(); // trigger recalculation of time series
        $this->assertTrue($user->getQuotaTracker()->getSeries()[0] == $t2->timestamp);
    }
}
