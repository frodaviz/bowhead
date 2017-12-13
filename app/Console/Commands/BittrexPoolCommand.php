<?php

namespace Bowhead\Console\Commands;

use Bowhead\Traits\OHLC;
use Bowhead\Util\BittrexAPIv1Client;
use Bowhead\Util\BittrexAPIv2Client;
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
        foreach ($instruments as $instrument) {
            $data = $bittrex2->getTicks($instrument);
            foreach ($data as $dataObjectO) {
                $this->markOHLCBittrex($dataObjectO, $instrument);
            }
            sleep(1);
        }

        echo "going to the endless loop\n";
        while (1) {
            foreach ($instruments as $instrument) {
                $data = $bittrex2->getLatestTick($instrument);
                print $instrument." ".$data[0]->C."\n";
                $this->markOHLCBittrex($data[0], $instrument);
                sleep(2);
            }
        }
    }
}
