<?php

namespace Bowhead\Console\Commands;

use Bowhead\Traits\OHLC;
use Bowhead\Traits\Signals;
use Bowhead\Util;
use Bowhead\Util\BittrexAPIv1Client;
use Bowhead\Util\BittrexAPIv2Client;
use Illuminate\Console\Command;


// https://github.com/andreas-glaser/poloniex-php-client

/**
 * Class ExampleCommand
 * @package Bowhead\Console\Commands
 */
class BittrexPoolAndSignalsTradingCommand extends Command
{

    use Signals, OHLC;
    protected $bittrex;
    protected $bittrex2;

    public function __construct()
    {
        parent::__construct();
        $this->bittrex = new BittrexAPIv1Client();
        $this->bittrex2 = new BittrexAPIv2Client();
    }

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'bowhead:bittrex_pool_and_signal_trade';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Forex signals example';


    public function doColor($val)
    {
        if ($val == 0) {
            return 'none';
        }
        if ($val == 1) {
            return 'green';
        }
        if ($val == -1) {
            return 'magenta';
        }
        return 'none';
    }

    protected $investment = 1000;
    protected $investedIn = array();
    protected $buySignals = 0;
    protected $sellSignals = 0;
    protected $transactionsMade = 0;
    protected $last;
    protected $instruments = ['BITTREX-USDT-LTC', 'BITTREX-USDT-XRP', 'BITTREX-USDT-NEO',
        'BITTREX-USDT-OMG', 'BITTREX-USDT-ZEC', 'BITTREX-USDT-BTC', 'BITTREX-USDT-ETH', 'BITTREX-USDT-BCC',
        'BITTREX-USDT-ETC', 'BITTREX-USDT-BTG', 'BITTREX-USDT-DASH', 'BITTREX-USDT-XMR'];

//    protected $instruments = ['BITTREX-BTC-GEO'];


    private function prePopulateBittrex()
    {
        foreach ($this->instruments as $instrument) {
            $instrument = substr($instrument, 8);
            $data = $this->bittrex2->getTicks($instrument);
            $this->markOHLCBittrex($data[0], $instrument);

            for ($i = 1; $i < 40; $i++) {
                $this->markOHLCBittrex($data[$i], $instrument);
            }
            for ($i = 40; $i < count($data); $i++) {
                if ($i % 100 == 0) {
                    echo $i . " Processed \n";
                }
                $this->markOHLCBittrex($data[$i], $instrument);
                $last = $data[$i];
                $this->signalProcess(["BITTREX-" . $instrument]);//THIS IS ONLY SIMULATION!!!!
            }
            print_r($data[0]);
            print_r($data[count($data) - 1]);
            sleep(1);
        }
    }

    private function tickBittrex()
    {
        try {

            foreach ($this->instruments as $instrument) {
                $instrument = substr($instrument, 8);
                $data = $this->bittrex2->getLatestTick($instrument);
                sleep(1);
                $dataLive = $this->bittrex->getTicker($instrument);
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

    private function signalProcess($instruments)
    {
        if (!isset($instruments)) {
            $instruments = $this->instruments;
        }
        $util = new Util\BrokersUtil();
        $console = new \Bowhead\Util\Console();
        $indicators = new \Bowhead\Util\Indicators();

//        $this->signals(false, false, $this->instruments);

        $back = $this->signals(1, 2, $instruments);
//        print_r($back);
        foreach ($instruments as $instrument) {

            if (strpos($back[$instrument], 'BUY') !== false) {
                $this->buySignals++;
                if (!array_key_exists($instrument, $this->investedIn)) {
                    $dataa = $this->getRecentData($instrument, 1);
                    $last = $dataa;
                    $this->investedIn[$instrument] = array("InitialPrice" => $last['close'], "InvestedVolume" => 50 / $last['close'][0]);
                    $this->investment -= 50;
                    echo "INVESTED IN " . $instrument . " VOLUME: " . (50 / $last['close'][0]) . "\n";
                    echo "Current saldo:" . $this->investment . "\n";
                }
            } else if (strpos($back[$instrument], 'SELL') !== false) {
                $this->sellSignals++;
                if (array_key_exists($instrument, $this->investedIn)) {
                    $this->transactionsMade++;
                    $dataa = $this->getRecentData($instrument, 1);
                    $last = $dataa;
                    $soldFor = $last['close'][0] * $this->investedIn[$instrument]["InvestedVolume"];
                    echo "SELLING " . $this->investedIn[$instrument]["InvestedVolume"] . " " . $instrument . " FOR: " . $soldFor . "\n";
                    $this->investment += $soldFor;
                    echo "Current saldo:" . $this->investment . "\n";
                    unset($this->investedIn[$instrument]);
                }
            }
        }
        return $back;
    }

    /**
     * @return null
     *
     *  this is the part of the command that executes.
     */
    public function handle()
    {
        echo "PRESS 'q' TO QUIT AND CLOSE ALL POSITIONS\n\n\n";
        stream_set_blocking(STDIN, 0);
        $this->prePopulateBittrex();


        //SELL AT THE END
        foreach ($this->instruments as $instrument) {

            if (array_key_exists($instrument, $this->investedIn)) {
                $this->transactionsMade++;
                $dataa = $this->getRecentData($instrument, 1);
                $last = $dataa;
                $soldFor = $last['close'][0] * $this->investedIn[$instrument]["InvestedVolume"];
                echo "SELLING " . $instrument . " FOR: " . $soldFor . "\n";
                $this->investment += $soldFor;
                echo "Current saldo:" . $this->investment . "\n";
                unset($this->investedIn[$instrument]);
            }
        }
        //PRINT RECAP
        echo "TOTAL BUY SIGNALS: " . $this->buySignals . " TOTAL SELL SIGNALS: " . $this->sellSignals . "\n\n";
        echo "TOTAL TRANSATIONS MADE: " . $this->transactionsMade . "\n\n";
        echo "TOTAL MONEY: " . $this->investment . "\n\n";
//        while (1) {
//            $this->tickBittrex();
//            $this->signalProcess();
//            sleep(5);
//        }


        return null;
    }


}
