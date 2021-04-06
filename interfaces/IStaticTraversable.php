<?php

    namespace Exteon\UriMapper;

    interface IStaticTraversable
    {
        /**
         * @return Path[]
         */
        public function getDescendants(): array;

        /**
         * @return Path[]
         */
        public function getChildren(): array;
    }