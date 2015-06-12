# OroCRMDotmailerBundle

This Bundle provides integration with [Dotmailer](http://www.dotmailer.com/) for OroCRM.
It allows to relate Marketing List to Dotmailer address book and receive Dotmailer Email Campaign statistics
back in OroCRM. Synchronization contacts between Dotmailer and OroCRM.

## Setting Up the Connection

First of all, a new Integration with type "Dotmailer" must be created.

Go to the "System -> Integrations" and click "Create Integration" button.

 - *Type*: must be set to Dotmailer
 - *Name*: must be filled with meaningful integration name
 - *Username*: must be a API user name from your Dotmailer "Manage users" page.
 - *Password*: is a password. It should have 8 characters or more.
 - *Check Connection*: is a button. You can check connection and your credentials before save Integration using "Check Connection" button.
 It appears only after the Username and Password with 8 characters or more has been filled..
 - *Default Owner*: Select the owner of the integration. All entities imported from the integration will be assigned to the selected user.


## Connecting Marketing List to Dotmailer

After integration is created and enabled Marketing Lists may be connected to Dotmailer Address Book.

> Only Marketing Lists with Email fields can be connected.

If the Marketing list is suitable for the connection, "Connect to Dotmailer" button will appear on Marketing List view page.
One Marketing List may be connected only to one Dotmailer Address Book. OroCRM Marketing Lists are represented in Dotmailer as Address Book.

Before connect Marketing List to Dotmailer you should [create Address Book](https://support.dotmailer.com/entries/20663833-Creating-an-address-book) in Dotmailer User Interface.

When "Connect to Dotmailer" button is clicked, the following form will appear:

 - *Integration*: is a Dotmailer integration selector
 - *Address Book*: is a Dotmailer Address Book selector

After the connection has been saved, the Address Book will be scheduled for creation along with contacts synchronization job.

>Job Queue Daemon has to be running.

Marketing Lists connected to Dotmailer must contain the Email field. Connection settings of the lists are added as a Dotmailer action on their view pages.
Available options are "Connection Settings", "Disconnect" and "Synchronize".


## Dotmailer Campaign Creation

Marketing List contacts may be used to send Dotmailer campaigns. OroCRM Marketing list is mapped to Dotmailer Address Book.
Campaign statistics are collected in OroCRM ONLY when Campaign is sent to Dotmailer Address Book which connected to Marketing List.

You should create and send Campaign to ONE Address Book in Dotmailer before inported campaigns and contact activity are collected.


## Import Synchronization Logic

Import is performed with *oro:cron:integration:sync* cron command.

 - **Address Book**: All Dotmailer Address Books are imported except "All Contacts" and "Test". These Books were created for each Dotmailer Account by default.
 - **Campaign**: Only sent campaigns to Address book that has connection to OroCRM Marketing List are imported.

A new Email Campaign will be created in OroCRM for a Dotmailer Campaign and synchronized during the following imports.
The OroCRM Email Campaign related to Marketing List that has connection to Dotmailer Address Book.

 - **Contact**: All subscribed contacts of already imported Address Books are imported.
 - **Unsubscribed Contact**: All contacts who were suppressed from first import time. And All contacts who unsubscribed from already imported Addres Books.
 - **Contact Activity**: Contact activities are loaded for each contact who had activity for Campaigns that already were imported to OroCRM.
 - **Campaign Summary**: Campaign summary are imported for each Campaigns that already were imported to OroCRM.

Each contact activity is mapped to OroCRM Marketing List Item and Email Campaign Statics by Email.


## Export Logic

Export is performed with *oro:cron:dotmailer:export* cron command.

During Address Books export we have 4 steps

 - **Exporting Removed Contacts**: Try to remove from address book unsubscribed contacts from Marketing Lists that connected to Address Books
 - **Sync Marketing List Item State**: Added Marketing List Item States for contacts who unsubscribed from Address Books
 - **Preparing Contacts for Export**: Change export status for contacts who will exports to Dotmailer
 - **Exporting Contacts**: Send csv file with contacts who should exports to Dotmailer
