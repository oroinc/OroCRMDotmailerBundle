The upgrade instructions are available at [Oro documentation website](https://doc.oroinc.com/master/backend/setup/upgrade-to-new-version/).

The current file describes significant changes in the code that may affect the upgrade of your customizations.

## Changes in the Dotdigital package versions

- [5.1.0](#510-2023-03-31)
- [5.0.0](#500-2022-01-26)
- [4.2.0](#420-2020-01-29)
- [4.1.0](#410-2020-01-31)
- [4.0.0](#400-2019-07-31)
- [3.1.0](#310-2019-01-30)
- [3.0.0](#300-2018-07-27)
- [2.6.0](#260-2018-01-31)
- [2.5.0](#250-2017-11-30)
- [2.3.0](#230-2017-07-28)
- [2.2.0](#220-2017-05-31)
- [2.1.0](#210-2017-03-30)

## 5.1.0 (2023-03-31)

[Show detailed list of changes](incompatibilities-5-1.md)

## 5.0.0 (2022-01-26)
[Show detailed list of changes](incompatibilities-5-0.md)

## 4.2.0 (2020-01-29)
[Show detailed list of changes](incompatibilities-4-2.md)

## 4.1.0 (2020-01-31)

[Show detailed list of changes](incompatibilities-4-1.md)

### Removed
* `*.class` parameters for all entities were removed from the dependency injection container.
The entity class names should be used directly, e.g. `'Oro\Bundle\EmailBundle\Entity\Email'`
instead of `'%oro_email.email.entity.class%'` (in service definitions, datagrid config files, placeholders, etc.), and
`\Oro\Bundle\EmailBundle\Entity\Email::class` instead of `$container->getParameter('oro_email.email.entity.class')`
(in PHP code).
* All `*.class` parameters for service definitions were removed from the dependency injection container.

## 4.0.0 (2019-07-31)
[Show detailed list of changes](incompatibilities-4-0.md)

### Changed
* In `Oro\Bundle\DotmailerBundle\Controller\AddressBookController::synchronizeAddressBookAction` 
 (`oro_dotmailer_synchronize_adddress_book` route)
 action the request method was changed to POST. 
* In `Oro\Bundle\DotmailerBundle\Controller\AddressBookController::synchronizeAddressBookDataFieldsAction` 
 (`oro_dotmailer_synchronize_adddress_book_datafields` route)
 action the request method was changed to POST. 
* In `Oro\Bundle\DotmailerBundle\Controller\AddressBookController::disconnectMarketingListAction` 
 (`oro_dotmailer_marketing_list_disconnect` route)
 action the request method was changed to DELETE. 
* In `Oro\Bundle\DotmailerBundle\Controller\DataFieldController::synchronizeAction` 
 (`oro_dotmailer_datafield_synchronize` route)
 action the request method was changed to POST. 
 
## 3.1.0 (2019-01-30)
[Show detailed list of changes](incompatibilities-3-1.md)

## 3.0.0 (2018-07-27)
[Show detailed list of changes](incompatibilities-3-0.md)

## 2.6.0 (2018-01-31)
[Show detailed list of changes](incompatibilities-2-6.md)

### Removed
* The parameter `oro_dotmailer.listener.datafield_remove.class` was removed form the service container

## 2.5.0 (2017-11-30)
[Show detailed list of changes](incompatibilities-2-5.md)

## 2.3.0 (2017-07-28)
[Show detailed list of changes](incompatibilities-2-3.md)

## 2.2.0 (2017-05-31)
[Show detailed list of changes](incompatibilities-2-2.md)

## 2.1.0 (2017-03-30)
[Show detailed list of changes](incompatibilities-2-1.md)

### Changed
- Class `AbstractMarketingListEntitiesAction`<sup>[[?]](https://github.com/oroinc/OroCRMDotmailerBundle/tree/2.1.0/Model/Action/AbstractMarketingListEntitiesAction.php "Oro\Bundle\DotmailerBundle\Model\Action\AbstractMarketingListEntitiesAction")</sup>
    - changed the return type of `getMarketingListEntitiesByEmail` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    
### Removed
- Method `AddressBookController::synchronizeAddressBook`<sup>[[?]](https://github.com/oroinc/OroCRMDotmailerBundle/tree/2.0.0/Controller/AddressBookController.php#L40 "Oro\Bundle\DotmailerBundle\Controller\AddressBookController::synchronizeAddressBook")</sup> was removed, use `AddressBookController::synchronizeAddressBookAction`<sup>[[?]](https://github.com/oroinc/OroCRMDotmailerBundle/tree/2.1.0/Controller/AddressBookController.php#L41 "Oro\Bundle\DotmailerBundle\Controller\AddressBookController::synchronizeAddressBookAction")</sup> instead.
- Method `AddressBookController::synchronizeAddressBookDataFields`<sup>[[?]](https://github.com/oroinc/OroCRMDotmailerBundle/tree/2.1.0/Controller/AddressBookController.php#L0 "Oro\Bundle\DotmailerBundle\Controller\AddressBookController::synchronizeAddressBookDataFields")</sup> was removed, use `AddressBookController::synchronizeAddressBookDataFieldsAction`<sup>[[?]](https://github.com/oroinc/OroCRMDotmailerBundle/tree/2.1.0/Controller/AddressBookController.php#L83 "Oro\Bundle\DotmailerBundle\Controller\AddressBookController::synchronizeAddressBookDataFieldsAction")</sup> instead.
- Method `DataFieldController::synchronize`<sup>[[?]](https://github.com/oroinc/OroCRMDotmailerBundle/tree/2.0.0/Controller/DataFieldController.php#L124 "Oro\Bundle\DotmailerBundle\Controller\DataFieldController::synchronize")</sup> was removed, use `DataFieldController::synchronizeAction`<sup>[[?]](https://github.com/oroinc/OroCRMDotmailerBundle/tree/2.1.0/Controller/DataFieldController.php#L124 "Oro\Bundle\DotmailerBundle\Controller\DataFieldController::synchronizeAction")</sup> instead.
