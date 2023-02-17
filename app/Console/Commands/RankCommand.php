<?php
namespace App\Console\Commands;

use App\Models\MdArticle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RankCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rank';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '定时计算选手作品排名';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //  获取所有作品
        $rows = MdArticle::select('id')->orderByDesc('number_votes')->get()->toArray();
        foreach ($rows as $k=>$row)
        {
            $rank = $k + 1 ;
            $affected = DB::table('md_article')
                ->where('id', $row['id'])
                ->update(['rank' => $rank]);
        }

        return 0;
    }
}
