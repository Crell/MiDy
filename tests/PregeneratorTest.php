<?php

declare(strict_types=1);

namespace Crell\MiDy;

use Crell\MiDy\Commands\StaticFilePregenerator;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\RunClassInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

// This is a total hack for now, so I can debug things.
#[RunClassInSeparateProcess]
class PregeneratorTest extends TestCase
{
    #[Test, DoesNotPerformAssertions]
    public function test(): void
    {
        $app = new MiDy('.', routesPath: \realpath('tests/test-routes'), publicPath: 'tests/test-public');

        /** @var StaticFilePregenerator $cmd */
        $cmd = $app->container->get(StaticFilePregenerator::class);

        $cmd->run();

    }
}
