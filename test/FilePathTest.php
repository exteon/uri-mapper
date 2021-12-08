<?php

    namespace Test\Exteon\UriMapper;

    use ErrorException;
    use Exteon\Uri\UnixPathUri;
    use Exteon\UriMapper\FilePath;
    use Exteon\UriMapper\FileRoot;
    use Exteon\UriMapper\UriMapper;
    use PHPUnit\Framework\TestCase;

    class FilePathTest extends TestCase
    {

        /**
         * @throws ErrorException
         */
        public function test_getFilename(): void
        {
            $uriMapper = new UriMapper();
            $root1 = new FileRoot($uriMapper, UnixPathUri::fromString('/a/'));
            $uriMapper->addRoot($root1);

            $path = new FilePath($root1, UnixPathUri::fromString('b/c/file.pdf'));
            self::assertEquals('file.pdf', $path->getFilename());
        }
    }