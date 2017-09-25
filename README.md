# GenjThumbnailBundle

Features:

* On-request thumbnail generation
* Image is cached on disk - subsequent requests will serve the file directly from disk instead of hitting the framework
* Configurable thumbnail formats supported
* A thumbnail format can have multiple filters
* Filenames based on slug (SEO friendly)
* Images can be served over a separate subdomain e.g. for loadbalancing or S3 usage. Conventionally we use 'static', but any name is possible.
* Uploads and thumbnails are stored in subdirectories based on the object ID, to prevent too many files in 1 directory
* Can generate thumbnail of any object property or even static assets

* CDN using AWS-S3 (optional)
    * Thumbnails are copied on AWS-S3, browser fetches thumbnails from the S3 CDN.
    * A 404 on S3 will redirect the browser fall back to the webserver which will then create the file and upload it to S3.

 

## Requirements

* VichUploaderBundle - https://packagist.org/packages/vich/uploader-bundle
* LiipImagineBundle - https://packagist.org/packages/liip/imagine-bundle
* LeagueFlysystem-aws-s3-v3 - https://packagist.org/packages/league/flysystem-aws-s3-v3 (for AWS-S3 only)
* OneUpFlysystem - https://packagist.org/packages/oneup/flysystem-bundle (for AWS-S3 only)


### Optional

* GenjFrontendUrlBundle - https://github.com/genj/GenjFrontendUrlBundle


# Installation

* Add the bundle to your project composer.json 

    ```
    "require": {
    [..]
        "genj/thumbnail-bundle": "dev-master",
    [..]
    }
    ```

    and run ```composer update```

* Register VichUploaderBundle, LiipImagineBundle, FlySystemBundle and GenjThumbnailBundle in AppKernel.php:

    ```
    new Vich\UploaderBundle\VichUploaderBundle(),
    new Liip\ImagineBundle\LiipImagineBundle(),
    new Genj\ThumbnailBundle\GenjThumbnailBundle(),
    new Oneup\FlysystemBundle\OneupFlysystemBundle(),
    ```

* Create a `domain` parameter in app/config/parameters.yml and fill it with the domain of the website without subdomain.

    ```
    domain: <SITE_DOMAIN.com>  # (without subdomain, .com is just an example)
    ```


* Add this to app/config/config.yml:

    ```
    imports:
        - { resource: thumbnailing.yml }
        - { resource: cdn.yml }

    framework:
        ...
        session:
            ...
            cookie_domain: "%domain%"
    ```

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

    For more examples of what can be configured here, see the READ.ME of the liib imagine bundle at https://github.com/liip/LiipImagineBundle/blob/1.0/README.md 


* Add configuration

    /app/config/config.yml
    ```
    liip_imagine:
        resolvers:
            default:
                web_path: ~
    
        filter_sets:
            cache: ~
    
        loaders:
            default:
                filesystem:
                    data_root: '%data_root%'
    ```

    The `data_root` parameters should point to your document root.

    If you wish to use a CDN then complete this section and follow the instructions in the **setup AWS-S3** or **setup Cloudflare** section below.


* enable route in routing.yml:

    ```
    genj_thumbnail:
        path:  /thumbnails/{bundleName}/{entityName}/{attribute}/{filter}/{idShard}/{slug}-{id}.{_format}
        host: '%domain%'
        defaults:
            _controller: liip_imagine.controller:filterActionForObject
            subdomain: 'static'
            attribute: 'fileUpload'
        requirements:
            _format: jpg|jpeg|gif|png
            slug: "[a-zA-Z0-9\\-\\/]+"
            idShard: "[\\w/]+"
            id: "^\\d+$"
    ```

    OR if you don't feel the need to adjust the original routing:

    ```
    genj_thumbnail_bundle:
        resource: "@GenjThumbnailBundle/Resources/config/routing.yml"
    ```

    Note: if you want to use the CDN, the routing is different, see the **setup AWS-S3** or the **setup Cloudflare** section below.


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



# Setup AWS-S3 (optional)

*This is only required if the site will use S3 as a CDN to store thumbnails.*

* If no bucket is available yet, create and configure one. See the section **Create and configure a bucket on AWS-S3** below.

* add additional external vendors in your composer.json
    ```
    "require": {
    [..]
        "genj/thumbnail-bundle": "dev-master",
        "league/flysystem-aws-s3-v3": "^1.0",
        "oneup/flysystem-bundle": "^1.7",
    [..]
    }
    ```

    and run ```composer update```

* update your app/config/config.yml for the CDN usage
    
    ```
    liip_imagine:
        cache: withCdn
        resolvers:
            default:
                web_path: ~
            withCdn:
                localAndCdnResolver:
                    use_cdn: true
                    cdn: oneup_flysystem.thumbnails_filesystem
        filter_sets:
            cache: ~
        loaders:
            default:
                filesystem:
                    data_root: '%data_root%'
    
    services:
        aws.s3_client:
            class: Aws\S3\S3Client
            arguments:
              aws_s3_client:
                  version: latest
                  region: <AWS_REGION>
                  credentials:
                      key: <AWS_KEY>
                      secret: <AWS_SECRET>
    
    oneup_flysystem:
        adapters:
            staticcdn_adapter:
                awss3v3:
                    client: aws.s3_client
                    bucket: <BUCKET_NAME>
                    prefix: ~
        filesystems:
            thumbnails:
                adapter: staticcdn_adapter
    
    ```
    
    Replace the following placeholders with the actual data:
    
        <REGION> The `region` that the bucket was created in, for ex. `eu-west-1`
        <BUCKET_NAME> The name of the bucket to use.
        <AWS_KEY> The `access key` that AWS assigned to the bucket.
        <AWS_SECRET> The `secret key` that AWS assigned to the bucket.
        
    The `%domain%` parameter does not have to be filled in, it is taken from `parameters.yml`.


* Define a value for `cdn_domain` in `parameters.yml` and compose it as follows:
    
    ```
    <BUCKET_NAME>.s3-website.<REGION>.amazonaws.com.
    ```

    for example:
    
    ```
    cdn_domain: static.glamour.nl.s3-website.eu-west-1.amazonaws.com.
    ```

    Make sure that the bucket-name and region are identical to the content of your config.yml.

* Modify routing to use %cdn_domain% instead of %domain%.

    ```
    genj_thumbnail:
        path:  /thumbnails/{bundleName}/{entityName}/{attribute}/{filter}/{idShard}/{slug}-{id}.{_format}
        host: '{domain}'
        defaults:
            _controller: liip_imagine.controller:filterActionForObject
            subdomain: 'static'
            domain: '%cdn_domain%'
            attribute: 'fileUpload'
        requirements:
            _format: jpg|jpeg|gif|png
            slug: "[a-zA-Z0-9\\-\\/]+"
            idShard: "[\\w/]+"
            id: "^\\d+$"
            domain: ".*"
    ```

## Create and configure a bucket on AWS-S3

* Log into the AWS administation console at https://aws.amazon.com/console/

* Create a bucket.

    Use a name like `static.<DOMAIN>.com` that clearly defines the website that the bucket is used for.
    The thumbnail bundle will create directories inside this bucket as needed.


* Set bucket policy

    Instruct S3 to allow anonymous read-only access to the objects in the bucket.
    For more information about how to set this up, see the S3 documentation at http://docs.aws.amazon.com/AmazonS3/latest/dev/s3-access-control.html
    
    Replace `<BUCKET_NAME>` with the actual name of the bucket.
    
    ```
    {
        "Version": "2012-10-17",
        "Statement": [
            {
                "Sid": "PublicReadForGetBucketObjects",
                "Effect": "Allow",
                "Principal": "*",
                "Action": "s3:GetObject",
                "Resource": "arn:aws:s3:::<BUCKET_NAME>/*" 
            }
        ]
    }
    ```

* Enable website hosting (static hosting)
    
    http://docs.aws.amazon.com/AmazonS3/latest/dev/WebsiteHosting.html

* Upload an index.html and error.html (optional)
    
    These pages are displayed when users navigate to a folder instead of an image (index.html) and when there was an error accessing the requested object (error.html)
    Users should never see these files but it is good practice to put a userfriendly (branded) message there, explaining what has happened and why they are not seeing what the where expecting.
    
    If you upload these documents, make sure to place them in the root of the bucket, make them readable by `everyone`, and put their filenames in the static hosting section of bucket configuration.
     
* Set up the redirection rule.
    
    When S3 cannot find the requested image, this rule will redirect the browser to the webserver which will then serve the image and upload it to S3 so the next user will not get a 404 anymore.
    Don't forget to fill in the correct value for `Hostname`, replace `UPLOAD_DOMAIN` with the actual name of hte host that supplies the images.
    
    By convention this should take the form of `upload.<DOMAIN>.com`.
    
    ```
    <RoutingRules>
        <RoutingRule>
            <Condition>
                <HttpErrorCodeReturnedEquals>404</HttpErrorCodeReturnedEquals>
            </Condition>
            <Redirect>
                <HostName>UPLOAD_DOMAIN</HostName>
                <HttpRedirectCode>302</HttpRedirectCode>
            </Redirect>
        </RoutingRule>
    </RoutingRules>
    ```

## Testing the AWS-S3 CDN

To test if the setup is working, you should do the following with an image that is accessed by the website using the twig thumbnail filter.

1. After uploading an image in the website and seeing it appear on the site, that image path should be available in the AWS-S3 bucket aswell.
(images are not uploaded to S3 until they are requested from the site)

2. Deleting the image should make the image disappear from the S3 bucket.
¡
3. Uploading a new image over an existing image should delete the old image on S3, after which requesting it from the site should add a copy of the new image.

4. Images uploaded to the S3 bucket should be publicly available because of the defined policy.

5. Accessing an image on S3 that does not exist should cause S3 to redirect back to the website.

# Setup Cloudflare (optional)

The bundle supports the purging of thumbnails from Cloudflare CDN. This is handled by the `CloudflareManager` and is
disabled by default. To enable it, do the following.

* Add to your `cdn.yml`:

    ```
    genj_thumbnail:
        cloudflare:
            enable:     true
            zone_id:    '%genj_thumbnail.cloudflare.zone_id%'
            auth_email: '%genj_thumbnail.cloudflare.auth_email%'
            auth_key:   '%genj_thumbnail.cloudflare.auth_key%'
    ```

* `zone_id` can be found on your domain overview page: https://www.cloudflare.com/a/overview/<your_domain>

* `auth_email` and `auth_key` (called `Global API Key` on the website) can be found on your Profile page: https://www.cloudflare.com/a/profile

* Add the secrets to your `parameters.yml`:

    ```
    genj_thumbnail.cloudflare.zone_id:
    genj_thumbnail.cloudflare.auth_email:
    genj_thumbnail.cloudflare.auth_key:
    ```

* If you host the thumbnails on a separate domain, configure the routing as such:

    ```
    genj_thumbnail:
        path:  /thumbnails/{bundleName}/{entityName}/{attribute}/{filter}/{idShard}/{slug}-{id}.{_format}
        host: '{domain}'
        defaults:
            _controller: liip_imagine.controller:filterActionForObject
            domain: '%cdn_domain%'
            attribute: 'fileUpload'
        requirements:
            _format: jpg|jpeg|gif|png
            slug: "[a-zA-Z0-9\\-\\/]+"
            idShard: "[\\w/]+"
            id: "^\\d+$"
            domain: ".*"
    ```

* And add the corresponding `cdn_domain` in your parameters.yml:

    ```
    cdn_domain: static.example.com
    ```

* If you want to exclude things from the Cloudflare cache (e.g. your `app_dev.php`), you can add those under Page Rules in your Cloudflare control panel. See https://www.cloudflare.com/a/page-rules/<example.com>. The rule should be `Cache Level: Bypass`.

## Testing Cloudflare CDN

To test if the setup is working, you should do the following with an image that is accessed by the website using the twig thumbnail filter. Note that there can be a few seconds of delay because of the way information is distributed amongst the Cloudflare nodes.

1. After uploading an image in the website and seeing it appear on the site, hitting it with curl should give `CF-Cache-Status: HIT`:

    ```
    $ curl -I https://example.com/thumbnail.jpg

    HTTP/1.1 200 OK
    ...
    CF-Cache-Status: HIT
    ```

2. Deleting the image should purge the thumbnail from Cloudflare caches. The next hit with curl should give `CF-Cache-Status: MISS`:

    ```
    $ curl -I https://example.com/thumbnail.jpg

    HTTP/1.1 200 OK
    ...
    CF-Cache-Status: MISS
    ```

3. Uploading a new image over an existing image should purge the thumbnail from Cloudflare caches, so a subsequent curl hit should result in `CF-Cache-Status: MISS`. This will cache the thumbnail, so the curl hit after that should give `CF-Cache-Status: HIT`.

4. Accessing a thumbnail on Cloudflare that does not exist, should show the website's 404 page and *not* cache it. So repeatedly requesting a non-existing should keep giving `CF-Cache-Status: MISS`.
