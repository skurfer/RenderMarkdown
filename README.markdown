# Render Markdown as HTML #

This script was created to convert [Markdown][md] files to usable HTML on-the-fly on various web sites I deal with. It's intended for situations where the web server will be serving out Markdown files directly. It wasn't meant to be used with blogging or CMS tools. There are usually plug-ins for that.

By combining this with Apache's "Fancy Indexing" and WebDAV, you can throw together a quick and dirty documentation repository, for instance. Or maybe you just want to quickly share a document with co-workers on an existing web server without worrying about whether or not they've heard of Markdown.

## Features ##

  * Generates HTML (with specific stylesheets for screen display and printing)
  * Generates a clickable table of contents (hidden by default) based on any headings found in the file
  * Provides a link that will display the original text version of the document

## Requirements ##

  * Apache with mod_rewrite
  * [PHP Markdown][phpmd] (or PHP Markdown Extra)
  * [PHP SmaryPants][phpsp]

## Setup ##

Clone this repository. For example:

    mkdir /var/www/support
    cd /var/www/support
    git clone git://github.com/skurfer/RenderMarkdown.git markdown

Download [PHP Markdown][phpmd] (or PHP Markdown Extra) and [PHP SmaryPants][phpsp] from Michel Fortin. Put `markdown.php` and `smartypants.php` somewhere in PHP's include path (or in the same directory as `render.php`).

Add an alias in your Apache config:

    Alias /markdown/ "/var/www/support/markdown/"

Add rewrite rules. This can be done in the `.htaccess` file for a specific folder, or in the global Apache config. Some common extensions are included, but you can adjust them to your needs. (You might want to process *all* text as Markdown by adding "txt". You can't currently use "text", though.)

    # display Markdown as HTML by default
    RewriteEngine on
    RewriteRule .+\.(markdown|mdown|md|mkd)$ /markdown/render.php
    RewriteRule .+\.(markdown|mdown|md|mkd)\.text$ /markdown/render.php [L]

Not everyone will love the included stylesheets, but they should give you an idea which elements to define styles for. Modify them to your liking.

## Cautions ##

If you enable this globally for every directory on your web server and you use WebDAV, be sure to disable Apache's RewriteEngine on WebDAV folders or the Markdown files in your WebDAV volume will get sent to your file manager as HTML as well.

## Credit ##

Although I have almost completely reworked and rewritten it, I should mention that the basis for generating the Table of Contents came from [this article][toc] on WebDesignLessons.com.

And of course we should all thank [Gruber][df] and Michel Fortin for their work.

[md]:    http://daringfireball.net/projects/markdown/
[phpmd]: http://michelf.com/projects/php-markdown/
[phpsp]: http://michelf.com/projects/php-smartypants/
[toc]:   http://www.webdesignlessons.com/creating-a-table-of-contents-generator-in-php/
[df]:    http://daringfireball.net/
