# KPS tools

## Purposes
With the migration from Navision to Business central, we need to replace the current integration of marketplace orders in Business central.\
The new system will use the current application as as middleware to connect both to marketplaces through their restful API and to BUsiness central using it Odata API.\
All requests will be done through http.\
A local database is setup to store all transactions and enabled workers to get an overview of all transactions and integrations.

A full picture of the process is visible on [this schema](docs/Processus_integration_D2C.jpg) 

 ![Full picture of the process](docs/Processus_integration_D2C.jpg "Integration proccess")



## Functionalities
### Order integration
This process will integrate in Business Central all the new orders shipped through the marketplace or all the orders that must be fulfilled by the seller.\
This task should be setup at least every 30 minutes. 

```
php bin/console app:integrate-orders-from CHANNEL --env=prod
```

For each sale channel, you will need to put this command. Channels are :

CHANNELADVISOR\
OWLETCARE\
ALIEXPRESS

This task will :
1. Connect to the marketplace
2. Retrieve all orders that need to be shipped by sellers or the one shiiped by FBA and to invoice
3. For each order, the process will be the following one. A log is filled up for each step and accesible through the web app.
    1. Transform the sale header to fit with the one to business central
    2. Define the company ERP in which integrate the sale order
    3. Do the correspondance between Marketplaces and Business central customers
    4. Store the order in a local database.
    5. For each order line, the process checks for a sku mapping in its own local database. If not, it will uses the marketplace one as SKU. If no product is found in BC, an error is thrown. And the order is marked as Error integration and state is stored. Process is stopped for this order.
    6. A total is done for each shipping part of an order. The shipping fees will then be included in a order line with G/L Account and account number.
    7. Integrate the order in Business central using the Business central API. If an error is encountred, the order is marked as Error integration and state is stored. Process is stopped for this order. 
    8. Change the order status locally as Integrated and stored the Business central Order number.
4. At the end of the process, if some errors were encountred, an email is sent to resume all errors and warn users and propose solutions.


### Order reintegration
This process will reintegrate in Business Central all the orders in error on the local database.\
This task should be setup at least once a day 

```
php bin/console app:integrate-orders-from CHANNEL 1 --env=prod
```
For each sale channel, you will need to put this command. Channels are :

CHANNELADVISOR\
OWLETCARE\
ALIEXPRESS


This task will :
1. Retrieve all local orders marked as In error of integration
2. For each order, the process of the integration will be the same as above.
3. At the end of the process, if some errors were encountred, an email is sent to resume all errors and warn users and propose solutions.



### Invoice and delivery notification
This order will check all the orders transformed in Business central in invoices and will upload the invoice pdf attached to the ERP document.
In the case of orders fulfilled by KPS, the service will push notifications of delivery and tracking code to the marketplace.


This task should be setup at least every 30 minutes. 

```
php bin/console app:integrate-invoices-from CHANNEL --env=prod
```
For each sale channel, you will need to put this command. Channels are :

CHANNELADVISOR\
OWLETCARE\
ALIEXPRESS

This task will :
1. Retrieve all orders marked as integrated in the local database
2. For each order, the process will be the following one. A log is filled up for each step and accesible through the web app.
    1. Check if the order in Business central is transformed and posted as a sale invoice. If not, process for this order is stopped
    2. Get from the BC api the content of pdf invoice.
    3. Do some treatme,ts relative to the sale channel\
    ChannelAdvisor : Upload the document to the ChannelAdvisor using the restful API.\
    Aliexpress : Check if delivery and put the tracking code\
    Owletcare : Check if delivery and put the tracking code
    4. Change the status of local database and mark it as invoice integrated and store the invoice number.
4. At the end of the process, if some errors were encountred, an email is sent to resume all errors and warn users and propose solutions.

A control is done regarding to the channel about the delay of treatment.

### Administration
The application come with an interface enabling to get 3 different tabs:
> **Orders**.
List of all orders stored in the local database. An user can filter by created date, status.\
Each order can be viewed in detail, with logs, errors, detail from Channel advisor and from Business central. Once order is post as invoice, an user can also downmload the invoice file.\
An user can also launch again the integration of an order in error or launch a batch of integration of orders on error.

> **Product correlations**.
List of all skus which need a correlation between the one used on ChannelAdvisor and the one stored in the ERP. An user can add, edit or delete product correlation.

> **User** 
List of all the users.
An user can add, edit or delete of user.

### Errors

#### Sku mapping
In the case, you received an error with SKU mapping error. the steps are :\
1. Find on Business central what is the real SKU
2. Connect to the application.
3. Go in the Sku mapping section
4. Add a new sku mapping. Put the sku used on marketplace and the one in Business central
5. Save.
6. Go to the order marked as Error of integration. 
7. Click on Retry.
8. The Weborder is integrated. If you don't do the steps 6 to 8, a process would do it twice a day for you.
[Check the video](https://www.loom.com/share/8f0cc5e142a947d8b66ffdf93eaf25d3)


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

## Technologies
php>=7.4, mysql8, symfony5.4, php cli

## Installation
```
cd path\toproject
composer install
```

You need to create a .env.local file and define mandatory parameters.\
Then clean the cache and we are good

```
php bin/console cache:clear
```

## List of cron tasks implemented
[List of cron tasks and frequency](/docs/cron.txt)

## Aliexpress
[Api documentation Aliexpress](https://developers.aliexpress.com/en/doc.htm?docId=108970&docType=1)

### Get credentials for Aliexpress
Go on your brower with the master account of aliexpress
And replace client_id value by ALI_EXPRESS_ID get on [Console Aliexpress](https://console.aliexpress.com/app/app.htm?appId=10234288#/?appId=10234288&_k=h5u3py)\
In your browser floow this url replacing the XXXXX value https://oauth.aliexpress.com/authorize?response_type=code&client_id=XXXXXXX&redirect_uri=https://aliexpress.gadgetiberia.es/es/module/aliexpress_official/auth?token=a1d930a3e4332d2c083978e8b5293b78&state=1212&view=web&sp=ae\
In the html request get the code of auth and use it in the command to regenerate the token. Token is valid for one year.
With the code you get, generate a new token
```
php bin/console app:aliexpress-generate-code CODE
```
Place the token, in your .env.local

## Documentation Amazon webservices
[Selling Partner API](https://developer-docs.amazon.com/sp-api)

## Documentation Shopify
[Shopify](https://shopify.dev/api/admin-rest)

### Get credentials for Shopify
Here the [instructions](https://help.shopify.com/en/manual/apps/custom-apps) to create a custom app and get infos

We need to provide the following informations :

scopes list : those are the autorizations you will provide to my token to get datas and write datas.
```read_analytics, read_assigned_fulfillment_orders, write_assigned_fulfillment_orders, write_customers, read_customers, write_draft_orders, read_draft_orders, write_files, read_files, write_fulfillments, read_fulfillments, read_gift_cards, write_inventory, read_inventory, read_locations, write_merchant_managed_fulfillment_orders, read_merchant_managed_fulfillment_orders, write_order_edits, read_order_edits, write_orders, read_orders, read_shopify_payments_accounts, read_shopify_payments_bank_accounts, read_shopify_payments_disputes, read_shopify_payments_payouts, read_products, write_products, read_discounts, write_discounts```\
token\
client API\
secret API\
version\
url

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

Error of customer not found > check the thick box on customer card  Disable search by name to make billing address editable