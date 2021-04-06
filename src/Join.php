<?php

    namespace Exteon\UriMapper;

    use Exception;
    use InvalidArgumentException;

    class Join
    {
        /** @var array<string,Root> */
        protected $sourceJoinPointsByContext = [];

        /** @var array<string,Root> */
        protected $destinationJoinPointsByContext = [];

        /** @var UriMapper */
        protected $uriMapper;

        /** @var JoinPoint[] */
        protected $sourceJoinPoints = [];

        /** @var JoinPoint[] */
        protected $destinationJoinPoints = [];

        /**
         * Join constructor.
         * @param UriMapper $uriMapper
         * @param JoinPoint[] $joinPoints
         */
        public function __construct(UriMapper $uriMapper, array $joinPoints)
        {
            foreach ($joinPoints as $joinPoint) {
                if (
                    $joinPoint->getType() == JoinPoint::TYPE_SOURCE ||
                    $joinPoint->getType() == JoinPoint::TYPE_BOTH
                ) {
                    $this->sourceJoinPoints[] = $joinPoint;
                    if (
                    !isset(
                        $this->sourceJoinPointsByContext[$joinPoint->getContext(
                        )]
                    )
                    ) {
                        $this->sourceJoinPointsByContext[$joinPoint->getContext(
                        )] = $joinPoint;
                    } else {
                        throw new InvalidArgumentException(
                            'Multiple joinpoints in the same join for the same source context'
                        );
                    }
                }
                if (
                    $joinPoint->getType() == JoinPoint::TYPE_DESTINATION ||
                    $joinPoint->getType() == JoinPoint::TYPE_BOTH
                ) {
                    $this->destinationJoinPoints[] = $joinPoint;
                    if (
                    !isset(
                        $this->destinationJoinPointsByContext[$joinPoint->getContext(
                        )]
                    )
                    ) {
                        $this->destinationJoinPointsByContext[$joinPoint->getContext(
                        )] = $joinPoint;
                    } else {
                        throw new InvalidArgumentException(
                            'Multiple joinpoints in the same join for the same destination context'
                        );
                    }
                }
            }
            $this->uriMapper = $uriMapper;
        }

        /**
         * @param Path $path
         * @param string $targetContext
         * @return Path|null
         * @throws Exception
         */
        public function mapPath(
            Path $path,
            string $targetContext = ''
        ): ?Path {
            $sourceContext = $path->getRoot()->getContext();
            $sourceJoinPoint = $this->getSourceJoinPointByContext(
                $sourceContext
            );
            $destinationJoinPoint = $this->getDestinationJoinPointByContext(
                $targetContext
            );
            if (
                !$sourceJoinPoint ||
                !$destinationJoinPoint
            ) {
                throw new InvalidArgumentException(
                    'Join cannot perform the mapping'
                );
            }
            $relative = (clone $path->getUri())->makeRelativeToBase(
                $sourceJoinPoint->getUri()
            );
            $newUri = (clone $destinationJoinPoint->getUri())->applyRelative(
                $relative
            );
            return $this->getUriMapper()->mapUri($newUri, $targetContext);
        }

        /**
         * @param string $context
         * @return JoinPoint|null
         */
        public function getSourceJoinPointByContext(string $context): ?JoinPoint
        {
            return $this->sourceJoinPointsByContext[$context] ?? null;
        }

        /**
         * @param string $context
         * @return JoinPoint|null
         */
        public function getDestinationJoinPointByContext(string $context
        ): ?JoinPoint {
            return $this->destinationJoinPointsByContext[$context] ?? null;
        }

        /**
         * @return UriMapper
         */
        public function getUriMapper(): UriMapper
        {
            return $this->uriMapper;
        }

        /**
         * @return JoinPoint[]
         */
        public function getSourceJoinPoints(): array
        {
            return $this->sourceJoinPoints;
        }

        /**
         * @return JoinPoint[]
         */
        public function getDestinationJoinPoints(): array
        {
            return $this->destinationJoinPoints;
        }
    }