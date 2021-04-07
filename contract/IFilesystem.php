<?php

    namespace Exteon\UriMapper;

    interface IFilesystem
    {
        /**
         * @param false $excludeLast
         * @return bool
         */
        public function createDir($excludeLast = false): bool;

        /**
         * @return bool
         */
        public function exists(): bool;

        /**
         * @return string
         */
        public function getFilename(): string;

        /**
         * @return bool
         */
        public function isFile(): bool;

        /**
         * @param string $newName
         * @return bool
         */
        public function rename(string $newName): bool;

        /**
         * @param bool $includingDir
         * @return bool
         */
        public function rm(bool $includingDir = true): bool;

        /**
         * @return bool
         */
        public function isDir(): bool;
    }