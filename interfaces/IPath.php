<?php

    namespace Exteon\UriMapper;

    use Exteon\Uri\AbstractUri;

    interface IPath
    {
        /**
         * @param int $levels
         * @return self|null
         */
        public function ascend(int $levels = 1): ?self;

        /**
         * @return UriMapper
         */
        public function getUriMapper(): UriMapper;

        /**
         * @param string $path
         * @return self|null
         */
        public function descend(string $path): ?self;

        /**
         * @return AbstractUri
         */
        public function getUri(): AbstractUri;

        /**
         * @return Root
         */
        public function getRoot(): Root;

        /**
         * @param string $targetContext
         * @return self|null
         */
        public function toContext(string $targetContext): ?self;

        /**
         * @return string
         */
        public function getContext(): string;

        public function toString(): string;
    }