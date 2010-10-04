<?php

/*
    To display a Markdown file as HTML, use mod_rewrite to call this script
    when the file is requested.
    
    By Rob McBroom, 2010
    
    TODO add INI file for settings
    TODO use first header as title
    TODO return a proper 404 for missing files
*/

$requested = rawurldecode( $_SERVER['REQUEST_URI'] );
$request_parts = explode( '.', $requested );
if ( array_pop( $request_parts ) == "text" ) {
  // replace the requested name with '.text' removed
  $requested = implode( '.', $request_parts );
  $show_text = true;
} else {
  // the file name to use for display and URLs
  $requested_file = basename( rawurldecode( $requested ) );
  $show_text = false;
}

if ( preg_match( "/^\./", $requested ) || $requested == "index.php" ) {
  // suspicious
  header( "Content-type: text/plain" );
  echo "It looks like you're up to something.\n";
  echo "Trying to read: $requested\n";
  exit;
}

// Markdown file to read
$md_source = $_SERVER['DOCUMENT_ROOT'] . $requested;
// path to use in link URLs
$ht_path = dirname( $_SERVER['SCRIPT_NAME'] );

if ( file_exists( $md_source ) ) {
  // if file name ended with ".text", show the original Markdown
  if ( $show_text ) {
    header( "Content-type: text/plain" );
    readfile( $md_source );
  } else {
    // Publish/Display the text as HTML
    $settings = parse_ini_file( "render.ini" );
    // convert to Markdown
    include_once( "markdown.php" );
    $html = Markdown( file_get_contents( $md_source ) );
    // apply SmartyPants
    if ( $settings['smartypants'] ) {
      include_once( "smartypants.php" );
      $html = SmartyPants( $html );
    }
    if ( $settings['toc'] ) {
      $html = table_of_contents( $html );
    }
    $title = "Viewing Markdown file ($requested_file) as HTML";
    if ( $settings['title_from_heading'] ) {
      $title = get_title( $html );
    }
    echo <<<HTML
<html>
<head>
  <meta http-equiv="Content-type" content="text/html; charset=utf-8">
  <link rel="stylesheet" href="${ht_path}/markdown-screen.css" type="text/css" media="screen" charset="utf-8">
  <link rel="stylesheet" href="${ht_path}/markdown-print.css" type="text/css" media="print" charset="utf-8">
  <title>$title</title>
  <script type="text/javascript" charset="utf-8">
    function hideStuff() {
      document.getElementById('TOC').style.display = 'none';
      document.getElementById('hideButton').style.display = 'none';
      document.getElementById('showButton').style.display = 'inline';
      return true;
    }
    function showStuff() {
      document.getElementById('TOC').style.display = 'inline';
      document.getElementById('hideButton').style.display = 'inline';
      document.getElementById('showButton').style.display = 'none';
      return true;
    }
  </script>
</head>
<body onLoad="hideStuff();">

HTML;
    if ( $settings['text_version'] ) {
      echo '<div class="controls" style="float: right"><a href="' . $requested_file . '.text">View Original Text</a></div>' . "\n";
    }
    echo $html;
    echo <<<HTML
  <div id="bigfoot">
    <!-- A decent amount of empty space was added so the browser can jump to anchors near the bottom of the page. -->
    &nbsp;
  </div>

HTML;
    echo <<<HTML
</body>
</html>
HTML;
  }
} else {
  echo "<p>I couldn't find anything under that name. Sorry.</p>\n";
}

function table_of_contents( $html ) {
  preg_match_all("/(<h([1-6]{1})[^<>]*>)([^<>]+)(<\/h[1-6]{1}>)/", $html, $matches, PREG_SET_ORDER);
  $toc = "";
  $list_index = 0;
  $indent_level = 0;
  $raw_indent_level = 0;
  $anchor_history = array();
  foreach ( $matches as $val ) {
    ++$list_index;
    $prev_indent_level = $indent_level;
    $indent_level = $val[2];
    $anchor = safe_parameter( $val[3] );
    // ensure that we don't reuse an anchor
    $anchor_index = 0;
    $raw_anchor = $anchor;
    while ( in_array( $anchor, $anchor_history ) ) {
      $anchor_index++;
      $anchor = $raw_anchor . strval( $anchor_index );
    }
    array_push( $anchor_history, $anchor );
    if ( $indent_level > $prev_indent_level ) {
      // indent further (by starting a sub-list)
      $toc .= "\n<ul>\n";
      $raw_indent_level++;
    }
    if ( $indent_level < $prev_indent_level ) {
      // end the list item
      $toc .= "</li>\n";
      // end this list
      $toc .= "</ul>\n";
      $raw_indent_level--;
    }
    if ( $indent_level <= $prev_indent_level ) {
      // end the list item too
      $toc .= "</li>\n";
    }
    // print this list item
    $toc .= '<li><a href="#'.$anchor.'">'. $val[3] . '</a>';
    $Sections[$list_index] = $val[1] . $val[3] . $val[4]; // Original heading to be Replaced
    $SectionWIDs[$list_index] = '<h' . $val[2] . ' id="'.$anchor.'">' . $val[3] . $val[4]; // New Heading
  }
  // close out the list
  $toc .= "</li>\n";
  for ( $i = $raw_indent_level; $i > 1; $i-- ) {
    $toc .= "</ul>\n</li>\n";
  }
  $toc .= "</ul>\n";
  return '<p><span id="hideButton" onClick="hideStuff();">Table of Contents <span class="controls">(hide)</span></span><span class="controls" id="showButton" onClick="showStuff();">Show Table of Contents</span></p>
<div id="TOC">' . $toc . '</div>' . "\n" . str_replace($Sections, $SectionWIDs, $html);
}

function get_title( $html ) {
  if ( preg_match( "/<h[1-6]{1}[^<>]*>([^<>]+)<\/h[1-6]{1}>/", $html, $matches ) ) {
    return $matches[1];
  } else {
    return "Untitled Markdown Document";
  }
}

function safe_parameter( $unsafe ) {
  
  /* change a string into something that can be safely used as a parameter
  in a URL. Example: "Rob is a PHP Genius" would become "rob_is_a_php_genius" */
  
  // remove all but alphanumerics, spaces and underscores
  $lowAN = preg_replace( "/[^-a-z0-9_ ]/", "", strtolower( $unsafe ) );
  // replace spaces/underscores with underscores
  $safe = preg_replace( "/[ _]+/", "_", $lowAN );
  return $safe;
}

function html_comment( $invar ) {
  /* for debugging - this function will spit out an HTML comment
  to show what's in a variable */
  
  echo "\n<!--\n";
  print_r( $invar );
  echo "\n-->\n";
}
?>
