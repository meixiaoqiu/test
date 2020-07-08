<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ClientException;

class qiangdan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qiangdan {sess_id?}';

    /**
     * gxn抢单
     *
     * @var string
     */
    protected $description = 'qiangdan';

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
        $server='https://s.yyovv.com/';
        $sess_id='d348e5c8-f4f5-4770-956f-984f43f48e5c';
        if (!empty($this->argument('sess_id'))) {
            $sess_id=$this->argument('sess_id');
        }
        //{"status":200,"message":"成功","release_number":"113","started_at":"Mon Jul 06 22:16:11 CST 2020","data":[{"source":"gpay_c2c_jiesuan","ext_id":17848,"account_number":"6217 0006 0000 2034 008","account_name":"蔡庆珍","bank_name":"中国建设银行","open_address":null,"amount":16000.00,"bonus":16.00,"fuyan":"","seller":"","until":"2020-07-07 22:45:04","itime":"2020-07-07 22:29:05"}]}

        //{"status":200,"message":"成功","release_number":"113","started_at":"Mon Jul 06 22:16:11 CST 2020","data":[{"source":"gpay_c2c_jiesuan","ext_id":17862,"account_number":"6226621402436198","account_name":"刘村","bank_name":"中国光大银行","open_address":null,"amount":10500.00,"bonus":5.25,"fuyan":"","seller":"","until":"2020-07-08 06:39:30","itime":"2020-07-08 06:23:28"}]}

        $test='{"status":200,"message":"成功","release_number":"113","started_at":"Mon Jul 06 22:16:11 CST 2020","data":[{"source":"gpay_c2c_jiesuan","ext_id":17863,"account_number":"6217003210015462001","account_name":"杨海花","bank_name":"建设银行","open_address":null,"amount":40000.00,"bonus":5.00,"fuyan":"","seller":"","until":"2020-07-08 07:16:45","itime":"2020-07-08 07:00:10"}]}';

        //$json=file_get_contents($server.'scalper-console/list_c2c_deposits?sess_id='.$sess_id);
        $json=$test;

        $client = new Client([
          // Base URI is used with relative requests
          'base_uri' => $server
          // You can set any number of default request options.
          //'timeout'  => 10.0,
        ]);

        $requests=function($total) use($client,$sess_id){
          for ($i=0;$i<100;$i++) {
                yield function() use ($client,$sess_id) {
                    return $client->request('GET', 'scalper-console/list_c2c_deposits?sess_id='.$sess_id);
                };
          }
        };

        $p= new Pool($client, $requests(10), [
            'concurrency' => 200,
            'fulfilled'   => function ($response, $index){

                $r = json_decode($response->getBody()->getContents());
                if ($r->status==200 && !empty($r->data)) {
                    foreach ($r->data as $k=>$v) {
                        $this->info('单号: '.$v->ext_id.' 金额: '.$v->amount);
                        if ($v->amount>=40000) {
                            $qiangdan_url=$server.'scalper-console/lock_c2c_deposit?sess_id='.$sess_id.'&source='.$v->source.'&id='.$v->ext_id;
                            $qiangdan=file_get_contents($qiangdan_url);
                            $this->info('抢单结果: '.$qiangdan);
                        }
                    }
                } else {
                    $this->info('无单');
                }
            },
            'rejected' => function ($reason, $index){
                $this->error("rejected" );
                $this->error("rejected reason: " . $reason );
                $this->countedAndCheckEnded();
            },
        ]);

        // 开始发送请求
        $promise = $p->promise();
        $promise->wait();

        /*

                for ($i=0;$i<5;$i++) {
                    $response = $client->request('GET', 'scalper-console/list_c2c_deposits?sess_id='.$sess_id);
                    //var_dump($response->getBody());die();
                    //$string=(string)$response->getBody();
                    //$json=json_decode($string);
                    $this->info($response->getBody());
                }
                */

        return 0;
    }
}
