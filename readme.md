# Channel tools

## Purposes
With the migration from Navision to Business central, we need to replace the current integration of marketplace orders in Business central. 
The new system will use the current application as as middleware to connect both to ChannelAdvisor through their restful API and to BUsiness central using it Odata API.
All requests will be done through http.
A local database is steup to store all transactions and enabled workers to get an overview of all transactions and integrations.
Moreover some setting will be accessible to ensure some 

## Technologies
php>=7.4, mysql8, symfony5, php cli

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
BC_URL="http://BCurl.cloudapp.azure.com:BCPORT/BCFOLDER"
BC_LOGIN="APIUSER"
BC_PASSWORD="APIPASSWORD"
```
Then clean the cache and we are good

```
php bin/console cache:clear
```

## Functionalities
### Order integration
This process will integrate in Business Central all the new orders shipped through the marketplace.
This task should be setup at least every 30 minutes. 

```
php bin/console app:integrate-orders-from-channel --env=prod
```

This task will :
1. Connect to ChannelAdvisor
2. Retrieve all orders shipped and not marked as exported
3. For each order, the process will be the following one. A log is filled up for each step and accesible through the web app.
    1. Transform the sale header to fit with the one to business central
    2. Do the correspondance between Amazon marketplaces and Business central customers
    3. Store the order in a local database.
    4. Mark on ChannelAdvisor the order as exported
    5. For each order line, the process checks for a product correlation in its own local database. If not, it will uses the Channeladvisor as SKU. If no product is found in BC, an error is thrown. And the order is marked as Error integration and state is stored. Process is stopped for this order.
    6. A total is done for each shipping part of an order. The shipping fees will then be included in a order line with G/L Account and account number.
    7. Integrate the order in Business central using the Business central API. If an error is encountred, the order is marked as Error integration and state is stored. Process is stopped for this order.
    8. Change the order status locally as Integrated and stored the Business central Order number.
4. At the end of the process, if some errors were encountred, an email is sent to resume all errors and warn users and propose solutions.


### Order reintegration
This process will reintegrate in Business Central all the orders in error on the local database.
This task should be setup at least once a day 

```
php bin/console reintegrate-orders-from-channel --env=prod
```

This task will :
1. Retrieve all local orders marked as In erro of integration
2. For each order, the process of the integration will be the same as above.
3. At the end of the process, if some errors were encountred, an email is sent to resume all errors and warn users and propose solutions.


### Invoice upload
This order will check all the orders transformed in Business central in invoices and will upload the invoice pdf attached to the ERP document.
This task should be setup at least every 30 minutes. 

```
php bin/console app:send-invoices-to-channeladvisor --env=prod
```

This task will :
1. Retrieve all orders marked as integrated in the local database
2. For each order, the process will be the following one. A log is filled up for each step and accesible through the web app.
    1. Check if the order in Business central is transformed and posted as a sale invoice. If not, process for this order is stopped
    2. Get from the BC api the content of pdf invoice.
    3. Upload the document to the ChannelAdvisor using the restful API.
    4. Change the status of local database and mark it as invoice integrated and store the invoice number.
4. At the end of the process, if some errors were encountred, an email is sent to resume all errors and warn users and propose solutions.

### Administration
The application come with an interface enabling to get 3 different tabs:
> **Orders**.
List of all orders stored in the local database. An user can filter by created date, status.
Each order can be viewed in detail, with logs, errors, detail from Channel advisor and from Business central. Once order is post as invoice, an user can also downmload the invoice file.
An user can also launch again the integration of an order in error or launch a batch of integration of orders on error.

> **Product correlations**.
List of all skus which need a correlation between the one used on ChannelAdvisor and the one stored in the ERP. An user can add, edit or delete product correlation.

> **User** 
List of all the users.
An user can add, edit or delete of user.


### Import invoices [deprecated]
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

## Documentation Business central APi
[Business central API](https://docs.microsoft.com/en-us/dynamics365/business-central/dev-itpro/api-reference/v1.0/)

The main entry points used :

[Sale invoice](https://docs.microsoft.com/en-us/dynamics365/business-central/dev-itpro/api-reference/v1.0/resources/dynamics_salesinvoice)

[Item](https://docs.microsoft.com/en-us/dynamics365/business-central/dev-itpro/api-reference/v1.0/resources/dynamics_item)

[Sale order](https://docs.microsoft.com/en-us/dynamics365/business-central/dev-itpro/api-reference/v1.0/resources/dynamics_salesorder)

[Company](https://docs.microsoft.com/en-us/dynamics365/business-central/dev-itpro/api-reference/v1.0/resources/dynamics_companies)

[Customer](https://docs.microsoft.com/en-us/dynamics365/business-central/dev-itpro/api-reference/v1.0/resources/dynamics_customer)

[Account](https://docs.microsoft.com/en-us/dynamics365/business-central/dev-itpro/api-reference/v1.0/resources/dynamics_account)

[PDF invoice](https://docs.microsoft.com/en-us/dynamics365/business-central/dev-itpro/api-reference/v1.0/api/dynamics_salesquote_pdfdocument)