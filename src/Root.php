<?php

    namespace Exteon\UriMapper;

    use ErrorException;
    use Exteon\Uri\AbstractUri;
    use InvalidArgumentException;

    class Root implements IRoot
    {
        /** @var AbstractUri */
        protected $mountUri;

        /** @var string */
        protected $context;

        /** @var UriMapper */
        protected $uriMapper;

        /** @var AbstractUri|null */
        protected $prefixUri;

        /** @var bool */
        protected $doesAllowSubroots;

        /**
         * Root constructor.
         * @param UriMapper $uriMapper
         * @param AbstractUri $mountUri
         * @param string $context
         * @param bool $doesAllowSubroots
         */
        public function __construct(
            UriMapper $uriMapper,
            AbstractUri $mountUri,
            string $context = '',
            bool $doesAllowSubroots = true
        ) {
            if (!$mountUri->isRooted()) {
                throw new InvalidArgumentException('Root URI must be rooted!');
            }
            $this->mountUri = $mountUri;
            if (
                $mountUri->hasTrailingSlash() ||
                $mountUri::isTrailingSlashInsensitive()
            ) {
                $this->prefixUri = (clone $mountUri)->setQueryString(
                    null
                )->setFragment(null);
            } else {
                $this->prefixUri = null;
            }
            $this->context = $context;
            $this->uriMapper = $uriMapper;
            $this->doesAllowSubroots = $doesAllowSubroots;
        }

        /**
         * @return AbstractUri
         */
        public function getMountUri(): AbstractUri
        {
            return $this->mountUri;
        }

        /**
         * @return string
         */
        public function getContext(): string
        {
            return $this->context;
        }

        /**
         * @param AbstractUri|null $uri
         * @return Path
         * @throws ErrorException
         */
        public function getPath(?AbstractUri $uri = null): Path
        {
            return new Path($this, $uri);
        }

        /**
         * @return AbstractUri|null
         */
        public function getPrefixUri(): ?AbstractUri
        {
            return $this->prefixUri;
        }

        /**
         * @return UriMapper
         */
        public function getUriMapper(): UriMapper
        {
            return $this->uriMapper;
        }

        /**
         * @return bool
         */
        public function doesAllowSubroots(): bool
        {
            return $this->doesAllowSubroots;
        }
    }