# OroCRMDotmailerBundle

This Bundle provides integration with [Dotmailer](http://www.dotmailer.com/) for OroCRM.
It allows to relate Marketing List to Dotmailer address book and receive Dotmailer Email Campaign statistics
back in OroCRM.

## Setting Up the Connection

First of all, a new Integration with type "Dotmailer" must be created.

Go to the "System -> Integrations" and click "Create Integration" button. Define the following settings:

 - *Type*: Dotmailer
 - *Name*: meaningful integration name
 - *Username*: an API user name from your Dotmailer "Manage users" page
 - *Password*: fill in the password of the API user defined at the Username field (more than 8 characters).
 - *Default Owner*: Select the owner of the integration. The selected user will be defined as the owner for all the records imported within the integration.

After the Username and Password have been filled in, the *Check Connection* button appears. Click the button to check the credentials validity before saving the Integration.

## Connecting Marketing List to Dotmailer

After the integration has been created and its status has been set to Active, the list of Address Books will be automatically imported from Dotmailer to OroCRM, and OroCRM Marketing Lists may be connected to the Dotmailer Address Books.

> Only Marketing Lists with Email fields can be connected.

If a Marketing list is suitable for the connection, the "Connect to Dotmailer" button will appear on the Marketing List view page.
Each Marketing List may be connected only to one Dotmailer Address Book and each Dotmailer Address Book may be connected only to one Marketing List, so each OroCRM Marketing Lists connected will be represented as an Address Book in Dotmailer.

When "Connect to Dotmailer" button is clicked, the form with two selectors will emerge:

 - *Integration*: the selector contains all the Dotmailer integrations available in the OroCRM instance. Select the integration with the Dotmailer instance, for which the connection must be performed. 
 - *Address Book*: the selector contains all the Dotmailer Address Book records [created](https://support.dotmailer.com/entries/20663833-Creating-an-address-book) in Dotmailer User Interface and available for connection. The selector does not contain the "All Contacts" and "Test" Address Books (automatically generated in Dotmailer), nor the Address Books that have already been connected to another Marketing List in OroCRM.

After the connection has been saved, the Marketing list contacts will be automatically exported from OroCRM to Dotmailer.
Since then, data synchronization (import and export) between OroCRM and Dotmailer will be automatically performed once in every 4 minutes.

> Job Queue Daemon has to be running.

After the connection has been saved, the "Connect to Dotmailer" button will disappear, and the "Dotmailer" action drop-down menu will appear instead. The following options are available in the menu:

- "Connection Settings": edit the connection settings
- "Disconnect": disconnect the Marketing List from the Address Book
- "Synchronize": manually start the synchronization between the Marketing List and the Address Book.


## Dotmailer Campaign Creation

Once a Marketing List has been connected to a Dotmailer Address Book, its contacts may be used to send Dotmailer campaigns. OroCRM collects the campaign and user activity statistics for the campaigns sent to the contacts in an Address Book connected to a Marketing Lists. The statistics will be collected ONLY WHEN a Dotmailer campaign has been sent to the contacts on the Address Book (unless a Dotmailer Campaign has been sent, no statistics will be collected in OroCRM).


## Import Synchronization Logic

Import is performed with *oro:cron:integration:sync* cron command after the integration has been saved and once in every four minutes after a connection has been created.

 - **Address Book**: All Dotmailer Address Books are imported except "All Contacts" and "Test" (these Books are created for each Dotmailer Account by default).
 - **Campaign**:  Details of campaigns sent to the contacts on Address Books connected to OroCRM Marketing Lists are imported.

For each Dotmailer campaign imported, a new Email Campaign will be created in OroCRM. During the import, the campaign related details are synchronized during the following imports as follows:

 - **Dotmailer Contact**: Import all the Dotmailer Contacts from all the Address Books imported to OroCRM (the contacts are added to the database and used at the backend, they won't be seen in the UI).
 - **Unsubscribed Contact**: Import all the contacts suppressed/unsubscribed from the Address Book since the first import. Status of this contacts in the related OroCRM Marketing Lists is set to unsubscribed.
 - **Contact Activity**: All the contact activities performed within a Dotmailer Campaign previously imported to ORoCRM are imported to OroCRM.
 - **Campaign Summary**: Campaign summary is imported for each Campaign previously imported to OroCRM.

Each contact activity is mapped to OroCRM Marketing List Item and Email Campaign Statics by the Email value.


## Export Logic

Export of the campaign details from OroCRM to Dotmailer is performed with *oro:cron:dotmailer:export* cron command once in every four minutes after a connection has been created.

Export is performed in 4 steps, as follows:

 - **Exporting Removed Contacts**: If a subscriber has been removed/unsubscribed from an OroCRM marketing list, the contacts are removed from the connected Address Book.
 - **Sync Marketing List Item State**: Subscribers of the OroCRM Marketing list are checked against the Unsubscribed Contacts of the related Marketing Campaign and unsubscribed from the Marketing List if necessary.
 - **Preparing Contacts for Export**: Status of contacts to be exported to Dotmailer is changed correpondingly.
 - **Exporting Contacts**: A csv file with contacts to be exported is sent to Dotmailer

After export is finished, command check export status on Dotmailer side. If export is finished on Dotmailer side command import Dotmailer contacts to get origin Id from Dotmailer. Otherwise next export command will do it.
