# GenjThumbnailBundle

Features:

* On-request thumbnail generation
* Image is cached on disk - subsequent requests will serve the file directly from disk instead of hitting the framework
* Configurable thumbnail formats supported
* A thumbnail format can have multiple filters
* Filenames based on slug (SEO friendly)
* Uploads and thumbnails are stored in subdirectories based on the object ID, to prevent too many files in 1 directory
* Generated URLs will point to http://static.yoursite
* Can generate thumbnail of any object property or even static assets


## Requirements

* VichUploaderBundle - https://packagist.org/packages/vich/uploader-bundle
* LiipImagineBundle - https://packagist.org/packages/liip/imagine-bundle


### Optional

* GenjFrontendUrlBundle - https://github.com/genj/GenjFrontendUrlBundle


# Installation

* Add to composer.json (dev-master is neccesary here, unfortunately): "liip/imagine-bundle": "dev-master" and run ```composer update```

* Register VichUploaderBundle, LiipImagineBundle and GenjThumbnailBundle in AppKernel.php:


    new Vich\UploaderBundle\VichUploaderBundle(),
    new Liip\ImagineBundle\LiipImagineBundle(),
    new Genj\ThumbnailBundle\GenjThumbnailBundle(),


* Add this to app/config/parameters.yml:


    domain: holland-herald.com


* Add this to app/config/config.yml:


    imports:
        - { resource: thumbnailing.yml }

    framework:
        ...
        session:
            ...
            cookie_domain: "%domain%"


* Create file app/config/thumbnailing.yml with this content:


```
    liip_imagine:   
        driver: imagick
        filter_sets:

            # used in admin and in listings
            teaser:
                quality: 90
                filters:
                    upscale: { min: [320, 320] }
                    thumbnail: { size: [320, 320] }
                    format: ['jpg']

            # main image - not over full size
            detail:
                quality: 90
                filters:
                    relative_resize: { widen: 1000 }
                    format: ['jpg']

            # full size
            big:
                quality: 90
                filters:
                    relative_resize: { widen: 1600 }
                    format: ['jpg']

```


* Add in routing_hh.yml AND routing_admin.yml:

```
    genj_thumbnail:
        pattern: /thumbnails/{bundleName}/{entityName}/{attribute}/{filter}/{idShard}/{slug}-{id}.{_format}
        host: '{subdomain}.{domain}'
        defaults:
            _controller: liip_imagine.controller:filterActionForObject
            subdomain: 'static'
            domain: '%domain%'
            attribute: 'fileUpload'
        requirements:
            _format: jpg|jpeg|gif|png
            slug: '[a-zA-Z0-9\-\/]+'
            subdomain: 'static|upload'
            domain: '[a-zA-Z0-9\.\-\/]+'
            idShard: '[\w/]+'
            id: '^\d+$'
```


OR if you don't feel the need to adjust the original routing:

    genj_thumbnail_bundle:
        resource: "@GenjThumbnailBundle/Resources/config/routing.yml"



# Usage

To generate an URL to a thumbnail:

```
<img src="{{ genj_thumbnail(object, 'fileUpload', 'teaser'}) }}">
```

To grab image info (width and height):

```
{% set image_src  = genj_thumbnail(object, 'fileUpload', 'teaser'}) %}
{% set image_info = image_src|genj_thumbnail_info %}

<img src="{{ genj_thumbnail(object, 'fileUpload', 'teaser'}) }}" width="{{ image_info.width }}" height="{{ image_info.height }}">
```



# Notes

Make sure you limit the length of your slug field, because browser url may be limited.
