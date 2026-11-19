<?php

// Local-only RSS/Atom responses for transport status and parser regressions.
$case = isset($_GET['case']) ? $_GET['case'] : 'rss';
if ($case === 'missing') {
    http_response_code(404);
    header('Content-Type: application/rss+xml; charset=utf-8');
    echo '<rss version="2.0"><channel><title>Not found</title></channel></rss>';
    return;
}

if ($case === 'failure') {
    http_response_code(503);
    header('Content-Type: application/rss+xml; charset=utf-8');
    echo '<rss version="2.0"><channel><title>Service unavailable</title></channel></rss>';
    return;
}

header('Content-Type: application/rss+xml; charset=utf-8');
if ($case === 'malformed') {
    echo '<rss><channel><title>Malformed';
    return;
}

if ($case === 'atom') {
    header('Content-Type: application/atom+xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' .
        '<feed xmlns="http://www.w3.org/2005/Atom"><title>Local Atom</title>' .
        '<id>urn:local:atom</id><updated>2026-01-01T00:00:00Z</updated>' .
        '<entry><title>Atom Entry</title><id>urn:local:entry</id>' .
        '<updated>2026-01-01T00:00:00Z</updated></entry></feed>';
    return;
}

echo '<?xml version="1.0" encoding="UTF-8"?>' .
    '<rss version="2.0"><channel><title>Local RSS</title><link>http://localhost/</link>' .
    '<description>Local regression test feed</description>' .
    '<item><title>Entry One</title><link>http://localhost/entry</link>' .
    '<description>Test entry</description></item></channel></rss>';
