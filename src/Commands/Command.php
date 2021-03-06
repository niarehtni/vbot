<?php

namespace Hanson\Vbot\Commands;

use Symfony\Component\Console\Application;

/**
 * Class Command.
 */
class Command
{
    public function run()
    {
        $application = new Application();

        $application->add(new ClearSessionCommand());
        $application->add(new MigrationCommand());

        $application->run();
    }
}
