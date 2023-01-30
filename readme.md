# Patxira

## User Documentation
[User documentation](docs/user.md)


## Technologies
php>=7.4, mysql8, symfony5.4, php cli, node 14, yarn

## Installation
```
cd path\toproject
composer install
yarn install
```
You need to create a .env.local file and define mandatory parameters.\
Then clean the cache and we are good

```
php bin/console cache:clear
```

## List of cron tasks implemented
[List of cron tasks and frequency](/docs/cron.txt)


## List of all commands and usage



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


## Errors

### Not found customer in Business central
Error of customer not found > check the thick box on customer card  Disable search by name to make billing address editable

### Stock integration


## External Documentation

### Aliexpress
[Api documentation Aliexpress](https://developers.aliexpress.com/en/doc.htm?docId=108970&docType=1)

#### Get credentials for Aliexpress
Go on your brower with the master account of aliexpress
And replace value by ALI_EXPRESS_ID get on [Console Aliexpress](https://console.aliexpress.com/app/app.htm?appId=10234288#/?appId=10234288&_k=h5u3py)\
In your browser follow this url replacing the XXXXX value https://oauth.aliexpress.com/authorize?response_type=code&client_id=XXXXXXX&redirect_uri=https://kps.green&state=1212&view=web&sp=ae\
In the html request get the code of auth and use it in the command to regenerate the token. Token is valid for one year.
With the code you get, generate a new token
```
php bin/console app:aliexpress-generate-code CODE
```
Place the token, in your .env.local

### Documentation Amazon webservices
[Selling Partner API](https://developer-docs.amazon.com/sp-api)

### Documentation Shopify
[Shopify](https://shopify.dev/api/admin-rest)

#### Get credentials for Shopify
Here the [instructions](https://help.shopify.com/en/manual/apps/custom-apps) to create a custom app and get infos

We need to provide the following informations :

scopes list : those are the autorizations you will provide to my token to get datas and write datas.
```read_analytics, read_assigned_fulfillment_orders, write_assigned_fulfillment_orders, write_customers, read_customers, write_draft_orders, read_draft_orders, write_files, read_files, write_fulfillments, read_fulfillments, read_gift_cards, write_inventory, read_inventory, read_locations, write_merchant_managed_fulfillment_orders, read_merchant_managed_fulfillment_orders, write_order_edits, read_order_edits, write_orders, read_orders, read_shopify_payments_accounts, read_shopify_payments_bank_accounts, read_shopify_payments_disputes, read_shopify_payments_payouts, read_products, write_products, read_discounts, write_discounts```\
token\
client API\
secret API\
version\
url

### Documentation ChannelAdvisor
[ChannelAdvisor](https://developer.channeladvisor.com/working-with-orders/channel-documents/)

### Documentation Business central APi
[Business central API](https://docs.microsoft.com/en-us/dynamics365/business-central/dev-itpro/api-reference/v1.0/)

The main entry points used :

[Sale invoice](https://docs.microsoft.com/en-us/dynamics365/business-central/dev-itpro/api-reference/v1.0/resources/dynamics_salesinvoice)

[Item](https://docs.microsoft.com/en-us/dynamics365/business-central/dev-itpro/api-reference/v1.0/resources/dynamics_item)

[Sale order](https://docs.microsoft.com/en-us/dynamics365/business-central/dev-itpro/api-reference/v1.0/resources/dynamics_salesorder)

[Company](https://docs.microsoft.com/en-us/dynamics365/business-central/dev-itpro/api-reference/v1.0/resources/dynamics_companies)

[Customer](https://docs.microsoft.com/en-us/dynamics365/business-central/dev-itpro/api-reference/v1.0/resources/dynamics_customer)

[Account](https://docs.microsoft.com/en-us/dynamics365/business-central/dev-itpro/api-reference/v1.0/resources/dynamics_account)

[PDF invoice](https://docs.microsoft.com/en-us/dynamics365/business-central/dev-itpro/api-reference/v1.0/api/dynamics_salesquote_pdfdocument)

