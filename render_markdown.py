import os
import re
from urllib import unquote
import markdown

def parse_metadata(source_text):
    """ Parse Meta-Data and separate from the original text.
    
    This was copied from the Python-Markdown meta-data extension
    because we need to get the metadata *before* processing the text.
    """
    meta = {}
    key = None
    lines = source_text.split('\n')
    META_RE = re.compile(r'^[ ]{0,3}(?P<key>[A-Za-z0-9_-]+):\s*(?P<value>.*)')
    META_MORE_RE = re.compile(r'^[ ]{4,}(?P<value>.*)')
    while 1:
        line = lines.pop(0)
        if line.strip() == '':
            break # blank line - done
        m1 = META_RE.match(line)
        if m1:
            key = m1.group('key').lower().strip()
            value = m1.group('value').strip()
            try:
                meta[key].append(value)
            except KeyError:
                meta[key] = [value]
        else:
            m2 = META_MORE_RE.match(line)
            if m2 and key:
                # Add another line to existing key
                meta[key].append(m2.group('value').strip())
            else:
                lines.insert(0, line)
                break # no meta data - done
    return (meta, '\n'.join(lines))

def first_heading(source_text):
    """Scan through the text and return the first heading."""
    lines = source_text.split('\n')
    for l in lines:
        if l.startswith(u'#'):
            return l.strip(u'# ')
    return None

def application(environ, start_response):
    # defaults
    status = '200 OK'
    show_text = False
    md_ext = ['extra', 'codehilite']
    
    ## read the INI settings
    pwd = os.path.dirname(environ['SCRIPT_FILENAME'])
    settings = {}
    conf_pat = re.compile(r'^(\S*)\s?=\s?(.*)$')
    for conf_line in open(pwd + '/render.ini'):
        m = conf_pat.match(conf_line)
        if m:
            s = m.group(1)
            v = m.group(2).strip('"\'')
            ## convert various strings to their boolean value
            if v.lower() == 'yes' or v.lower() == 'on' or v == '1':
                v = True
            elif v.lower() == 'no' or v.lower() == 'off' or v == '0':
                v = False
            ## support PHP-style array values
            if s.endswith('[]'):
                s = s[:-2]
                if settings.has_key(s):
                    settings[s].append(v)
                else:
                    settings[s] = [v]
            else:
                settings[s] = v
    
    ## get the source file path
    requested = unquote(environ['PATH_INFO'])
    rparts = requested.split('-')
    if rparts[-1] == settings.get('text_suffix', 'text'):
        requested = '-'.join(rparts[:-1])
        show_text = True
    source_file = environ['DOCUMENT_ROOT'] + requested
    source_text = unicode(open(source_file).read(), 'utf-8')
    
    ## display original text?
    if show_text:
        response_headers = [
            ('Content-type', 'text/plain;charset=utf-8'),
            ('Content-Length', str(len(source_text))),
        ]
        start_response(status, response_headers)
        
        return [source_text.encode('utf-8')]
    
    ## read some prefs
    include_toc = bool(int(settings.get('toc', 0)))
    toc_hidden = bool(int(settings.get('toc_hidden', 0)))
    meta_behavior = settings.get('metadata', 'ignore')
    link_attrs = settings.get('link_attrs', [])
    link_pattern = settings.get('link_pattern', None)
    text_version = bool(int(settings.get('text_version', 0)))
    text_suffix = settings.get('text_suffix', "text")
    
    ## process metadata
    meta_title = None
    if meta_behavior != 'ignore':
        (metadata, source_text) = parse_metadata(source_text)
        ## check for a title
        if metadata.has_key('title') and len(metadata['title'][0]) > 0:
            meta_title = metadata['title'][0]
            del metadata['title']
        ## respect the per-document pref for table of contents
        if metadata.has_key('toc'):
            toc = metadata['toc'][0].lower()
            if toc == 'on' or toc == 'yes' or toc == '1':
                include_toc = True
            elif toc == 'off' or toc == 'no' or toc == '0':
                include_toc = False
            del metadata['toc']
        ## display metadata?
        if meta_behavior == 'table':
            meta_html_table = '<table id="metadata">\n'
            for (name, values) in metadata.items():
                if link_pattern is not None and name in link_attrs:
                    newval = []
                    for target in values:
                        link = link_pattern.replace('%k', name).replace('%v', target)
                        newval.append('<a href="%s">%s</a>' % (link, target))
                    values = newval
                table_row = '''<tr>
    <td class="mda">%s</td>
    <td class="mdv">%s</td>
</tr>
''' % (name, '<br>'.join(values))
                meta_html_table = meta_html_table + table_row
            meta_html_table = meta_html_table + '</table>\n'
            source_text = meta_html_table + source_text
    
    toc_display = u''
    if include_toc:
        ## make sure the required extension is loaded
        md_ext.append('toc')
        ## molest the source
        source_text = u'<p id="showhide" class="controls" onClick="toggleVisibility(this, \'TOC\');">Hide Table of Contents</p>\n\n[TOC]\n\n' + source_text
        ## set initial state for table of contents
        if toc_hidden:
            toc_display = u' onLoad="javascript:toggleVisibility(document.getElementById(\'showhide\'), \'TOC\');"'
    
    text_link = ''
    if text_version:
        text_href = '%s-%s' % (requested, text_suffix)
        text_div = '<div class="controls" style="float: right"><a href="%s">View Original Text</a></div>' % text_href
        source_text = unicode(text_div, 'utf-8') + source_text
    
    
    ## a Markdown object to do the work
    md = markdown.Markdown(extensions=md_ext,
                           output_format='html4')
    ## convert text to HTML
    mdown = md.convert(source_text)
    
    title = "Viewing Markdown file (%s) as HTML" % os.path.basename(requested)
    if meta_title is not None:
        ## a title in the metadata takes precedence
        title = meta_title
    elif bool(int(settings.get('title_from_heading', 1))):
        heading = first_heading(source_text)
        if heading is not None:
            title = heading
    ht_path = "/markdown"
    codehilite_style = unicode(settings.get('codehilite_style', ''), 'utf-8')
    html = u'''<html>
<head>
  <meta http-equiv="Content-type" content="text/html; charset=utf-8">
  <link rel="stylesheet" href="%s/markdown-screen.css" type="text/css" media="screen" charset="utf-8">
  <link rel="stylesheet" href="%s" type="text/css" media="screen" charset="utf-8">
  <link rel="stylesheet" href="%s/markdown-print.css" type="text/css" media="print" charset="utf-8">
  <title>%s</title>
  <script type="text/javascript" charset="utf-8">
    function toggleVisibility(theButton, targetName) {
      var target = document.getElementById(targetName);
      if ( target.style.opacity == '0' ) {
        // show
        target.style.left = '0px';
        target.style.position = 'relative';
        target.style.opacity = '1';
        theButton.innerHTML = "Hide Table of Contents";
      } else {
        // hide
        target.style.left = '-4000px';
        target.style.position = 'absolute';
        target.style.opacity = '0';
        theButton.innerHTML = "Show Table of Contents";
      }
      return true;
    }
  </script>
</head>
<body%s>
%s
</body>
</html>

''' % (ht_path, codehilite_style, ht_path, title, toc_display, mdown)
    
    ## use an ID instead of a class for the table of contents
    html = html.replace(u'div class="toc"', 'div id="TOC"')
    
    # lines = ['* * * WEB SERVER ENVIRONMENT * * *']
    # for v in environ:
    #     lines.append('%s:: %s' % (v, environ[v]))
    # output = '\n'.join(lines)
    # html += '<pre>' + output + '</pre>'
    
    response_headers = [
        ('Content-type', 'text/html'),
        ('Content-Length', str(len(html))),
    ]
    start_response(status, response_headers)
    
    return [html.encode('utf-8')]

