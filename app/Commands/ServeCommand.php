<?php

namespace Datashaman\Lowdown\Commands;

use LaravelZero\Framework\Commands\Command;

class ServeCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'serve';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Serve documentation.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $result = `php -S 127.0.0.1:8080 -t docs/api`;
        $this->line($result);
    }
}
