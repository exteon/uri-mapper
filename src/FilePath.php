<?php

    namespace Exteon\UriMapper;

    use DirectoryIterator;
    use ErrorException;
    use Exception;
    use Exteon\FileHelper;
    use Exteon\Uri\UnixPathUri;
    use InvalidArgumentException;

    /**
     * @method UnixPathUri getUri()
     * @method UnixPathUri getRelativeUriClone()
     */
    class FilePath extends Path implements IStaticTraversable, IFilesystem
    {
        public function __construct(FileRoot $root, ?UnixPathUri $uri)
        {
            if($uri){
                FileRoot::validateFilePath($uri);
            }
            parent::__construct($root, $uri);
        }

        public function createDir($excludeLast = false): bool
        {
            return FileHelper::preparePath($this->getUnixPath(), $excludeLast);
        }

        public function getUnixPath(): string
        {
            return $this->getUri()->getUnixPath();
        }

        public function exists(): bool
        {
            return file_exists($this->getUnixPath());
        }

        /**
         * @return AbstractPath[]
         * @throws ErrorException
         */
        public function getDescendants(): array
        {
            $children = $this->getChildren();
            $descendants = [];
            foreach ($children as $child) {
                $descendants[] = $child;
                if($child instanceof IStaticTraversable){
                    $descendants = array_merge(
                        $descendants,
                        $child->getDescendants()
                    );
                }
            }
            return $descendants;
        }

        /**
         * @return AbstractPath[]
         * @throws ErrorException
         */
        public function getChildren(): array
        {
            $children = [];
            $filePath = $this->getUnixPath();
            if (is_dir($filePath)) {
                $iterator = new DirectoryIterator($filePath);
                foreach ($iterator as $fileInfo) {
                    if ($iterator->isDot()) {
                        continue;
                    }
                    $filename = $fileInfo->getFilename();
                    if(!$this->root->doesAllowSubroots()){
                        $child = clone $this;
                        $childUri = $this->getRelativeUriClone()->descend(
                            $filename
                        );
                        $child->setRelativeUri($childUri);
                        $children[] = $child;
                    } else {
                        $children[] = $this->descend($filename);
                    }
                }
            }
            return $children;
        }

        public function getFilename(): string
        {
            return $this->getUri()->getDocument();
        }

        public function isFile(): bool
        {
            return is_file($this->getUnixPath());
        }

        /**
         * @param string $newName
         * @return bool
         * @throws Exception
         */
        public function rename(string $newName): bool
        {
            if (strpos($newName, '/') !== false) {
                throw new InvalidArgumentException(
                    'Cannot rename to a different path'
                );
            }
            if (
                $newName === '.' |
                $newName === '..'
            ) {
                throw new InvalidArgumentException(
                    'Invalid name'
                );
            }
            if (!$this->getUri()) {
                throw new Exception('Cannot rename file root');
            }
            $newUri = (clone $this->getUri())->ascend(1)->descend($newName);
            $result = rename($this->getUnixPath(), $newUri->getUnixPath());
            if ($result) {
                $this->setRelativeUri($newUri);
            }
            return $result;
        }

        public function rm(bool $includingDir = true): bool
        {
            $path = $this->getUnixPath();
            if ($this->isDir()) {
                return FileHelper::rmDir($path, $includingDir);
            } else {
                return unlink($path);
            }
        }

        public function isDir(): bool
        {
            return is_dir($this->getUnixPath());
        }
    }