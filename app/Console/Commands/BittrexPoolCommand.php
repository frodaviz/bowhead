<?php

namespace Bowhead\Console\Commands;

use Bowhead\Traits\OHLC;
use Bowhead\Util\BittrexAPIv1Client;
use Bowhead\Util\BittrexAPIv2Client;
use Datetime;
use Illuminate\Console\Command;


/**
 * Class BittrexPoolCommand
 * @package Bowhead\Console\Commands
 *
 */
class BittrexPoolCommand extends Command
{
    use OHLC;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bowhead:bittrex_pool';
    protected $name = 'bowhead:bittrex_pool';

    /**
     * @var currency pairs
     */
    protected $instrument;

    /**
     * @var
     */
    protected $console;

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
     * @return mixed
     */
    public function handle()
    {
        $bittrex = new BittrexAPIv1Client();
        $bittrex2 = new BittrexAPIv2Client();
        $instruments = ['USDT-LTC', 'USDT-XRP', 'USDT-NEO', 'USDT-OMG', 'USDT-ZEC', 'USDT-BTC', 'USDT-ETH', 'USDT-BCC', 'USDT-ETC', 'USDT-BTG', 'USDT-DASH', 'USDT-XMR'];
//        $instruments = ['USDT-XRP', 'USDT-NEO', 'USDT-OMG', 'USDT-ZEC', 'USDT-DASH', 'USDT-XMR'];
        echo "Prepopulating the database\n";
//        foreach ($instruments as $instrument) {
//            $data = $bittrex2->getTicks($instrument);
//            foreach ($data as $dataObjectO) {
//                $this->markOHLCBittrex($dataObjectO, $instrument);
//            }
//            sleep(1);
//        }

        echo "going to the endless loop\n";
        while (1) {
            try {

                foreach ($instruments as $instrument) {
                    $data = $bittrex2->getLatestTick($instrument);
                    sleep(1);
                    $dataLive = $bittrex->getTicker($instrument);
//                print_r($data);
//                print_r($dataLive);
                    $dataLive->T = (new DateTime())->format('Y-m-dTH:i:s');
                    $dataLive->C = $dataLive->Last;
                    $dataLive->O = $data[0]->O;
                    $dataLive->H = $data[0]->H;
                    $dataLive->L = $data[0]->L;
                    $dataLive->BV = $data[0]->BV;
//                print_r($data);
//                print_r($dataLive);
                    print " " . $data[0]->T . " " . $instrument . " " . $data[0]->C . "\n";
                    print " " . $dataLive->T . " " . $instrument . " " . $dataLive->C . "\n\n";
                    $this->markOHLCBittrex($dataLive, $instrument);
                    $this->markOHLCBittrex($data[0], $instrument);
                    sleep(1);
                }
            } catch (\Exception $e) {
                echo 'Caught exception: ', $e->getMessage(), "\nContinuing execution\n";
                sleep(2);
            }
        }
    }
}
