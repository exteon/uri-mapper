<?php

    namespace Test\Exteon\UriMapper;

    use ErrorException;
    use Exception;
    use Exteon\Uri\UnixPathUri;
    use Exteon\Uri\Uri;
    use Exteon\UriMapper\Join;
    use Exteon\UriMapper\JoinPoint;
    use Exteon\UriMapper\Root;
    use Exteon\UriMapper\UriMapper;
    use InvalidArgumentException;
    use PHPUnit\Framework\TestCase;

    class UriMapperTest extends TestCase
    {
        public function testAddingWrongUriMapperRoot(): void
        {
            $uriMapper1 = new UriMapper();
            $uriMapper2 = new UriMapper();
            $root2 = new Root($uriMapper2, Uri::fromString('/'));
            $this->expectException(InvalidArgumentException::class);
            $uriMapper1->addRoot($root2);
        }

        /**
         * @throws Exception
         */
        public function testMapping(): void
        {
            $uriMapper = new UriMapper();

            $uri1 = '/a/';
            $root1 = new Root($uriMapper, Uri::fromString($uri1));
            $uri2 = '/a/b/';
            $root2 = new Root($uriMapper, Uri::fromString($uri2));
            $uri3 = '/a/b/c/';
            $root3 = new Root($uriMapper, Uri::fromString($uri3));
            $uri4 = '/a/b/d/';
            $root4 = new Root($uriMapper, Uri::fromString($uri4));
            $uriMapper->addRoot($root1);
            $uriMapper->addRoot($root2);
            $uriMapper->addRoot($root3);
            $uriMapper->addRoot($root4);


            $path = $uriMapper->getParentRootPath($root1);
            self::assertNull($path);

            $path = $uriMapper->getParentRootPath($root2);
            self::assertSame($root1, $path->getRoot());
            self::assertEquals($uri2, $path->getUri()->toString());

            $path = $uriMapper->getParentRootPath($root3);
            self::assertSame($root2, $path->getRoot());
            self::assertEquals($uri3, $path->getUri()->toString());

            $path = $uriMapper->getParentRootPath($root4);
            self::assertSame($root2, $path->getRoot());
            self::assertEquals($uri4, $path->getUri()->toString());


            $path = $uriMapper->mapUri($root1->getMountUri());
            self::assertSame($root1, $path->getRoot());
            self::assertEquals($uri1, $path->getUri()->toString());

            $path = $uriMapper->mapUri($root2->getMountUri());
            self::assertSame($root2, $path->getRoot());
            self::assertEquals($uri2, $path->getUri()->toString());

            $path = $uriMapper->mapUri($root3->getMountUri());
            self::assertSame($root3, $path->getRoot());
            self::assertEquals($uri3, $path->getUri()->toString());

            $path = $uriMapper->mapUri($root4->getMountUri());
            self::assertSame($root4, $path->getRoot());
            self::assertEquals($uri4, $path->getUri()->toString());
        }

        public function testForbiddenSubroots(): void
        {
            $uriMapper = new UriMapper();

            $root1 = new Root($uriMapper, Uri::fromString('/a/'), '', false);
            $root2 = new Root($uriMapper, Uri::fromString('/a/b/'));

            $uriMapper->addRoot($root1, $root2);
            $this->expectException(Exception::class);
            $uriMapper->prime();
        }

        /**
         * @throws ErrorException
         * @throws Exception
         */
        public function testMappingByAnUpperJoin(): void
        {
            $uriMapper = new UriMapper();

            $uriMapper->addRoot(
                new Root($uriMapper, Uri::fromString('/'), 'a'),
                new Root($uriMapper, Uri::fromString('http://foo.bar/'), 'b')
            );

            $uriMapper->addJoin(
                new Join(
                    $uriMapper,
                    [
                        new JoinPoint($uriMapper, Uri::fromString('/'), 'a'),
                        new JoinPoint(
                            $uriMapper,
                            Uri::fromString('http://foo.bar/'),
                            'b'
                        )
                    ]
                ),
                new Join(
                    $uriMapper,
                    [
                        new JoinPoint($uriMapper, Uri::fromString('/x/'), 'a')
                    ]
                )
            );

            self::assertNotNull(
                $uriMapper->mapUri(Uri::fromString('/x/y'), 'a')->toContext('b')
            );
        }

        /**
         * @throws ErrorException
         */
        public function testSameJoinTwice(): void
        {
            $uriMapper = new UriMapper();

            $root1s = new Root($uriMapper, UnixPathUri::fromString('/a/'));
            $root1d = new Root(
                $uriMapper,
                Uri::fromString('http://host/x/?qs#f'),
                'd'
            );
            $uriMapper->addRoot($root1s)->addRoot($root1d);
            $join = new Join(
                $uriMapper,
                [
                    new JoinPoint(
                        $uriMapper,
                        UnixPathUri::fromString('/a/')
                    ),
                    new JoinPoint(
                        $uriMapper,
                        Uri::fromString('http://host/x/?qs#f'),
                        'd'
                    )
                ]
            );
            $uriMapper->addJoin($join);
            $uriMapper->addJoin($join);
            $this->expectException(Exception::class);
            $uriMapper->mapUri(UnixPathUri::fromString('/a'));
        }

        /**
         * @throws ErrorException
         */
        public function testAmbiguousRoot(): void
        {
            $uriMapper = new UriMapper();

            $root1s = new Root($uriMapper, UnixPathUri::fromString('/a/'));
            $root1s2 = new Root(
                $uriMapper, UnixPathUri::fromString('/a/')
            );

            $uriMapper->addRoot($root1s);
            $uriMapper->addRoot($root1s2);
            $this->expectException(Exception::class);
            $uriMapper->mapUri(UnixPathUri::fromString('/a'));
        }
    }
