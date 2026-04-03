<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand as BaseServeCommand;

class ServeCommand extends BaseServeCommand
{
    public function handle()
    {
        $this->input->setOption('no-reload', true);

        return parent::handle();
    }
}
