# Patxira

## Purposes
The app aims to be a middleware between some of online sale channels providing IT solutions to solve syncronization and integration problematics.

### Order synchronisation
The main task executed by the app is the integration in Business central.
Each sale order belongs to a sale channel and through a integration channel (Channeladvisor, Aliexpress, ...). 
Sale order will be integrated and regarding the carrier solution (3PL or own managed) wil be posted as invoice or as shipment.
In case of a own managed shipment, tracking will be send through sale channel.
Invoice will be sent automatically by email or through the sale channel.

A full picture of the process is visible on [this schema](img/Processus_integration_D2C.jpg) 

 ![Full picture of the process](img/Processus_integration_D2C.jpg "Integration proccess")

### Stock synchronization
The app connects hourly to Business cental to get the level of stocks (La Roca, Madrid) of every SKU and send available level of stock to every sale channel. 
Some rules are added to add some buffer and avoid syncronisation error.
For the warehouse in La Roca, if the level is greater than 5, the available of stock is calculated as 70% of the real available stock. If the real stock is less than 5, the stoc k is considered equal to 0.
For the warehouse in Madrid, real stock is sent.

### FBA and Amazon reporting
The app connects to different reports provided by Amazon and uses the content to provide some helpers to manage business.

- stock level on FBA and alert regarding skus that need to be sent to FBA
- returns and reimbursements of FBA
- financial events linked to FBA transactions 
- export datas to Power BI to provide datas to consume

### Pricing
The app can manage prices and promotions prices to the different setup sale channels. The sale channel is a combination of a marketplace and a company, then Amazon.es will be involved in 2 companies that means there will be 2 sale channels for each company.
For a sku given, users can manage if a product price is enabled on a sale channel and then define is price.
The promotion system will help users to manage finely promotion prices.

## Usage

### Login
App is acccesible through web [https://marketplace.kps-group.com](https://marketplace.kps-group.com) 

![Login](img/login.png "Login")

You need to provide your email as username and the password that have been provided.

### Interface

![Interface](img/interface.png "Interface")

Interface has common functionality through all screens
1. Menu and submenus : Regarding your roles you will not access to every section of the application
2. Search bar. Fill up and tape enter on your keyboard. Search only in the section you are in.
3. Logout button
4. Filtering and general actions. This zone provide all general actions for a list. (ex : filter, add new item,export in csv, batch delete, ...).  Regarding your roles you will not access to every action
5. Sorting. Only column with this sign provide sort asc or desc functionality
6. Individual action.  (ex : detail, edit, delete, ...). Regarding your roles you will not access to every action.


### Orders

![Order](img/order-page.png "Order page")

1. List of all sale channels
2. Common action as See online the sale order (Amazon, Aliexpress, ...), download the pdf invoice form business central, ...
3. Display all the elements retrieved from sale channel regarding the order + status of the integration in the information system
4. Display a chronology of the integration of the order
5. Display all the elements retrieved from Business central, especially the VAT rates or shipping rates.
6. For Amazon only, we retrieve all the financial datas associated with this transaction (order,r eturn, cashback, fee or shipping)


### Pricing

#### Prices


#### Promotions


##### Import


#### Sale Channel



### Config
#### Sku mappings

![Sku](img/Sku-Mappings.png "Sku mappings")

For some reasons, the skus created in the sale channel is not corresponding to any sku in BUsiness central.
THis interface enables operators to add, edit or delete sku mappings between a sku used in a sale channel and the sku used in BUsiness central.

#### Users

![User](img/Create-User.png "User creation")

Define the role the user will have in the application
- Email is required and unique
- New password and repeat password should be provide at creation and when you wnat to update it
- Channel alerts. Select the list of the sale channels for which the user will receive alerts by emails
- Manage FBA. Enable to get all Amazon & FBA section
- Manage price. Enable to add prices and manage promotions.
- Sale channels. List of sale channels for which the user will be able to manage price and promotions
- Manage users. The user can create new users.

## Errors
During syncronisation, some alerts can be sent by email to advice operators about the operations they should operate to 

### Sku mapping
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

### Address length
If you received messages containing those information *The BC sale order WPV21-01103 corresponding to the weborder 8155312123395027 has been created with an address length of the street over 40 characters. Please modify it on Business central*
The DHL connector has a restriction of 40 characters for the address section. A script try to reduce the address at the maximum but it happens that the limit is exceeded. 
In this case : 
1. Find on Business central the sale order or the sale shipment if it is already created
2. Modify the delivery address to suit the limitation of 40 characters, keeping the essential information.

### Delay of treatment
If you received messages containing those information *Order AliExpress n°3017869929123012 (#53100) > Shipping should be processed in 30 hours for Order AliExpress n°3017869929123012 (#53100)*
Some businesss rules have been added to check the integration process. 
> Every sale order managed by 3PL should be invoiced in less than 24 hours

> Every sale order managed by our own warehouse should be processed in the 30 hours after the purchase.

In this case, you need to analyse why the sale order was not processed.
Reasons can be multiple (stock, tracking, problem of posting orders).
In this case : 
1. Find on Business central the sale order and check if it is released
2. Check the availability of the stock of all the lines
3. Check if the shipment has been done
4. Check if the carrier has the tracking information

### Delay of tracking
If you received messages containing those information *Order AliExpress n°3018294824139882 (#54539) has been sent with the invoice GFV22/0900124 but no tracking is retrieved. Please confirm tracking on ALIEXPRESS*
The invoice is created in Business central and shipment also has been created. But no tracking code is available. Normally, in the process, we get the tracking code in the next 12 hours after the creation of the invoice.

In this case, you should investigate if there is some issues with tracking or with warehouse to resolve the issue.
In the same time, you should add the tracking code on the marketplace of you get it to avoid any penalities.
