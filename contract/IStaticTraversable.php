<?php

    namespace Exteon\UriMapper;

    interface IStaticTraversable
    {
        /**
         * @return AbstractPath[]
         */
        public function getDescendants(): array;

        /**
         * @return AbstractPath[]
         */
        public function getChildren(): array;
    }