DotmailerBundle
---------------
* The `ExportManager`<sup>[[?]](https://github.com/oroinc/OroCRMDotmailerBundle/blob/2.4/Model/ExportManager.php "Oro\Bundle\DotmailerBundle\Model\ExportManager")</sup> was divided into two managers, `Oro\Bundle\DotmailerBundle\Model\ExportManager` and `Oro\Bundle\DotmailerBundle\Model\QueueExportManager`.
* The `ExportManager::__construct`<sup>[[?]](https://github.com/oroinc/OroCRMDotmailerBundle/blob/2.4/Model/ExportManager.php#L46 "Oro\Bundle\DotmailerBundle\Model\ExportManager::__construct")</sup> now have only one dependency on the `Doctrine\Common\Persistence\ManagerRegistry`.
