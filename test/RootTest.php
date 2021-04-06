<?php

    namespace Test\Exteon\UriMapper;

    use Exteon\Uri\Uri;
    use Exteon\UriMapper\Root;
    use Exteon\UriMapper\UriMapper;
    use InvalidArgumentException;
    use PHPUnit\Framework\TestCase;

    class RootTest extends TestCase
    {
        public function testConstructWithUnrootedUri(): void
        {
            $uriMapper = new UriMapper();
            $this->expectException(InvalidArgumentException::class);
            new Root($uriMapper, Uri::fromString('relative'));
        }
    }
