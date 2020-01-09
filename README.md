# OroDotmailerBundle

OroDotmailerBundle provides [integration](https://github.com/oroinc/platform/tree/master/src/Oro/Bundle/IntegrationBundle) with [Dotmailer](http://www.dotmailer.com/) marketing automation platform for Oro applications.

The bundle enables users to create and configure the integration, connect Oro marketing lists to Dotmailer address books, provides UI to set data fields mapping between Dotmailer data and Oro applications entities, and enables to schedule synchronization process or start it manually.

## Setting Up the Connection

First of all, a new Integration with type "dotmailer" must be created.

Go to the "System -> Integrations" and click "Create Integration" button. Define the following settings:

 - *Type*: dotmailer
 - *Name*: meaningful integration name
 - *Username*: an API user name from your dotmailer "Manage users" page
 - *Password*: fill in the password of the API user defined at the Username field (more than 8 characters).
 - *Client Id*: the client id to use for single sign-on. https://developer.dotmailer.com/docs/using-oauth-20-with-dotmailer
 - *Client Secret Key*: the client secret key to use for single sign-on. https://developer.dotmailer.com/docs/using-oauth-20-with-dotmailer
 - *Custom OAuth Domain*: fill in custom domain if it's is used in dotmailer. By default https://r1-app.dotmailer.com/ is used
 - *Default Owner*: Select the owner of the integration. The selected user will be defined as the owner for all the records imported within the integration.

After the Username and Password have been filled in, the **Check Connection** button appears. Click the button to check the credentials validity before saving the Integration.

*Note*: OroCRM exports data into dotmailer asynchronously using a message queue processor. This export job has low priority, as it has to wait until dotmailer WatchDog finishes its checks and the time it takes is unpredictable. Until dotmailer returns the export status, the OroCRM address book is not updated. To avoid any discrepancies in the dotmailer and OroCRM data, the running export process blocks launching any new exports to dotmailer. As soon as OroCRM gets the response from dotmailer about the export process completion, Marketing List statuses are updated in OroCRM and further  exports are processed.

## Connecting Marketing List to dotmailer

After the integration has been created and its status has been set to Active, the list of Address Books will be automatically imported from dotmailer to Oro, and Oro Marketing Lists may be connected to the dotmailer Address Books.

> Only Marketing Lists with Email fields can be connected.

If a Marketing list is suitable for the connection, the **Connect to dotmailer** button will appear on the Marketing List view page.
Each Marketing List may be connected only to one dotmailer Address Book and each dotmailer Address Book may be connected only to one Marketing List, so each Oro Marketing Lists connected will be represented as an Address Book in dotmailer.

When **Connect to dotmailer** button is clicked, the form with two selectors will emerge:

 - *Integration*: the selector contains all the dotmailer integrations available in the Oro instance. Select the integration with the dotmailer instance, for which the connection must be performed. 
 - *Address Book*: the selector contains all the dotmailer Address Book records [created](https://support.dotmailer.com/entries/20663833-Creating-an-address-book) in dotmailer User Interface and available for connection. The selector does not contain the "All Contacts" and "Test" Address Books (automatically generated in dotmailer), nor the Address Books that have already been connected to another Marketing List in Oro.
 - *Create New Entities*: checkbox to control if new entities can be created. If a contact is found in address book and there is no entity in the application with such contact's email address, new entity will be created in the application based on the mapping setup. New entity will be created only in case there is a "two way sync" mapping configured for each entity's required fields.

After the connection has been saved, the Marketing list contacts will be automatically exported from Oro to dotmailer.
Since then, data synchronization (import and export) between Oro and dotmailer will be automatically performed once every 4 minutes.

> Job Queue Daemon has to be running.

After the connection has been saved, the **Connect to dotmailer** button will disappear, and the "dotmailer" action drop-down menu will appear instead. The following options are available in the menu:

- "Connection Settings": edit the connection settings
- "Disconnect": disconnect the Marketing List from the Address Book
- "Synchronize": manually start the synchronization between the Marketing List and the Address Book.
- "Refresh Data Fields": manually mark all Marketing List records as updated to make sure data fields data is up to date in dotmailer after next synchronization. 

## Managing dotmailer Data fields and mappings

### Data fields
If at least one dotmailer integration is created, a new dotmailer menu group is availabe under Marketing.
Under Marketing->dotmailer->Data Fields you can view, remove or create new dotmailer datafields.
By default, data fields are synchronized with dotmailer once a day. This interval can be changed under System->Configuration->Integrations->dotmailer Settings
Synchronization can be also trigged manually with "Synchronize" button from data fields grid.
Existing data fields cannot be updated because API does not allow it.
### Data fields mapping
In order to export/import specific data fields from/to dotmailer, you can build mapping between Oro entities fields and dotmailer datafields.
When new integration is created, default mappings for common data fields (FIRSTNAME, LASTNAME and FULLNAME) are added automatically for crm entities (Contact, Lead etc.)
Existing mapping can be updated and new mappings can be added using mapping form and mapping configuration widget.
You can map several entity fields to one dotmailer string data field. In this case entity's fields values will be concated with a blank, e.g. "Firstname Lastname".
"Two Way Sync" checkbox should be checked if you want to update application entities with data from dotmailer.
### Data fields values synchronization
After mapping is configured, tracking of changes done on mapped real fields is performed automatically and processed every 5 minutes.
Changes done on virtual fields used in the mappings are not tracked. `oro_dotmailer.on_build_mapping_tracked_fields`
event can be used to customize the list of fields to track. 

You can trigger re-sync of data fields manually on required marketing list from marketing list view dotmailer settings. Alternatively, 
system configuration setting can be set under System->Configuration->Integrations->dotmailer, to perform daily force update of data fields.
Possible options:
- *None*: No force sync is performed.
- *For mappings with virtual fields only* (default): Perform force fields update only if a mapping has at least one virtual field used. 
- *For all mappings*: Perform force fields update for all marketing lists.

## dotmailer Campaign Creation

Once a Marketing List has been connected to a dotmailer Address Book, its contacts may be used to send dotmailer campaigns. Oro collects the campaign and user activity statistics for the campaigns sent to the contacts in an Address Book connected to a Marketing Lists. The statistics will be collected ONLY WHEN a dotmailer campaign has been sent to the contacts on the Address Book (unless a dotmailer Campaign has been sent, no statistics will be collected in Oro).


## Import Synchronization Logic

Import is performed with *oro:cron:integration:sync* cron command after the integration has been saved and once in every four minutes after a connection has been created.

 - **Address Book**: All dotmailer Address Books are imported except "All Contacts" and "Test" (these Books are created for each dotmailer Account by default).
 - **Campaign**:  Details of campaigns sent to the contacts on Address Books connected to Oro Marketing Lists are imported.

For each dotmailer campaign imported, a new Email Campaign and a Marketing Campaign will be created in Oro. During the import, the campaign related details are synchronized during the following imports as follows:

 - **dotmailer Contact**: Import all the dotmailer Contacts from all the Address Books imported to Oro (the contacts are added to the database and used at the backend, they won't be seen in the UI).
 - **Unsubscribed Contact**: Import all the contacts suppressed/unsubscribed from the Address Book since the first import. Status of this contacts in the related Oro Marketing Lists is set to unsubscribed.
 - **Contact Activity**: All the contact activities performed within a dotmailer Campaign previously imported to OroCRM are imported to Oro. Activities (send, open, click etc.) are additionaly stored as marketing activities. In case several dotmailer email campaigns should be a part of a single marketing campaign, several automatically generated marketing campaigns can be merged within campaigns grid.
 - **Campaign Summary**: Campaign summary is imported for each Campaign previously imported to Oro.

Each contact activity is mapped to Oro Marketing List Item and Email Campaign Statics by the Email value.


## Export Logic

Export of the campaign details from Oro to dotmailer is performed with *oro:cron:dotmailer:export* cron command once in every four minutes after a connection has been created.

Export is performed in 4 steps, as follows:

 - **Exporting Removed Contacts**: If a subscriber has been removed/unsubscribed from an Oro marketing list, the contacts are removed from the connected Address Book.
 - **Sync Marketing List Item State**: Subscribers of the Oro Marketing list are checked against the Unsubscribed Contacts of the related Marketing Campaign and unsubscribed from the Marketing List if necessary.
 - **Preparing Contacts for Export**: Status of contacts to be exported to dotmailer is changed correpondingly.
 - **Exporting Contacts**: A csv file with contacts to be exported is sent to dotmailer

After export is finished, command check export status on dotmailer side. If export is finished on dotmailer side command import dotmailer contacts to get origin Id from dotmailer. Otherwise next export command will do it.

## dotmailer Single Sign-on
To be able to enter dotmailer account straight from the application, dotmailer provides single sign-on feature https://developer.dotmailer.com/docs/using-oauth-20-with-dotmailer.
To use signel sign-on, you need to obtain api key and secret from your dotmailer manager and put them during integration configuration.
The requested callback url to provide is https://{your domain}/dotmailer/oauth/callback. 
After this, navigate to Marketing->dotmailer->Email Studio and choose the integration you'd like to connect with your dotmailer account.
Click connect button to perform the OAuth authorization. After successful login to your dotmailer account, you should be redirected back to the application and see your dotmailer account dashboard in the iframe.
After this you will not need to login into dotmailer account each time and can access it from the application.

Resources
---------

  * [OroCommerce, OroCRM and OroPlatform Documentation](https://doc.oroinc.com)
  * [Contributing](https://doc.oroinc.com/community/contribute/)
