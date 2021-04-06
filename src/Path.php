<?php

    namespace Exteon\UriMapper;

    use ErrorException;
    use Exception;
    use Exteon\Uri\AbstractUri;
    use InvalidArgumentException;

    class Path implements IPath
    {
        /** @var Root */
        protected $root;

        /** @var AbstractUri|null */
        protected $relativeUri;

        /** @var AbstractUri|null */
        protected $absoluteUri;

        /**
         * Path constructor.
         * @param Root $root
         * @param AbstractUri|null $uri
         * @throws ErrorException
         * @throws Exception
         */
        public function __construct(Root $root, ?AbstractUri $uri)
        {
            $this->root = $root;
            $prefixUri = $root->getPrefixUri();
            if (!$prefixUri) {
                throw new ErrorException(
                    'Can only derive from directory roots'
                );
            }
            if (!$uri) {
                $relativeUri = $uri;
            } elseif (
            $uri->isRooted()
            ) {
                $relativeUri = clone $uri;
                $relativeUri->makeRelativeToBase($prefixUri);
                if ($relativeUri->isRooted()) {
                    throw new InvalidArgumentException(
                        'Absolute uri must have the root as prefix'
                    );
                }
            } else {
                $relativeUri = $uri;
            }
            $this->setRelativeUri($relativeUri);
        }

        /**
         * @param Path $instance
         * @return Path
         * @throws ErrorException
         */
        public static function type(Path $instance): Path
        {
            if (!($instance instanceof static)) {
                throw new ErrorException('Invalid type');
            }
            return $instance;
        }

        /**
         * @param int $levels
         * @return Path|null
         * @throws ErrorException
         * @throws Exception
         */
        public function ascend(int $levels = 1): ?IPath
        {
            if ($levels <= 0) {
                throw new InvalidArgumentException(
                    '$levels must be a positive integer'
                );
            }
            $pathDepth = $this->relativeUri->getPathDepth();
            if ($pathDepth >= $levels) {
                $relative = (clone $this->relativeUri)->ascend($levels);
                return $this->root->getPath($relative);
            }
            $parentRoot = $this->getUriMapper()->getParentRootPath(
                $this->root
            );
            if ($parentRoot) {
                return $parentRoot->ascend($levels - $pathDepth);
            }
            return null;
        }

        /**
         * @return UriMapper
         */
        public function getUriMapper(): UriMapper
        {
            return $this->root->getUriMapper();
        }

        /**
         * @param string $path
         * @return Path|null
         * @throws ErrorException
         * @throws Exception
         */
        public function descend(string $path): ?IPath
        {
            if (!$path) {
                return $this;
            } else {
                UriMapper::validateRelativePath($path);
                $sameRootPath = !$this->root->doesAllowSubroots();
                if ($sameRootPath) {
                    // Performance implementation
                    $descend = clone $this;
                    $uri = $this->getRelativeUriClone()->descend($path);
                    $descend->setRelativeUri($uri);
                    return $descend;
                }
                $uri = clone $this->getUri();
                $uri->descend($path);
                return $this->getUriMapper()->mapUri(
                    $uri,
                    $this->root->getContext()
                );
            }
        }

        /**
         * Used for performance descend to get a clone of our relativePath
         * or a new path if null
         *
         * @return AbstractUri
         */
        protected function getRelativeUriClone(): AbstractUri
        {
            $relativeUri = $this->getRelativeUri();
            if ($relativeUri) {
                return clone $relativeUri;
            }
            $uriType = get_class($this->root->getMountUri());
            return new $uriType();
        }

        /**
         * @return AbstractUri|null
         */
        public function getRelativeUri(): ?AbstractUri
        {
            return $this->relativeUri;
        }

        /**
         * @param AbstractUri|null $relativeUri
         */
        public function setRelativeUri(?AbstractUri $relativeUri): void
        {
            $this->relativeUri = $relativeUri;
            $this->absoluteUri = null;
        }

        /**
         * @return AbstractUri
         */
        public function getUri(): AbstractUri
        {
            if ($this->absoluteUri) {
                return $this->absoluteUri;
            }
            if ($this->relativeUri) {
                $absoluteUri = (clone $this->getRoot()->getPrefixUri(
                ))->applyRelative($this->relativeUri);
            } else {
                $absoluteUri = (clone $this->getRoot()->getMountUri(
                ))->setQueryString(null)->setFragment(null);
            }
            $this->absoluteUri = $absoluteUri;
            return $absoluteUri;
        }

        /**
         * @return Root
         */
        public function getRoot(): Root
        {
            return $this->root;
        }

        /**
         * @param string $targetContext
         * @return Path|null
         * @throws Exception
         */
        public function toContext(string $targetContext): ?IPath
        {
            if ($this->getContext() === $targetContext) {
                return $this;
            }
            return $this->getUriMapper()->mapPath(
                $this,
                $targetContext
            );
        }

        /**
         * @return string
         */
        public function getContext(): string
        {
            return $this->root->getContext();
        }

        public function toString(): string
        {
            return $this->getUri()->toString();
        }
    }