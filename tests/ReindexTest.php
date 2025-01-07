<?php

declare(strict_types=1);

namespace Crell\MiDy;

use Crell\MiDy\Commands\Reindex;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\RunClassInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PDO;

// This is a total hack for now, so I can debug things.
//#[RunClassInSeparateProcess]
class ReindexTest extends TestCase
{
    #[Test, DoesNotPerformAssertions]
    public function test(): void
    {
        $app = new MiDy('.', routesPath: \realpath('tests/test-routes'), publicPath: 'tests/test-public');

        /** @var Reindex $cmd */
        $cmd = $app->container->get(Reindex::class);

        $cmd->run();

//        $db = $app->container->get(PDO::class);
//        var_dump($db->query("SELECT logicalPath, physicalPath, frontmatter FROM file")->fetchAll(\PDO::FETCH_ASSOC));
    }
}
