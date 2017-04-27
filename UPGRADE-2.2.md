UPGRADE FROM 2.1 to 2.2
=======================

- Class `Oro\Bundle\DotmailerBundle\Async\ExportContactsStatusUpdateProcessor`
    - construction signature was changed now it takes next arguments:
        - `DoctrineHelper` $doctrineHelper,
        - `ExportManager` $exportManager,
        - `JobRunner` $jobRunner,
        - `TokenStorageInterface` $tokenStorage,
        - `LoggerInterface` $logger
