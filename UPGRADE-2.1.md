UPGRADE FROM 2.0 to 2.1
========================

- Class `Oro\Bundle\DotmailerBundle\Model\Action\AbstractMarketingListEntitiesAction`
    - changed the return type of `getMarketingListEntitiesByEmail` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
- Renamed method `Oro\Bundle\DotmailerBundle\Controller\AddressBookController::synchronizeAddressBook` to `Oro\Bundle\DotmailerBundle\Controller\AddressBookController::synchronizeAddressBookAction`.
- Renamed method `Oro\Bundle\DotmailerBundle\Controller\AddressBookController::synchronizeAddressBookDataFields` to `Oro\Bundle\DotmailerBundle\Controller\AddressBookController::synchronizeAddressBookDataFieldsAction`.
- Renamed method `Oro\Bundle\DotmailerBundle\Controller\DataFieldController::synchronize` to `Oro\Bundle\DotmailerBundle\Controller\DataFieldController::synchronizeAction`.