# Laravel-epay API

Laravel wrapper for the Epay.bg API.
Working with laravel 5.1

## Install

Via Composer

``` bash
$ composer require angelbachev/epay

```

* Add the service provider to your $providers array in config/app.php file like:

```
AngelBachev\Epay\EpayServiceProvider::class
```

* Add the alias to your $aliases array in config/app.php file like:

```
'Epay' => AngelBachev\Epay\Facades\Epay::class
```

* Run one of the following commands to publish package configuration depending on your needs:
    * To publish only **angelbachev/epay** package configuration:
    ```
    php artisan vendor:publish --provider="AngelBachev\Epay\EpayServiceProvider"
    ```
    * To publish configuration of all packages you use:
    ```
    php artisan vendor:publish --tag="config"
    ```
    * To publish all assets of all packages:
    ```
    php artisan vendor:publish
    ```
* Add the following lines to your .env file
```
# Epay configuration values
EPAY.mode=stage   # if you want to make real payments set this to prod
# Settings for testing purposes
EPAY.stage.client_id=   #Add your Customer number
EPAY.stage.secret=      #Add your Secret key
EPAY.stage.success_url= #URL where you want the customer to be redirected after confirming payment
EPAY.stage.cancel_url=  #URL where you want the customer to be redirected if he rejects the payment
# Production settings
EPAY.prod.client_id=    #Add your Customer number
EPAY.prod.secret=       #Add your Secret key
EPAY.prod.success_url=  #URL where you want the customer to be redirected after confirming payment
EPAY.stage.cancel_url=  #URL where you want the customer to be redirected if he rejects the payment
```
## Usage

``` php

    $invoice     = mt_rand(1, 1000000);
    $amount      = 150.63;
    $expiration  = '01.03.2016 08:30:00';
    $description = 'Invoice Description';

    Epay::setData(
        $invoice,     // accepts only positive integer values
        $amount,      // accepts only positive integers and float numbers with 1 or 2 digits after decimal point
        $expiration,  // accepts time in format DD.MM.YYYY[ hh:mm[:ss]]
        $description, // max length 100 symbols
        [$currency],  // optional, accepts only "BGN", "USD", "EUR" ("BGN" by default)
        [$encoding]   // optional, accepts only "utf-8"
    );

```

### Notification receive route (POST)

``` PHP
    Route::post('receive', function() { # replace 'receive' with your real route for handling Epay notifications

        $receiver = Epay::receiveNotification(Input::all());

        /**
        * Update order or status of payment
        *
        *    array (
        *      'invoice' => '1500',
        *      'status' => 'PAID',
        *      'pay_date' => '20160221143730',
        *      'stan' => '036257',
        *      'bcode' => '036257',
        *    ),
        *
        **/
        // Do something with the response
        foreach($receiver['items'] as $item) {
            Log::info($item);
            Log::info($item['status']);
            Log::info($item['invoice']);
        }

        return $receiver['response'];
    });
```


### Form in view
```
    <form action="{{ Epay::getSubmitUrl() }}" method="post">
        {!! Epay::generateHiddenInputs() !!}

        // your code here

        <button type=submit>Send</button>
    </form>
```

## Support
This package only supports Laravel 5 & Laravel 5.1 & 5.2 at the moment.

* In case of any issues, kindly create one on the Issues section.
* If you would like to contribute:
  * Fork this repository.
  * Implement your features.
  * Generate pull request.

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Security

If you discover any security related issues, please email angelbachev@gmail.com instead of using the issue tracker.

## Credits

- [epay.bg demo packages][https://demo.epay.bg/]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

https://packagist.org/packages/angelbachev/epay
