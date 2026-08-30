<?php
// Minimal, dependency-free Markdown renderer for story/entity bio bodies.
// Deliberately not a full CommonMark implementation — it only needs to
// handle what the admin editor actually produces: headings, paragraphs,
// **bold**/*italic*, [links](url), and the image+caption pairs the editor's
// image picker inserts (see admin/editor.php's insertImage()).
//
// The one rule that matters beyond formatting: an image line is only ever
// rendered when immediately followed by an italic-only caption line, which
// is where the credit text lives. An image missing its caption is dropped
// rather than shown uncredited — enforcement of "no image without
// credit_text", not just editor convention.

function render_markdown(string $md): string {
    $lines = preg_split('/\r\n|\r|\n/', $md);
    $html = '';
    $paragraph = [];

    $flush = function () use (&$paragraph, &$html) {
        if ($paragraph) {
            $html .= '<p>' . md_inline(implode(' ', $paragraph)) . "</p>\n";
            $paragraph = [];
        }
    };

    $i = 0;
    $count = count($lines);
    while ($i < $count) {
        $line = $lines[$i];

        if (trim($line) === '') {
            $flush();
            $i++;
            continue;
        }

        if (preg_match('/^(#{1,6})\s+(.*)$/', $line, $m)) {
            $flush();
            $level = strlen($m[1]);
            $html .= "<h{$level}>" . md_inline($m[2]) . "</h{$level}>\n";
            $i++;
            continue;
        }

        if (preg_match('/^!\[([^\]]*)\]\((\S+)\)\s*$/', $line, $m)) {
            $flush();
            $alt = $m[1];
            $url = $m[2];
            // look ahead past blank lines for an italic-only caption line
            $j = $i + 1;
            while ($j < $count && trim($lines[$j]) === '') $j++;
            if ($j < $count && preg_match('/^\*(?!\*)(.+?)\*\s*$/', $lines[$j], $cap)) {
                $html .= '<figure><img src="' . h($url) . '" alt="' . h($alt) . '" loading="lazy">'
                    . '<figcaption>' . md_inline($cap[1]) . "</figcaption></figure>\n";
                $i = $j + 1;
            } else {
                // No caption immediately after — refuse to render rather
                // than show an uncredited image.
                $html .= "<!-- image dropped: missing credit caption -->\n";
                $i++;
            }
            continue;
        }

        $paragraph[] = trim($line);
        $i++;
    }
    $flush();

    return $html;
}

function md_inline(string $text): string {
    $text = h($text);
    $text = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/', '<a href="$2" rel="noopener">$1</a>', $text);
    $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $text);
    return $text;
}
