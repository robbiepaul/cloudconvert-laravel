# CloudConvert Laravel API


A Laravel wrapper for the CloudConvert API. See [https://cloudconvert.com](https://cloudconvert.com) for more details.

[![Build Status](https://travis-ci.org/robbiepaul/cloudconvert-laravel.svg?branch=v0.1)](https://travis-ci.org/robbiepaul/cloudconvert-laravel) [![Latest Stable Version](https://poser.pugx.org/robbiep/cloudconvert-laravel/v/stable)](https://packagist.org/packages/robbiep/cloudconvert-laravel) [![Total Downloads](https://poser.pugx.org/robbiep/cloudconvert-laravel/downloads)](https://packagist.org/packages/robbiep/cloudconvert-laravel) [![Latest Unstable Version](https://poser.pugx.org/robbiep/cloudconvert-laravel/v/unstable)](https://packagist.org/packages/robbiep/cloudconvert-laravel) [![License](https://poser.pugx.org/robbiep/cloudconvert-laravel/license)](https://packagist.org/packages/robbiep/cloudconvert-laravel)
 

 
## Installation
 
Install this package through [Composer](https://getcomposer.org/). 

Add this to your `composer.json` dependencies:

### Using Laravel 5.0+

```js
"require": {
   "robbiep/cloudconvert-laravel": "2.*"
}
```

### Using Laravel ~4.2

```js
"require": {
   "robbiep/cloudconvert-laravel": "1.*@dev"
}
```

Run `composer install` to download the required files.

Next you need to add the service provider to `config/app.php`

```php
'providers' => array(
    ...
    RobbieP\CloudConvertLaravel\CloudConvertLaravelServiceProvider::class
)
```
One more step. 

You need to publish the config `php artisan vendor:publish`

Just enter your API key in `config/cloudconvert.php` 
> You can get your free API key by registering at [https://cloudconvert.com](https://cloudconvert.com)

Now you can use CloudConvert in your application!

## Usage
There's many ways to use CloudConvert. I'll cover a few of them here, for all the converter options I suggest checking out the API docs.
 
### File conversion
```php
# Convert the file to /a/path/to/file.mp4

CloudConvert::file('/a/path/to/file.mov')->to('mp4');
```

```php
# Convert the file and save it in a different location /a/new/path/to/new.mp4

CloudConvert::file('/a/path/to/biggles.webm')->to('/a/new/path/to/new.mp4');
```

```php
# It also works with Laravel's file upload

if (Input::hasFile('photo'))
{
    CloudConvert::file( Input::file('photo') )->to('/a/local/path/profile_image.jpg');
}
```

```php
# Convert the image to kitty.jpg with quality of 70%

CloudConvert::file('kitty.png')->quality(70)->to('jpg');

```

```php
# Convert a PowerPoint presentation to a set of images, let's say you only want slides 2 to 4
# This will save presentation-2.jpg, presentation-3.jpg and presentation-4.jpg

CloudConvert::file('presentation.ppt')->pageRange(2, 4)->to('jpg');
```

#### Dynamic file conversion
```php
# Dynamic PDF creation using DOCX/PPTX templates
# See this blog post for more details: https://cloudconvert.com/blog/dynamic-pdf-creation-using-docx-templates/

$variables = ['name' => 'John Doe', 'address' => 'Wall Street'];
CloudConvert::file('invoice_template.docx')->templating($variables)->to('invoice.pdf');
```

#### Converter options
There are many more conversion options. I've put shortcuts like the ones above for the most common. However you can pass through any options you like using the `withOptions` method, such as:

```php
# Convert the meow.wav to meow.mp3 with a frequecy of 44100 Hz and normalize the audio to +20dB

CloudConvert::file('meow.wav')->withOptions([
    'audio_frequency' => '44100', 
    'audio_normalize' => '+20dB'
])->to('mp3');


# Convert the fido_falls_over.mp4 to fido.gif but you only want 10 seconds of it, starting at 1:02

CloudConvert::file('fido_falls_over.mp4')->withOptions([
    'trim_from' => '62', 
    'trim_to' => '72'
])->to('fido.gif');

# Or the same with using the shortcuts:
CloudConvert::file('fido_falls_over.mp4')->trimFrom(62)->trimTo(72)->to('fido.gif');

```
#### Chaining multiple conversions
You can also chain multiple conversions on one process, like this:
```php
# Convert a TrueType font in to all the fonts you need for a cross browser web font pack

CloudConvert::file('claw.ttf')->to('eot', true)->to('otf', true)->to('woff', true)->to('svg');

# Or the same thing with an array
CloudConvert::file('claw.ttf')->to(['eot', 'otf', 'woff', 'svg']);
```
#### Remote files
It will also work with converting remote files (just make sure you provide a path to save it to)
```php
# Convert Google's SVG logo hosted on Wikipedia to a png on your server

CloudConvert::file('http://upload.wikimedia.org/wikipedia/commons/a/aa/Logo_Google_2013_Official.svg')
            ->to('images/google.png');
```

#### Merging PDFs
At the moment, merging only works with remotely hosted files, however in the future it will work with uploaded files and files from storage
```php
# Merge the PDFs in the array in to a single PDF

CloudConvert::merge([
             'https://cloudconvert.com/assets/d04a9878/testfiles/pdfexample1.pdf',                          
             'https://cloudconvert.com/assets/d04a9878/testfiles/pdfexample2.pdf'
            ])
            ->to('merged.pdf');
```

### Website screenshot
CloudConvert will also take a screenshot of a website and convert it to an image or pdf for you:
```php
# Take a screenshot with the default options: 1024px with with full height of webpage

CloudConvert::website('www.nyan.cat')->to('screenshots/nyan.jpg');
```

```php
# You can also specify the width and the height as converter options

CloudConvert::website('www.nyan.cat')
            ->withOptions([
                 'screen_width' => 1024,
                 'screen_height' => 700
            ])->to('screenshots/nyan.png');
```

### Converting to and from external storage options
At the moment CloudConvert let you use *FTP* or *Amazon S3* as storage options. However it looks like in the future they will add *Google Drive* and *Dropbox* to the API
> **Please note: **
> To use these storage options you will need to provide the configuration in the `config/cloudconvert.php` 

```php
# Lets say you have a PDF and you want to convert it to an ePub file and 
# store it on your Amazon S3 bucket (defined in your config). It's this simple:

CloudConvert::file('/a/local/path/garfield.pdf')->to(CloudConvert::S3('Garfield_converted.epub'));
```

```php
# You can also override the default options by providing them as an array as the second argument

CloudConvert::file('/a/local/path/garfield.pdf')
            ->to(CloudConvert::S3('Garfield_converted.epub', [
                'bucket'  => 'a-different-bucket',
                'acl'     => 'public-read',
                'region'  => 'us-east-1'
            ]));
```

```php
# Now you want to convert the file on your S3 to a txt file and store it on a server via FTP

CloudConvert::file(CloudConvert::S3('Garfield_converted.epub'))
            ->to(CloudConvert::FTP('path/to/garfield.txt'));
```
It's that simple. The storage options `CloudConvert::S3($path)` and `CloudConvert::FTP($path)` can be used for both input files and output files.

### Non-blocking conversion using a callback URL
When the conversion might take a long time you could use:
```php
# Script: sendConversion
CloudConvert::file('/a/path/to/file.mov')
            ->callback('http://myserver.com/save_file.php')
            ->convert('mp4');
            

# Script: saveFile
CloudConvert::useProcess($_REQUEST['url'])
            ->save('/path/converted.mp4');
```

### Non-blocking conversion using a queue
To use queues you will need have set-up either beanstalk or iron in your `config/queue.php`
```php
# The queue will check every second if the conversion has finished. 
# It times out after 120 seconds (configurable).

CloudConvert::file('/a/path/to/file.mov')->queue('to', '/a/path/to/file.mp4')
```

### Conversion types
You can view the conversion types using the `conversionTypes()` method. It always returns `Illuminate\Support\Collection`.
```php
# To get all possible types

$types = CloudConvert::conversionTypes();
```

```php
# To get all possible types in a specific group

$types = CloudConvert::conversionTypes('video');
```

```php
# To get all possible output formats if you know the input format

$types = CloudConvert::input('pdf')->conversionTypes();
```

```php
# Same if you know the output format and want to see what can be inputted

$types = CloudConvert::output('jpg')->conversionTypes();
```

### Processes status
You may want to list all your processes, running, finished and failed. It always returns a `Illuminate\Support\Collection`.
```php
# To get all possible types
$processes = CloudConvert::processes();

# To delete a process by ID
CloudConvert::deleteProcess($process_id);
```

## Artisan commands
If you want to do quick conversions or calls to the API from your console, you can use the following commands:

#### Convert a file
```bash
# Options: --opions, --background, --storage, --path
php artisan cloudconvert:convert video.mov mp4
php artisan cloudconvert:convert /path/to/video.mov converted.mp4 --storage='s3'
```

#### Website screenshot
```bash
# Options: --opions, --storage, --path
php artisan cloudconvert:website www.laravel.com jpg
```

#### Processes list
```bash
# Options: --delete (used with process_id)
# Argument: process_id (optional) - will show the status of that process
php artisan cloudconvert:processes <process_id>
```

#### Conversion types
```bash
# Options: --input, --output 
# (both optional - however if you included both you will see all the 
# possible converter options for those types, not just the default ones)
php artisan cloudconvert:types
php artisan cloudconvert:types --input='nice.pdf'
php artisan cloudconvert:types --input='doc' --output='jpg'
```
## Using this package without Laravel
You still need to use composer. Type `composer require robbiep/cloudconvert-laravel` to download the files, then you can use the package like this:

```php
require_once('vendor/autoload.php');

$cloudConvert = new RobbieP\CloudConvertLaravel\CloudConvert(['api_key' => 'API_KEY_HERE']);

$cloudConvert->file('randomuser.jpg')->to('png');
```

## Todo
- [x] Release
- [ ] Write some more tests
- [x] Enable merging of multiple files
- [x] Enable multiple conversions using one process
- [ ] Refactor the commands
- [x] Added support for Guzzle ~6.0
- [ ] Readme file is getting long, convert to wiki
 
## Contributing
 
1. Fork it
2. Create your feature branch: `git checkout -b my-new-feature`
3. Commit your changes: `git commit -am 'Add some feature'`
4. Push to the branch: `git push origin my-new-feature`
5. Submit a pull request 
  
## Credits
 
Thanks to [Lunaweb Ltd.](http://www.lunaweb.de/) for their API. Go [check it out](https://cloudconvert.com/page/api).
 
## Resources

* [API Documentation](https://cloudconvert.com/page/api)
* [Conversion Types](https://cloudconvert.com/formats)
* [CloudConvert Blog](https://cloudconvert.com/blog)
