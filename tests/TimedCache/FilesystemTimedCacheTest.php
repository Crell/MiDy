<?php

declare(strict_types=1);

namespace Crell\MiDy\TimedCache;

use Crell\MiDy\FakeFilesystem;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FilesystemTimedCacheTest extends TestCase
{
    use FakeFilesystem;

    protected vfsStreamDirectory $vfs;

    #[Before]
    protected function initFilesystemCache(): void
    {
        $this->vfs = vfsStream::setup('cache', null, []);
    }

    protected function makeCache(array $allowedClasses = []): FilesystemTimedCache
    {
        return new FilesystemTimedCache($this->vfs->url(), $allowedClasses);
    }

    public static function cacheTargetsProvider(): iterable
    {
        foreach ([time() - 20, new \DateTimeImmutable('-20 seconds')] as $mtime) {
            yield [
                'data' => 'abcd',
                'dataMTime' => $mtime,
                'allowedClasses' => []
            ];

            yield [
                'data' => ['a' => 'A', 'b' => 'B'],
                'dataMTime' => $mtime,
            ];

            yield [
                'data' => new Dummy('beep'),
                'dataMTime' => $mtime,
                'allowedClasses' => [Dummy::class],
            ];
        }
    }

    #[Test, DataProvider('cacheTargetsProvider')]
    public function can_explicitly_write(
        mixed $data,
        int|\DateTimeInterface $dataMTime,
        array $allowedClasses = []
    ): void {
        $cache = $this->makeCache($allowedClasses);

        $success = $cache->write('key', $data);

        self::assertTrue($success);

        $cacheFile = $this->vfs->url() . '/key';
        $cached = file_get_contents($cacheFile);

        self::assertNotEmpty($cached);
    }

    #[Test, DataProvider('cacheTargetsProvider')]
    public function can_regenerate(mixed $data, int|\DateTimeInterface $dataMTime, array $allowedClasses = []): void
    {
        $cache = $this->makeCache($allowedClasses);

        $result = $cache->get('key', $dataMTime, fn() => $data);

        self::assertEquals($data, $result);
    }

    #[Test, DataProvider('cacheTargetsProvider')]
    public function regeneration_only_called_once_if_no_change(
        mixed $data,
        int|\DateTimeInterface $dataMTime,
        array $allowedClasses = []
    ): void {
        $cache = $this->makeCache($allowedClasses);

        $result = $cache->get('key', $dataMTime, fn() => $data);
        self::assertEquals($data, $result);

        $cache->get('key', $dataMTime, fn() => throw new \Exception('This should not be called'));
    }

    #[Test, DataProvider('cacheTargetsProvider')]
    public function regeneration_called_if_timestamp_is_newer(
        mixed $data,
        int|\DateTimeInterface $dataMTime,
        array $allowedClasses = []
    ): void {
        $cache = $this->makeCache($allowedClasses);

        $result = $cache->get('key', $dataMTime, fn() => $data);
        self::assertEquals($data, $result);

        $called = false;

        if ($dataMTime instanceof \DateTimeInterface) {
            $dataMTime = new \DateTimeImmutable('@' . $dataMTime->getTimestamp() + 40);
        } else {
            $dataMTime += 40;
        }

        $result = $cache->get('key', $dataMTime, function () use ($data, &$called) {
            $called = true;
            return $data;
        });

        self::assertTrue($called);
        self::assertEquals($data, $result);
    }

    #[Test]
    public function cannot_deserialize_if_insufficient_classes_allowed(): void
    {
        $cache = $this->makeCache([self::class]);

        $data = new Dummy('beep');

        // This should succeed, as it's writing the data for the first time.
        $result = $cache->get('key', time() - 20, fn() => $data);
        self::assertEquals($data, $result);

        // This should fail, because the serialized data cannot be unseralized.
        $result = $cache->get('key', time() - 20, fn() => $data);
        self::assertNotEquals($data, $result);
    }
}

class Dummy
{
    public function __construct(public string $name) {}
}
