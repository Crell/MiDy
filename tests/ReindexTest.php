<?php

declare(strict_types=1);

namespace Crell\MiDy;

use Crell\MiDy\Commands\Reindex;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

// This is a total hack for now, so I can debug things.
class ReindexTest extends TestCase
{
    #[Test, DoesNotPerformAssertions]
    public function run_command(): void
    {
        $this->markTestSkipped('PHPUnit complains this is not cleaning up custom error/exception handers, but it is not setting any.');

        $app = new MiDy('.', routesPath: \realpath('tests/test-routes'), publicPath: 'tests/test-public');

        /** @var Reindex $cmd */
        $cmd = $app->container->get(Reindex::class);

        $cmd->run();
    }
}
