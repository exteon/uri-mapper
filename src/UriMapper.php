<?php

    namespace Exteon\UriMapper;

    use ArrayObject;
    use ErrorException;
    use Exception;
    use Exteon\Uri\AbstractUri;
    use InvalidArgumentException;
    use SplObjectStorage;

    class UriMapper
    {
        protected const REGEXP_SEPARATOR = '`';

        /** @var Join[] */
        protected $joins = [];

        /** @var bool */
        protected $isPrimed = false;

        /** @var array<string,string> */
        protected $rootLookups;

        /** @var array<string,string> */
        protected $parentRootLookups;

        /** @var array<string,array<string,array<string,Join>>> */
        protected $joinsByUri;

        /** @var array<string,array<string,Root>> */
        protected $parentRootsByUri;

        /** @var @var bool */
        protected $isPriming = false;

        /** @var SplObjectStorage<Root,Root|null> */
        protected $rootParentMap;

        /** @var Root[] */
        protected $roots = [];

        /** @var array<string,array<string,Root>> */
        protected $rootsByUri;

        /**  @var SplObjectStorage<JoinPoint,array<string,Join>> */
        protected $joinPointsToJoins;

        /** @var array<string,array<string,string>> */
        private $joinLookups;

        /**
         * @param string $path
         * @throws Exception
         */
        public static function validateRelativePath(string $path): void
        {
            if (preg_match('`^/|//`', $path)) {
                throw new InvalidArgumentException(
                    'Relative paths must not start with \'/\' or contain consecutive \'/\'s'
                );
            }
        }

        /**
         * @param AbstractUri $uri
         * @param string $context
         * @return AbstractPath|null
         * @throws ErrorException
         * @throws Exception
         */
        public function mapUri(
            AbstractUri $uri,
            string $context = ''
        ): ?AbstractPath {
            $root = $this->lookupRoot($uri, $context);
            if ($root) {
                return $root->getPath($uri);
            }
            return null;
        }

        /**
         * @param AbstractUri $uri
         * @param string $context
         * @return Root|null
         * @throws Exception
         */
        protected function lookupRoot(
            AbstractUri $uri,
            string $context = ''
        ): ?Root {
            $this->internalPrime(false);
            if (!isset($this->rootLookups[$context])) {
                return null;
            }
            if (
            preg_match(
                $this->rootLookups[$context],
                $uri->toString(),
                $match
            )
            ) {
                return $this->rootsByUri[$context][$match[0]];
            }
            return null;
        }

        /**
         * @param bool $force
         * @throws Exception
         */
        protected function internalPrime(bool $force): void
        {
            if (
                (
                    !$force &&
                    $this->isPrimed
                ) ||
                $this->isPriming
            ) {
                return;
            }
            $this->isPriming = true;

            $this->primeRoots();
            $this->primeJoins();

            $this->isPrimed = true;
            $this->isPriming = false;
        }

        /**
         * @throws Exception
         */
        protected function primeRoots(): void
        {
            $this->rootsByUri = [];
            $this->parentRootsByUri = [];

            $rootsByContext = [];
            $parentRootsByContext = [];

            foreach ($this->roots as $root) {
                $context = $root->getContext();
                if (!isset($this->rootsByUri[$context])) {
                    $this->rootsByUri[$context] = [];
                }
                $meta = $this->getUriRegexpMeta($root->getMountUri());
                if (isset($this->rootsByUri[$context][$meta['uri']])) {
                    if ($this->rootsByUri[$context][$meta['uri']] !== $root) {
                        throw new Exception(
                            'Multiple roots for the same uri for the same context'
                        );
                    }
                } else {
                    $this->rootsByUri[$context][$meta['uri']] = $root;
                }
                $rootsByContext[$context][] = array_merge(
                    $meta,
                    ['root' => $root]
                );
                $prefixUri = $root->getPrefixUri();
                if ($prefixUri) {
                    if (!isset($this->parentRootsByUri[$context])) {
                        $this->parentRootsByUri[$context] = [];
                    }
                    $meta = $this->getUriRegexpMeta($prefixUri);
                    if (isset($this->parentRootsByUri[$context][$meta['uri']])) {
                        if (
                            $this->parentRootsByUri[$context][$meta['uri']] !==
                            $root
                        ) {
                            throw new Exception(
                                'Multiple root prefixes for the same uri for the same context'
                            );
                        }
                    } else {
                        $this->parentRootsByUri[$context][$meta['uri']] = $root;
                    }
                    $parentRootsByContext[$context][] = array_merge(
                        $meta,
                        ['root' => $root]
                    );
                }
            }

            $this->rootLookups = [];
            $this->parentRootLookups = [];

            foreach ($rootsByContext as $context => $rootMetaRecs) {
                $rootRegexps = [];
                usort(
                    $rootMetaRecs,
                    function ($a, $b) {
                        return
                            strlen($b['uri']) -
                            strlen($a['uri']);
                    }
                );
                foreach ($rootMetaRecs as $rootMetaRec) {
                    $escaped = preg_quote(
                        $rootMetaRec['uri'],
                        self::REGEXP_SEPARATOR
                    );
                    if ($rootMetaRec['hasTrailingSlash']) {
                        if ($rootMetaRec['isTrailingSlashInsensitive']) {
                            $rootRegexps[] = "{$escaped}(?=[/?#]|$)";
                        } else {
                            $rootRegexps[] = "{$escaped}";
                        }
                    } else {
                        $rootRegexps[] = "{$escaped}(?=[?#]|$)";
                    }
                }
                $this->rootLookups[$context] =
                    self::REGEXP_SEPARATOR .
                    '^(?:' .
                    implode('|', $rootRegexps) .
                    ')' .
                    self::REGEXP_SEPARATOR;
            }

            foreach ($parentRootsByContext as $context => $rootMetaRecs) {
                $rootRegexps = [];
                usort(
                    $rootMetaRecs,
                    function ($a, $b) {
                        return
                            strlen($b['uri']) -
                            strlen($a['uri']);
                    }
                );
                foreach ($rootMetaRecs as $rootMetaRec) {
                    $escaped = preg_quote(
                        $rootMetaRec['uri'],
                        self::REGEXP_SEPARATOR
                    );
                    if ($rootMetaRec['hasTrailingSlash']) {
                        if ($rootMetaRec['isTrailingSlashInsensitive']) {
                            $rootRegexps[] = "{$escaped}(?=/[^?#])";
                        } else {
                            $rootRegexps[] = "{$escaped}(?=[^?#])";
                        }
                    }
                }
                $this->parentRootLookups[$context] =
                    self::REGEXP_SEPARATOR .
                    '^(?:' .
                    implode('|', $rootRegexps) .
                    ')' .
                    self::REGEXP_SEPARATOR;
            }

            $this->rootParentMap = new SplObjectStorage();
            foreach ($rootsByContext as $context => $rootMetaRecs) {
                foreach ($rootMetaRecs as $rootMetaRec) {
                    $root = $rootMetaRec['root'];
                    $parentRoot = $this->lookupParentRoot(
                        $root->getMountUri(),
                        $context
                    );
                    if (
                        $parentRoot &&
                        !$parentRoot->doesAllowSubroots()
                    ) {
                        throw new Exception(
                            'Subroot mounted in a parent root that does not allow subjoins'
                        );
                    }
                    $this->rootParentMap[$root] = $parentRoot;
                }
            }
        }

        /**
         * @param AbstractUri $uri
         * @return array {
         *   uri: string,
         *   hasTrailingSlash: bool,
         *   isTrailingSlashInsensitive: bool
         * }
         * @throws Exception
         */
        protected function getUriRegexpMeta(AbstractUri $uri): array
        {
            $uriStr = $uri->getUriStringWithoutQueryFragment();
            $isTrailingSlashInsensitive =
                $uri::isTrailingSlashInsensitive();
            if ($uri->hasTrailingSlash()) {
                $hasTrailingSlash = true;
                if ($isTrailingSlashInsensitive) {
                    $uriStr = substr($uriStr, 0, -1);
                }
            } else {
                $hasTrailingSlash = false;
            }
            return [
                'uri' => $uriStr,
                'hasTrailingSlash' => $hasTrailingSlash,
                'isTrailingSlashInsensitive' => $isTrailingSlashInsensitive
            ];
        }

        /**
         * @param AbstractUri $uri
         * @param string $context
         * @return Join|null
         * @throws Exception
         */
        protected function lookupParentRoot(
            AbstractUri $uri,
            string $context = ''
        ): ?Root {
            $this->internalPrime(false);
            if (!isset($this->parentRootLookups[$context])) {
                return null;
            }
            if (
            preg_match(
                $this->parentRootLookups[$context],
                $uri->toString(),
                $match
            )
            ) {
                return $this->parentRootsByUri[$context][$match[0]];
            }
            return null;
        }

        /**
         * @throws Exception
         */
        protected function primeJoins(): void
        {
            $this->joinsByUri = [];
            $this->joinPointsToJoins = new SplObjectStorage();

            $joinPointsByContext = [];

            foreach ($this->joins as $join) {
                $joinPoints = $join->getSourceJoinPoints();
                foreach ($joinPoints as $joinPoint) {
                    $context = $joinPoint->getContext();
                    if (!isset($this->joinsByUri[$context])) {
                        $this->joinsByUri[$context] = [];
                    }
                    if (!isset($this->joinPointsToJoins[$joinPoint])) {
                        $this->joinPointsToJoins[$joinPoint] = new ArrayObject(
                        );
                    }
                    $meta = $this->getUriRegexpMeta($joinPoint->getUri());
                    $joinedContexts = [];
                    foreach (
                        $join->getDestinationJoinPoints(
                        ) as $destinationJoinPoint
                    ) {
                        $joinedContext = $destinationJoinPoint->getContext();
                        if ($joinedContext === $context) {
                            continue;
                        }
                        if (isset($this->joinPointsToJoins[$joinPoint][$joinedContext])) {
                            throw new Exception(
                                'Multiple joins for the same join point to the same context'
                            );
                        }
                        $this->joinPointsToJoins[$joinPoint][$joinedContext] = $join;
                        $joinedContexts[] = $joinedContext;
                        if (!isset($this->joinsByUri[$context][$joinedContext])) {
                            $this->joinsByUri[$context][$joinedContext] = [];
                        }
                        if (isset($this->joinsByUri[$context][$joinedContext][$meta['uri']])) {
                            if (
                                $this->joinsByUri[$context][$joinedContext][$meta['uri']] !==
                                $join
                            ) {
                                throw new Exception(
                                    'Multiple joins for the same uri to the same target context'
                                );
                            }
                        } else {
                            $this->joinsByUri[$context][$joinedContext][$meta['uri']] = $join;
                        }
                    }
                    $joinPointsByContext[$context][] = [
                        'meta' => $meta,
                        'joinedContexts' => $joinedContexts
                    ];
                }
            }

            $this->joinLookups = [];

            foreach ($joinPointsByContext as $sourceContext => $sourceContextMetaRecs) {
                if (!isset($this->joinLookups[$sourceContext])) {
                    $this->joinLookups[$sourceContext] = [];
                }
                $joinRegexps = [];
                usort(
                    $sourceContextMetaRecs,
                    function ($a, $b) {
                        return
                            strlen($b['meta']['uri']) -
                            strlen($a['meta']['uri']);
                    }
                );
                foreach ($sourceContextMetaRecs as $sourcePointMetaRec) {
                    $meta = $sourcePointMetaRec['meta'];
                    $escaped = preg_quote(
                        $meta['uri'],
                        self::REGEXP_SEPARATOR
                    );
                    if ($meta['hasTrailingSlash']) {
                        if ($meta['isTrailingSlashInsensitive']) {
                            $joinRegexp = "{$escaped}(?=[/?#]|$)";
                        } else {
                            $joinRegexp = "{$escaped}";
                        }
                    } else {
                        $joinRegexp = "{$escaped}(?=[?#]|$)";
                    }

                    foreach ($sourcePointMetaRec['joinedContexts'] as $targetContext) {
                        if (!isset($joinRegexps[$targetContext])) {
                            $joinRegexps[$targetContext] = [];
                        }
                        $joinRegexps[$targetContext][] = $joinRegexp;
                    }
                }
                foreach ($joinRegexps as $targetContext => $regexps) {
                    $this->joinLookups[$sourceContext][$targetContext] =
                        self::REGEXP_SEPARATOR .
                        '^(?:' .
                        implode('|', $regexps) .
                        ')' .
                        self::REGEXP_SEPARATOR;
                }
            }
        }

        /**
         * @param Root $root
         * @return AbstractPath|null
         * @throws Exception
         */
        public function getParentRootPath(Root $root): ?AbstractPath
        {
            $this->internalPrime(false);
            $parentRoot = $this->rootParentMap[$root] ?? null;

            if ($parentRoot) {
                return $parentRoot->getPath($root->getMountUri());
            }

            return null;
        }

        /**
         * @param AbstractUri $uri
         * @param string $context
         * @return Root|null
         * @throws Exception
         */
        public function getRootWithUri(
            AbstractUri $uri,
            string $context = ''
        ): ?Root {
            $meta = $this->getUriRegexpMeta($uri);
            return $this->rootsByUri[$context][$meta['uri']] ?? null;
        }

        /**
         * @param AbstractPath $path
         * @param string $targetContext
         * @return AbstractPath|null
         * @throws Exception
         */
        public function mapPath(
            AbstractPath $path,
            string $targetContext
        ): ?AbstractPath {
            $sourceContext = $path->getContext();
            if ($sourceContext === $targetContext) {
                return $path;
            } else {
                $uri = $path->getUri();
                $join = $this->lookupJoin($uri, $sourceContext, $targetContext);
                if ($join) {
                    return $join->mapPath(
                        $path,
                        $targetContext
                    );
                }
                return null;
            }
        }

        /**
         * @param AbstractUri $uri
         * @param string $sourceContext
         * @param string $targetContext
         * @return Join|null
         * @throws Exception
         */
        public function lookupJoin(
            AbstractUri $uri,
            string $sourceContext,
            string $targetContext
        ): ?Join {
            $this->internalPrime(false);
            if (!isset($this->joinLookups[$sourceContext][$targetContext])) {
                return null;
            }
            if (
            preg_match(
                $this->joinLookups[$sourceContext][$targetContext],
                $uri->toString(),
                $match
            )
            ) {
                return $this->joinsByUri[$sourceContext][$targetContext][$match[0]];
            }
            return null;
        }

        /**
         * @param Join ...$joins
         * @return static
         */
        public function addJoin(Join ...$joins): self
        {
            foreach ($joins as $join) {
                if ($join->getUriMapper() !== $this) {
                    throw new InvalidArgumentException(
                        'Join is assigned to another URI mapper'
                    );
                }
                $this->joins[] = $join;
            }
            $this->isPrimed = false;
            return $this;
        }

        /**
         * @param Root ...$roots
         * @return static
         */
        public function addRoot(Root ...$roots): self
        {
            foreach ($roots as $root) {
                if ($root->getUriMapper() !== $this) {
                    throw new InvalidArgumentException(
                        'Root is assigned to another URI mapper'
                    );
                }
                $this->roots[] = $root;
            }
            $this->isPrimed = false;
            return $this;
        }

        /**
         * @throws Exception
         */
        public function prime(): void
        {
            $this->internalPrime(true);
        }
    }
