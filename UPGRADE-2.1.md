UPGRADE FROM 2.0 to 2.1
========================

####General
- Changed minimum required php version to 7.0
- Updated dependency to [fxpio/composer-asset-plugin](https://github.com/fxpio/composer-asset-plugin) composer plugin to version 1.3.
- Composer updated to version 1.4.

```
    composer self-update
    composer global require "fxp/composer-asset-plugin"
```

- Class `Oro\Bundle\DotmailerBundle\Model\Action\AbstractMarketingListEntitiesAction`
    - changed the return type of `getMarketingListEntitiesByEmail` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
- Renamed method `Oro\Bundle\DotmailerBundle\Controller\AddressBookController::synchronizeAddressBook` to `Oro\Bundle\DotmailerBundle\Controller\AddressBookController::synchronizeAddressBookAction`.
- Renamed method `Oro\Bundle\DotmailerBundle\Controller\AddressBookController::synchronizeAddressBookDataFields` to `Oro\Bundle\DotmailerBundle\Controller\AddressBookController::synchronizeAddressBookDataFieldsAction`.
- Renamed method `Oro\Bundle\DotmailerBundle\Controller\DataFieldController::synchronize` to `Oro\Bundle\DotmailerBundle\Controller\DataFieldController::synchronizeAction`.