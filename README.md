# Example REST and SOAP service

Example for using REST and SOAP service in Symfony 4

## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes. See deployment for notes on how to deploy the project on a live system.

### Installing

Run composer install

```
composer install
```

Set parameters for database connection and run schema update command afterwards
```
php bin/console doctrine:schema:update --force
```


You need to create a default user that will have API access, you can do this with FOS command
```
php bin/console fos:user:create sometestapiuser@example.com
```

For test purposes, use same username as email and you need to set those in .env file

```
SR_APIUSERNAME=sometestapiuser@example.com
SR_APIPASSWORD=SomeTestApiPassword
```

Set the rest of variables in .env file

```
SP_BASEURI=http://127.0.0.1
SP_SOAPURI=/soap?wsdl
SP_CUSTOMEREMAIL=sometestcustomeruser@example.com
SP_CUSTOMERPASSWORD=SomeTestCustomerPassword
SP_CUSTOMERFIRSTNAME=John
SP_CUSTOMERLASTNAME=Doe
SP_CUSTOMERSTREET='Some street'
SP_CUSTOMERCOUNTRY='Some country'
SP_ORDERAMOUNT=100
SP_SHIPPINGAMOUNT=200
SP_TAXAMOUNT=300
```


In public/soap.wsdl file you need also to change soap address according on your values for SP_BASEURI and
SP_SOAPURI, without ?wsdl. So for example above change to this:

```
<service name="soapwsdl">
    <port name="soapwsdlPort" binding="tns:soapwsdlBinding">
        <soap:address location="http://127.0.0.1/soap"/>
    </port>
</service>
```

## Run bot

You can run bot command to make all requests and show results

```
php bin/console app:run-bot
```

The command will show all results in console