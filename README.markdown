# Render Markdown as HTML #

This script was created to convert [Markdown][md] files to usable HTML on-the-fly on various web sites I deal with. It's intended for situations where the web server will be serving out Markdown files directly. It wasn't meant to be used with blogging or CMS tools. There are usually plug-ins for that.

As an example, here's [this README][readme] being processed by the script.

By combining this with Apache's "Fancy Indexing" and WebDAV, you can throw together a quick and dirty documentation repository, for instance. Or maybe you just want to quickly share a document with someone on an existing web server without worrying about whether or not they've heard of Markdown.

## Features ##

  * Generates HTML (with specific stylesheets for screen display and printing).
  * Generates a clickable table of contents (hidden by default) based on any headings found in the file.
  * Provides a link that will display the original text version of the document.
  * Metadata support (see below)
  * A mostly self-explanatory INI file is included to control behavior.

### Metadata ###

[MultiMarkdown metadata][mmd] can be ignored, removed, or displayed in a table. The table has an ID and it's parts have classes, so you can more easily target it in your CSS.

If you choose to display metadata in a table, you can optionally have certain values in the table turned into links. For instance, say you have all your documents indexed somehow by a metadata attribute called "tags" and your site will list matching documents at a URL like "http://www.mysite.tld/tags/foo". To make the displayed tags link to the appropriate address, you could define this in the INI:

    link_attrs[] = "tags"
    link_pattern = "http://www.mysite.tld/%k/%v"

`link_attrs` is an array of possible attribute names, so be sure to include the empty trailing brackets.

## Requirements ##

  * Apache with mod_rewrite
  * [PHP Markdown][phpmd] (or PHP Markdown Extra)
  * [PHP SmaryPants][phpsp] (optional)

This could surely be made to work with other web servers. If anyone does so, please let me know what it takes so I can include the instructions here.

## Setup ##

Clone this repository. For example:

    mkdir /var/www/support
    cd /var/www/support
    git clone git://github.com/skurfer/RenderMarkdown.git markdown

Download [PHP Markdown][phpmd] (or PHP Markdown Extra) and [PHP SmaryPants][phpsp] from Michel Fortin. Put `markdown.php` and `smartypants.php` somewhere in PHP's include path (or in the same directory as `render.php`).

Add an alias in your Apache config:

    Alias /markdown/ "/var/www/support/markdown/"

Add rewrite rules. This can be done in the `.htaccess` file for a specific folder, or in the global Apache config. Some common extensions are included, but you can adjust them to your needs. (You might want to process *all* text as Markdown by adding "txt".)

    # display Markdown as HTML by default
    RewriteEngine on
    RewriteRule .+\.(markdown|mdown|md|mkd)$ /markdown/render.php
    RewriteRule .+\.(markdown|mdown|md|mkd)\-text$ /markdown/render.php [L]

Copy `render.ini.default` to `render.ini` and edit to your liking.

Not everyone will love the included stylesheets, but they should give you an idea which elements to define styles for.

## Cautions ##

If you enable this globally for every directory on your web server and you use WebDAV, be sure to disable Apache's RewriteEngine on WebDAV folders or the Markdown files in your WebDAV volume will get sent to your file manager as HTML as well.

`mod_userdir` locations like `http://server.tld/~user/Foo.mdown` don't currently work. (It's difficult to determine the filesystem path using only the URL in such cases.)

## Credit ##

Although I have almost completely reworked and rewritten it, I should mention that the basis for generating the Table of Contents came from [this article][toc] on WebDesignLessons.com.

And of course we should all thank [Gruber][df] and Michel Fortin for their work.

[md]:     http://daringfireball.net/projects/markdown/
[mmd]:    https://github.com/fletcher/MultiMarkdown/wiki/MultiMarkdown-Syntax-Guide
[readme]: http://projects.skurfer.com/Example.mdown
[phpmd]:  http://michelf.com/projects/php-markdown/
[phpsp]:  http://michelf.com/projects/php-smartypants/
[toc]:    http://www.webdesignlessons.com/creating-a-table-of-contents-generator-in-php/
[df]:     http://daringfireball.net/

## Future Features ##

I've considered adding the following features, but I don't personally have a need for them at the moment. If even one person requests one of these, I'll probably add it.

  * Specify a file to include before the main body
  * Specify a file to include after the main body
  * An option to display the source file's last modified time (top, bottom, none)
  * An option to add outline-style letters and numbers to items in the table of contents (or MediaWiki style numbering)
  * An option to add the letters/numbers from the table of contents to the actual headings in the document
