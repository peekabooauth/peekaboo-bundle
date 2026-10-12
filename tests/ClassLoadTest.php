<?php

namespace Peekabooauth\PeekabooBundle\Tests;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ClassLoadTest extends TestCase
{
    // Declaring a class is where PHP checks it against the installed Symfony interfaces,
    // so loading each one catches a signature that does not fit this Symfony version.
    #[DataProvider('provideClasses')]
    public function testClassLoadsAgainstInstalledSymfony(string $class): void
    {
        self::assertTrue(class_exists($class) || interface_exists($class), $class . ' is not loadable');
    }

    public static function provideClasses(): iterable
    {
        $srcDir = dirname(__DIR__) . '/src';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir, FilesystemIterator::SKIP_DOTS));

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = substr($file->getPathname(), strlen($srcDir) + 1, -4);
            $class = 'Peekabooauth\\PeekabooBundle\\' . str_replace('/', '\\', $relativePath);

            yield $class => [$class];
        }
    }
}
