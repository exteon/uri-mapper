<?php

    namespace Exteon\UriMapper;

    use ErrorException;
    use Exteon\Uri\AbstractUri;

    abstract class AbstractPath
    {
        /**
         * @param int $levels
         * @return self|null
         */
        public abstract function ascend(int $levels = 1): ?self;

        /**
         * @return UriMapper
         */
        public abstract function getUriMapper(): UriMapper;

        /**
         * @param string $path
         * @return self|null
         */
        public abstract function descend(string $path): ?self;

        /**
         * @return AbstractUri
         */
        public abstract function getUri(): AbstractUri;

        /**
         * @return Root
         */
        public abstract function getRoot(): Root;

        /**
         * @param string $targetContext
         * @return self|null
         */
        public abstract function toContext(string $targetContext): ?self;

        /**
         * @return string
         */
        public abstract function getContext(): string;

        public abstract function toString(): string;

        /**
         * @param self $instance
         * @return static
         * @throws ErrorException
         */
        public static function type(self $instance): self
        {
            if (!($instance instanceof static)) {
                throw new ErrorException('Invalid type');
            }
            return $instance;
        }

    }