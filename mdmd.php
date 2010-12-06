<?php
/*
## Markdown Metadata ##

by Rob McBroom

Parse metadata at the beginning of a Markdown document.

This is based on (and should be fully compatible with) the metadata system
used by [MultiMarkdown][1] and by [Python-Markdown][2].

### Usage ###

$markdown_source = file_get_contents( $markdown_file );
$md = parse_metadata( $markdown_source );

It returns a two-element associative array containing 'metadata' and 'document'.

'metadata' is an array of attributes and their values. Values are stored in an array, no matter how many there are. It might be easier to just demonstrate. This section at the top of a document:

    author: Rob McBroom
    date:   2010/12/06
    tags:   php
            project
            markdown
    blank:

will get turned into this:

    Array
    (
        [author] => Array
            (
                [0] => Rob McBroom
            )
        [date] => Array
            (
                [0] => 2010/12/06
            )
        [tags] => Array
            (
                [0] => php
                [1] => project
                [2] => markdown
            )
        [blank] => Array
            (
                [0] => 
            )
    )

'document' is a string containing the original document minus any metadata
that was found. You can run this through PHP Markdown.

[1]: https://github.com/fletcher/MultiMarkdown/wiki/MultiMarkdown-Syntax-Guide
[2]: http://www.freewisdom.org/projects/python-markdown/Meta-Data
*/

function parse_metadata( $raw_doc ) {
  /* designed to be compatible with MultiMarkdown's metadata */
  $lines = explode( PHP_EOL, $raw_doc );
  $metadata = $doc_lines = array();
  // flag to halt searching for metadata
  $parse = true;
  foreach( $lines as $line ) {
    if ( $parse ) {
      if ( preg_match( "/^$/", $line ) ) {
        // stop looking for meta-data, treat the rest as the document
        $parse = false;
        continue;
      }
      if ( preg_match( "/([\w-]+):(.*)/", $line, $parts ) ) {
        $key = strtolower( trim( $parts[1] ) );
        $value = trim( $parts[2] );
        $metadata[$key] = array( $value );
      } elseif ( preg_match( "/^\s\s\s\s(.+)/", $line, $parts ) ) {
        // indenting 4 or more spaces continues the previous key
        if ( ! isset( $key ) ) {
          // the document started with an indented line
          // assume no meta-data and stop parsing
          $parse = false;
          array_push( $doc_lines, $line );
          continue;
        }
        $value = trim( $parts[1] );
        array_push( $metadata[$key], $value );
      } else {
        // not a blank line, but not metadata either
        // probably a document that begind with a normal line
        $parse = false;
        array_push( $doc_lines, $line );
      }
    } else {
      // add lines to the document
      array_push( $doc_lines, $line );
    }
  }
  $document = implode( PHP_EOL, $doc_lines );
  return( array( 'metadata' => $metadata, 'document' => $document ) );
}
?>
