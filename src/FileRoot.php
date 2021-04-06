<?php

    namespace Exteon\UriMapper;

    use ErrorException;
    use Exteon\Uri\AbstractUri;
    use Exteon\Uri\UnixPathUri;
    use InvalidArgumentException;

    class FileRoot extends Root
    {
        /**
         * Root constructor.
         * @param UriMapper $uriMapper
         * @param UnixPathUri $mountUri
         * @param string $context
         * @param bool $doesAllowSubroots
         */
        public function __construct(
            UriMapper $uriMapper,
            UnixPathUri $mountUri,
            string $context = '',
            bool $doesAllowSubroots = true
        ) {
            self::validateFilePath($mountUri);
            parent::__construct(
                $uriMapper,
                $mountUri,
                $context,
                $doesAllowSubroots
            );
        }

        public static function validateFilePath(UnixPathUri $uri): void
        {
            if (preg_match('`(^|/)(\\.|\\.\\.)(/|$)`', $uri->getPath())) {
                throw new InvalidArgumentException(
                    'File paths must not contain \'.\' or \'..\' as path fragments'
                );
            }
        }

        /**
         * @param AbstractUri|null $uri
         * @return FilePath
         * @throws ErrorException
         */
        public function getPath(?AbstractUri $uri = null): Path
        {
            return new FilePath($this, $uri ? UnixPathUri::from($uri) : null);
        }
    }