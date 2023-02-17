<?php

namespace App\Console;

use App\Console\Commands\RankCommand;
use App\Console\Commands\TestCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
//        $schedule->job(new TestCommand())->dailyAt('14:15')->appendOutputTo('./uploads/test1.log') ;
        $schedule->job(new RankCommand())->dailyAt('00:00')->appendOutputTo('./uploads/rank.log') ;

//        $schedule->command('test1')->dailyAt('11:59')->appendOutputTo('./uploads/test1.log') ;
//        $schedule->command('test1:handle')->dailyAt('11:40')->appendOutputTo('./uploads/test1.log') ;

//        $filePath =base_path()."\uploads\info.txt";
//
//        //   每天00:00 执行一次
//        $schedule->call(function () {
//            DB::table('alipay_coupons')->where(['id'=>5])->delete();
//            echo "执行成功";
//        })->dailyAt('11:26')
//            ->appendOutputTo($filePath);

        // 每天执行一次 调用对象
//        $schedule->call(new DeleteRecentUsers)->dailyAt('13:00');

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
