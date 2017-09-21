## 2.3.0 (2017-07-28)
[Show detailed list of changes](file-incompatibilities-2-3-0.md)

## 2.2.0 (2017-05-31)
[Show detailed list of changes](file-incompatibilities-2-2-0.md)

## 2.1.0 (2017-03-30)
[Show detailed list of changes](file-incompatibilities-2-1-0.md)
### Changed
- Class `Oro\Bundle\DotmailerBundle\Model\Action\AbstractMarketingListEntitiesAction`
    - changed the return type of `getMarketingListEntitiesByEmail` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
### Removed
- Method `Oro\Bundle\DotmailerBundle\Controller\AddressBookController::synchronizeAddressBook` was removed, use `Oro\Bundle\DotmailerBundle\Controller\AddressBookController::synchronizeAddressBookAction` instead.
- Method `Oro\Bundle\DotmailerBundle\Controller\AddressBookController::synchronizeAddressBookDataFields` was removed, use `Oro\Bundle\DotmailerBundle\Controller\AddressBookController::synchronizeAddressBookDataFieldsAction` instead.
- Method `Oro\Bundle\DotmailerBundle\Controller\DataFieldController::synchronize` was removed, use `Oro\Bundle\DotmailerBundle\Controller\DataFieldController::synchronizeAction` instead.
