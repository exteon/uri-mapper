<?php

    namespace Test\Exteon\UriMapper;

    use ErrorException;
    use Exception;
    use Exteon\Uri\UnixPathUri;
    use Exteon\Uri\Uri;
    use Exteon\UriMapper\Join;
    use Exteon\UriMapper\JoinPoint;
    use Exteon\UriMapper\Path;
    use Exteon\UriMapper\Root;
    use Exteon\UriMapper\UriMapper;
    use PHPUnit\Framework\TestCase;

    class PathTest extends TestCase
    {
        /**
         * @throws ErrorException
         */
        public function testAscend(): void
        {
            $uriMapper = new UriMapper();

            $root1 = new Root($uriMapper, Uri::fromString('/a/'));
            $root2 = new Root($uriMapper, Uri::fromString('/a/b/'));
            $root3 = new Root($uriMapper, Uri::fromString('/a/b/c/'));
            $uriMapper->addRoot($root1);
            $uriMapper->addRoot($root2);
            $uriMapper->addRoot($root3);

            $path = new Path($root3, Uri::fromString('d/e'));

            $a = $path->ascend();
            self::assertSame($root3, $a->getRoot());
            self::assertEquals('/a/b/c/d/', $a->getUri()->toString());

            $a = $a->ascend();
            self::assertSame($root3, $a->getRoot());
            self::assertEquals('/a/b/c/', $a->getUri()->toString());

            $a = $a->ascend();
            self::assertSame($root2, $a->getRoot());
            self::assertEquals('/a/b/', $a->getUri()->toString());

            $a = $a->ascend();
            self::assertSame($root1, $a->getRoot());
            self::assertEquals('/a/', $a->getUri()->toString());

            $a = $a->ascend();
            self::assertNull($a);

            $a = $path->ascend(2);
            self::assertSame($root3, $a->getRoot());
            self::assertEquals('/a/b/c/', $a->getUri()->toString());

            $a = $path->ascend(3);
            self::assertSame($root2, $a->getRoot());
            self::assertEquals('/a/b/', $a->getUri()->toString());

            $a = $path->ascend(4);
            self::assertSame($root1, $a->getRoot());
            self::assertEquals('/a/', $a->getUri()->toString());

            $a = $path->ascend(5);
            self::assertNull($a);
        }

        /**
         * @throws ErrorException
         */
        public function testDescend(): void
        {
            $uriMapper = new UriMapper();

            $root1 = new Root($uriMapper, Uri::fromString('/a/?qs#f'));
            $root2 = new Root($uriMapper, Uri::fromString('/a/b/?qs#f'));
            $root3 = new Root($uriMapper, Uri::fromString('/a/b/c/?qs#f'));
            $uriMapper->addRoot($root1);
            $uriMapper->addRoot($root2);
            $uriMapper->addRoot($root3);

            $path = new Path($root1, new Uri());

            $a = $path->descend('x');
            self::assertSame($root1, $a->getRoot());
            self::assertEquals('/a/x', $a->getUri()->toString());

            $a = $path->descend('x/');
            self::assertSame($root1, $a->getRoot());
            self::assertEquals('/a/x/', $a->getUri()->toString());

            $path2 = $path->descend('b');
            self::assertSame($root1, $path2->getRoot());
            self::assertEquals('/a/b', $path2->getUri()->toString());

            $path2 = $path->descend('b/');
            self::assertSame($root2, $path2->getRoot());
            self::assertEquals('/a/b/', $path2->getUri()->toString());

            $path2 = $path->descend('b/c');
            self::assertSame($root2, $path2->getRoot());
            self::assertEquals('/a/b/c', $path2->getUri()->toString());

            $path2 = $path->descend('b/c/');
            self::assertSame($root3, $path2->getRoot());
            self::assertEquals('/a/b/c/', $path2->getUri()->toString());
        }

        /**
         * @throws ErrorException
         */
        public function testDescendNoSubroots(): void
        {
            $uriMapper = new UriMapper();

            $root1 = new Root(
                $uriMapper, Uri::fromString('/a/?qs#f'), '', false
            );
            $uriMapper->addRoot($root1);

            $path = new Path($root1, new Uri());

            $a = $path->descend('x');
            self::assertSame($root1, $a->getRoot());
            self::assertEquals('/a/x', $a->getUri()->toString());

            $a = $path->descend('x/');
            self::assertSame($root1, $a->getRoot());
            self::assertEquals('/a/x/', $a->getUri()->toString());

            $path2 = $path->descend('b/c');
            self::assertSame($root1, $path2->getRoot());
            self::assertEquals('/a/b/c', $path2->getUri()->toString());

            $path2 = $path->descend('b/c/');
            self::assertSame($root1, $path2->getRoot());
            self::assertEquals('/a/b/c/', $path2->getUri()->toString());
        }


        /**
         * @throws ErrorException
         */
        public function testDescendUnixPaths(): void
        {
            $uriMapper = new UriMapper();

            $root1 = new Root($uriMapper, UnixPathUri::fromString('/a/'));
            $root2 = new Root(
                $uriMapper, UnixPathUri::fromString('/a/b/')
            );
            $root3 = new Root(
                $uriMapper,
                UnixPathUri::fromString('/a/b/c/')
            );
            $uriMapper->addRoot($root1);
            $uriMapper->addRoot($root2);
            $uriMapper->addRoot($root3);

            $path = new Path($root1, new Uri());

            $a = $path->descend('x');
            self::assertSame($root1, $a->getRoot());
            self::assertEquals('/a/x', $a->getUri()->toString());

            $a = $path->descend('x/');
            self::assertSame($root1, $a->getRoot());
            self::assertEquals('/a/x/', $a->getUri()->toString());

            $path2 = $path->descend('b');
            self::assertSame($root2, $path2->getRoot());
            self::assertEquals('/a/b/', $path2->getUri()->toString());

            $path2 = $path->descend('b/');
            self::assertSame($root2, $path2->getRoot());
            self::assertEquals('/a/b/', $path2->getUri()->toString());

            $path2 = $path->descend('b/c');
            self::assertSame($root3, $path2->getRoot());
            self::assertEquals('/a/b/c/', $path2->getUri()->toString());

            $path2 = $path->descend('b/c/');
            self::assertSame($root3, $path2->getRoot());
            self::assertEquals('/a/b/c/', $path2->getUri()->toString());
        }


        /**
         * @throws ErrorException
         * @throws Exception
         */
        public function testToContext(): void
        {
            $uriMapper = new UriMapper();

            $uriMapper
                ->addRoot(new Root($uriMapper, UnixPathUri::fromString('/a/')))
                ->addRoot(
                    new Root($uriMapper, Uri::fromString('http://host/x/'), 'd')
                )
                ->addRoot(
                    new Root($uriMapper, Uri::fromString('http://host/y/'), 'd')
                );

            $joinPoint1s = new JoinPoint(
                $uriMapper,
                UnixPathUri::fromString('/a/')
            );
            $joinPoint1d = new JoinPoint(
                $uriMapper,
                Uri::fromString('http://host/x/?qs#f'),
                'd'
            );
            $joinPoint2s = new JoinPoint(
                $uriMapper, UnixPathUri::fromString('/a/b/')
            );
            $joinPoint2d = new JoinPoint(
                $uriMapper,
                Uri::fromString('http://host/y/?qs#f'),
                'd'
            );
            $joinPoint3s = new JoinPoint(
                $uriMapper,
                UnixPathUri::fromString('/a/b/c/')
            );
            $joinPoint3d = new JoinPoint(
                $uriMapper,
                Uri::fromString('http://host/y/z/?qs#f'),
                'd'
            );

            $uriMapper
                ->addJoin(
                    new Join($uriMapper, [$joinPoint1s, $joinPoint1d])
                )
                ->addJoin(
                    new Join($uriMapper, [$joinPoint2s, $joinPoint2d])
                )
                ->addJoin(
                    new Join($uriMapper, [$joinPoint3s, $joinPoint3d])
                );

            $path = $uriMapper->mapUri(UnixPathUri::fromString('/a'));
            self::assertEquals(
                'http://host/x/?qs#f',
                $path->toContext('d')->getUri()->toString()
            );

            $path = $uriMapper->mapUri(UnixPathUri::fromString('/a/'));
            self::assertEquals(
                'http://host/x/?qs#f',
                $path->toContext('d')->getUri()->toString()
            );

            $path = $uriMapper->mapUri(UnixPathUri::fromString('/a/p'));
            self::assertEquals(
                'http://host/x/p',
                $path->toContext('d')->getUri()->toString()
            );

            $path = $uriMapper->mapUri(UnixPathUri::fromString('/a/p/'));
            self::assertEquals(
                'http://host/x/p/',
                $path->toContext('d')->getUri()->toString()
            );

            $path = $uriMapper->mapUri(UnixPathUri::fromString('/a/b'));
            self::assertEquals(
                'http://host/y/?qs#f',
                $path->toContext('d')->getUri()->toString()
            );


            $path = $uriMapper->mapUri(Uri::fromString('http://host/x'), 'd');
            self::assertNull($path);

            $path = $uriMapper->mapUri(Uri::fromString('http://host/y'), 'd');
            self::assertNull($path);

            $path = $uriMapper->mapUri(
                Uri::fromString('http://host/y/z/p'),
                'd'
            );
            self::assertEquals(
                '/a/b/c/p',
                $path->toContext('')->getUri()->toString()
            );

            $path = $uriMapper->mapUri(
                Uri::fromString('http://host/y/z/p/'),
                'd'
            );
            self::assertEquals(
                '/a/b/c/p/',
                $path->toContext('')->getUri()->toString()
            );
        }

        /**
         * @throws ErrorException
         * @throws Exception
         */
        public function testMultipleJoinsForJoinPoint(): void
        {
            $uriMapper = new UriMapper();

            $uriMapper
                ->addRoot(
                    new Root($uriMapper, UnixPathUri::fromString('/a/'))
                )
                ->addRoot(
                    new Root($uriMapper, Uri::fromString('http://host/'), 'd')
                )
                ->addRoot(
                    new Root($uriMapper, Uri::fromString('http://host/'), 'e')
                );


            $joinPoint1s = new JoinPoint($uriMapper, UnixPathUri::fromString('/a/'));
            $joinPoint1d = new JoinPoint(
                $uriMapper,
                Uri::fromString('http://host/x/?qs#f'),
                'd'
            );
            $joinPoint1e = new JoinPoint(
                $uriMapper,
                Uri::fromString('http://host/z/?qs#f'),
                'e'
            );

            $uriMapper->addJoin(
                new Join($uriMapper, [$joinPoint1s, $joinPoint1d])
            );
            $uriMapper->addJoin(
                new Join($uriMapper, [$joinPoint1s, $joinPoint1e])
            );
            $path = $uriMapper->mapUri(UnixPathUri::fromString('/a'));
            self::assertEquals(
                'http://host/x/?qs#f',
                $path->toContext('d')->getUri()->toString()
            );
            self::assertEquals(
                'http://host/z/?qs#f',
                $path->toContext('e')->getUri()->toString()
            );
        }

        /**
         * @throws ErrorException
         * @throws Exception
         */
        public function testToSameContext(): void
        {
            $uriMapper = new UriMapper();

            $uriMapper->addRoot(
                new Root($uriMapper, UnixPathUri::fromString('/a/'))
            );

            $path = $uriMapper->mapUri(UnixPathUri::fromString('/a'));
            self::assertEquals(
                $path,
                $path->toContext('')
            );
        }
    }
