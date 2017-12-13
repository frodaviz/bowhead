<?php

namespace Bowhead\Console\Commands;

use Bowhead\Util\Console;
use Bowhead\Console\Kernel;
use Bowhead\Util;
use Bowhead\Util\BittrexAPIv1Client;
use Illuminate\Console\Command;

/**
 * Class BittrexPoolCommand
 * @package Bowhead\Console\Commands
 *
 */
class BittrexPoolCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bowhead:bittrex_pool';
    protected $name = 'bowhead:bittrex_pool';

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
        print json_encode($bittrex->getCurrencies());
        echo "\n";
    }
}
