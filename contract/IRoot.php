<?php

    namespace Exteon\UriMapper;

    use Exteon\Uri\AbstractUri;

    interface IRoot
    {
        /**
         * @return AbstractUri
         */
        public function getMountUri(): AbstractUri;

        /**
         * @return string
         */
        public function getContext(): string;

        /**
         * @param AbstractUri|null $uri
         * @return AbstractPath
         */
        public function getPath(?AbstractUri $uri = null): AbstractPath;

        /**
         * @return AbstractUri|null
         */
        public function getPrefixUri(): ?AbstractUri;

        /**
         * @return UriMapper
         */
        public function getUriMapper(): UriMapper;

        /**
         * @return bool
         */
        public function doesAllowSubroots(): bool;
    }