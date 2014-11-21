


# CloudConvert Laravel API


A Laravel wrapper for the CloudConvert API. See [https://cloudconvert.org](https://cloudconvert.org) for more details.

[![Build Status](https://travis-ci.org/robbiepaul/cloudconvert-laravel.svg?branch=v0.1)](https://travis-ci.org/robbiepaul/cloudconvert-laravel)
 

 
## Installation
 
Install this package through [Composer](https://getcomposer.org/). 

Add this to your `composer.json` dependencies:

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
    'RobbieP\CloudConvertLaravel\CloudConvertLaravelServiceProvider'
)
```
One more step. 

You need to publish the config `php artisan config:publish robbiep/cloudconvert-laravel`

Just enter your API key in `config/packages/robbiep/cloudconvert-laravel/config.php` 
> You can get your free API by just registering at [https://cloudconvert.org](https://cloudconvert.org)


Now you can use CloudConvert in your application!

## Usage
There's many ways to use CloudConvert. I'll cover a few of them here, for all the converter options I suggest checking out the API docs.
 
### File conversion
```php
# Convert the file to /a/path/to/file.mp4
CloudConvert::file('/a/path/to/file.mov')->to('mp4');

# Convert the file and save it in a different location /a/new/path/to/new.mp4
CloudConvert::file('/a/path/to/biggles.webm')->to('/a/new/path/to/new.mp4');

# Convert the image to kitty.jpg with quality of 70%
CloudConvert::file('kitty.png')->quality(70)->to('jpg');

# Convert a PowerPoint presentation to a set of images, let's say you only want slides 2 to 4
# This will save presentation-2.jpg, presentation-3.jpg and presentation-4.jpg
CloudConvert::file('presentation.ppt')->pageRange(2, 4)->to('jpg');
```
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

You can also chain multiple conversions on one process, like this:
```php
# Convert a TrueType font in to all the fonts you need for a cross browser web font pack
CloudConvert::file('claw.ttf')->to('eot')->to('otf')->to('woff')->to('svg');
```
It will also work with converting remote files (just make sure you provide a path to save it to)
```php
# Convert a TrueType font in to all the fonts you need for a cross browser web font pack
CloudConvert::file('claw.ttf')->to('eot')->to('otf')->to('woff')->to('svg');
```


### File conversion

## Todo
- [x] Release
- [ ] Write some more tests
- [x] Enable multiple conversions using one process
- [ ] Refactor the commands
  
 
## Contributing
 
1. Fork it
2. Create your feature branch: `git checkout -b my-new-feature`
3. Commit your changes: `git commit -am 'Add some feature'`
4. Push to the branch: `git push origin my-new-feature`
5. Submit a pull request 
  
## Credits
 
Thanks to [Lunaweb Ltd.](http://www.lunaweb.de/) for their API. Go [check it out](https://cloudconvert.org/page/api).
 
## Resources

* [API Documentation](https://cloudconvert.org/page/api)
* [Conversion Types](https://cloudconvert.org/formats)
* [CloudConvert Blog](https://cloudconvert.org/blog)
