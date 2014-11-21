


# CloudConvert Laravel API


A Laravel wrapper for the CloudConvert API. See [https://cloudconvert.org](https://cloudconvert.org) for more details.

[![Build Status](https://travis-ci.org/robbiepaul/cloudconvert-laravel.svg?branch=v0.1)](https://travis-ci.org/robbiepaul/cloudconvert-laravel)
 

 
## Installation
 
Install this package through [Composer](https://getcomposer.org/). 

Add this to your `composer.json` dependencies:

```
"require": {
   "robbiep/cloudconvert-laravel": "1.*@dev"
}
```

Run `composer install` to download the required files.

Next you need to add the service provider to `config/app.php`

```
'providers' => array(
    ...
    'RobbieP\CloudConvertLaravel\CloudConvertLaravelServiceProvider'
)
```
One more step. 

You need to publish the config `php artisan config:publish robbiep/cloudconvert-laravel`

Just enter your API key in `config/packages/robbiep/cloudconvert-laravel/config.php` (you can get one for free at [https://cloudconvert.org](https://cloudconvert.org))


Now you can use CloudConvert in your application!

## Usage
There's many ways to use CloudConvert. I'll cover a few of them here, for all the converter options I suggest checking out the API docs.
 
### Simplest file conversion
```
CloudConvert::file('/a/path/to/file.mov')->to('mp4');
// This will convert the file to /a/path/to/file.mp4

CloudConvert::file('/a/path/to/file.mov')->to('/a/new/path/to/new.mp4');
// This will convert the file to /a/new/path/to/new.mp4

```

## Todo
* Write some more tests
* Enable multiple conversions using one process
* Refactor the commands
  
 
## Contributing
 
1. Fork it!
2. Create your feature branch: `git checkout -b my-new-feature`
3. Commit your changes: `git commit -am 'Add some feature'`
4. Push to the branch: `git push origin my-new-feature`
5. Submit a pull request :D
  
## Credits
 
Thanks to Lunaweb Ltd. for their API. Go check it out.
 
## Resources

* [API Documentation](https://cloudconvert.org/page/api)
* [Conversion Types](https://cloudconvert.org/formats)
* [CloudConvert Blog](https://cloudconvert.org/blog)