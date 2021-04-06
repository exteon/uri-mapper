<?php

    namespace Exteon\UriMapper;

    use Exteon\Uri\AbstractUri;
    use InvalidArgumentException;

    class JoinPoint
    {
        public const TYPE_SOURCE = 1;
        public const TYPE_DESTINATION = 2;
        public const TYPE_BOTH = 3;

        /** @var AbstractUri */
        protected $uri;

        /** @var string */
        protected $context;

        /** @var UriMapper */
        protected $uriMapper;

        /** @var AbstractUri|null */
        protected $prefixUri;

        /** @var int */
        protected $type;

        /**
         * Root constructor.
         * @param UriMapper $uriMapper
         * @param AbstractUri $uri
         * @param string $context
         * @param int $type
         */
        public function __construct(
            UriMapper $uriMapper,
            AbstractUri $uri,
            string $context = '',
            int $type = self::TYPE_BOTH
        ) {
            if (!$uri->isRooted()) {
                throw new InvalidArgumentException('Root URI must be rooted!');
            }
            $this->uri = $uri;
            if (
                $uri->hasTrailingSlash() ||
                $uri::isTrailingSlashInsensitive()
            ) {
                $this->prefixUri = $uri;
            } else {
                $this->prefixUri = null;
            }
            $this->context = $context;
            $this->uriMapper = $uriMapper;
            $this->type = $type;
        }

        /**
         * @return AbstractUri
         */
        public function getUri(): AbstractUri
        {
            return $this->uri;
        }

        /**
         * @return string
         */
        public function getContext(): string
        {
            return $this->context;
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
         * Returns one of self::TYPE_*
         * @return int
         */
        public function getType(): int
        {
            return $this->type;
        }
    }