# Invoice import system

## Purposes
In order to satisfy the conditions of Amazon about VAT, all sellers need to upload invoices and credit notes in order to be downloadables through Amazon interfaces.
ChannelAdvisor helps doing the job by adding a functionnality of uploading documents and added to an order through their API 

## Technologies
php>=7.2.5, mysql5.7, symfony5, php cli

## Installation
```
cd path\toproject
composer install
```

You need to create a .env.local file and define mandatory parameters
```
SFTP_HOST_AWS="hostexample.server.transfer.eu-west-3.amazonaws.com"
SFTP_USERNAME_AWS="loginexample"
SFTP_KEY_AWS="%kernel.project_dir%/var/key/pathtokey.ppk"
CHANNEL_REFRESHTOKEN="channel_advisor_refresh_token"
CHANNEL_APPLICATIONID="channel_advisor_applicationid"
CHANNEL_SECRET="channel_advisor_secret"
DATABASE_URL="mysql://username:password@127.0.0.1:3306/databasename?serverVersion=5.7"
MAILER_DSN="gmail+smtp://nameUser:password@default"
APP_ENV=prod
```
Then clean the cache and we are good

```
php bin/console cache:clear
```

## Jobs

### Import invoices
An exterior cronjob is done everyday at 23:59, extracting PDF invoices from KPsport ERP to a files server on amazonaws.

The structure of the file servers is as following

```
|____credit_notes
|____ invoices
|____ details.csv
|____integrated
       |________12010023
       |         |________invoices
       |         |________credit_notes
       |________12010026
       |         |________invoices
       |         |________credit_notes
       |________2009934
       |         |________invoices
       |         |________credit_notes
       |________12010025
       |         |________invoices
       |         |________credit_notes
       |________12010024
                 |________invoices
                 |________credit_notes
```

Every file is named with the marketplace order number and according to the types is a putting in the good directory (credit_notes or invoices) 
At the root of the server, we get a details.csv. This file is built from  ERP 

```
document_no : ERP document number
external_order_id : marketplace ordernumber
ca_marketplace_id : channeladvisor profile
currency : currency symbol
total_amount : total amount without VAT
total_incVat : total amount with VAT
vat_amount : vat total 
document_type : document type
```

To launch the job 

```
php bin/console app:invoice-import
```

The job connects to AmazonAws via sftp.
It gets all datas from the details.csv files.
It browse the datas. If there is duplicated order numbers for an invoice, they won't be treated because it should not happen.
Then the script proceeds all the files from the directory invoices and credit_notes.
It get the datas form the details.csv file.
It search for the order on ChannelAdvisor with the ca_marketplace_id and external_order_id.
It upload the document throught the API sending file and metadata (amount, type, VAT amount)
It saves the record on the local database

At the end, it send a log rapport with errors, not found, metrics.

## Documentation ChannelAdvisor
[ChannelAdvisor](https://developer.channeladvisor.com/working-with-orders/channel-documents/)