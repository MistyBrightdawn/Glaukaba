<?php

// src/Formatting.php

class Formatting {

    // Define regex patterns as class constants or static properties for clarity
    // These are simplified and will need significant expansion for full WakabaMark.
    private const PROTOCOL_RE = '/(?:http|https|ftp|mailto|nntp):/i'; // Simplified from wakautils
    private const URL_RE = '/((?:http:\/\/|https:\/\/|ftp:\/\/|mailto:|news:|irc:)[^\s<>()"]*?(?:\([^\s<>()"]*?\)[^\s<>()"]*?)*)((?:\s|<|>|"|\.|\)|\]|!|\?|,|&#44;|&quot;)*(?:[\s<>()"]|$))/s'; // Simplified

    /**
     * Main function to format a comment using WakabaMark-style rules.
     * This is a simplified port of Perl's do_wakabamark.
     *
     * @param string $text The raw comment text.
     * @param callable|null $quote_link_handler Optional callback to generate links for >>quotes.
     *        The callback should accept (string $post_num, ?string $board_dir) and return HTML string.
     * @return string The HTML formatted comment.
     */
    public static function format_comment_wakabamark_style(string $text, ?callable $quote_link_handler = null): string {
        $text = htmlspecialchars($text, ENT_QUOTES, CHARSET); // Basic escaping first
        $lines = preg_split('/(\r\n|\n|\r)/', $text);
        $html_output = "";
        $in_paragraph = false; // Simple paragraph handling

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            if (trim($line) === '') {
                if ($in_paragraph) {
                    $html_output .= "</p>\n";
                    $in_paragraph = false;
                }
                $html_output .= "<br>\n"; // Original wakabamark adds <br> for effectively empty lines if they contribute to structure
                continue;
            }

            if (!$in_paragraph) {
                $html_output .= "<p>";
                $in_paragraph = true;
            }


            // Simplified block detection (very basic)
            if (strpos($line, '[code]') === 0 && strpos($line, '[/code]') !== false) {
                if ($in_paragraph) { $html_output .= "</p>\n"; $in_paragraph = false; }
                $code_content = substr($line, strlen('[code]'), -strlen('[/code]'));
                $html_output .= '<pre class="prettyprint">' . htmlspecialchars($code_content) . '</pre>'; // Needs JS for highlighting
            } elseif (strpos($line, '[spoiler]') === 0 && strpos($line, '[/spoiler]') !== false) {
                if ($in_paragraph) { $html_output .= "</p>\n"; $in_paragraph = false; }
                $spoiler_content = substr($line, strlen('[spoiler]'), -strlen('[/spoiler]'));
                $html_output .= '<span class="spoiler">' . self::format_spans_wakabamark_style(htmlspecialchars($spoiler_content), $quote_link_handler) . '</span>';
            } elseif (strpos($line, '&gt;') === 0) { // Quoted line (>)
                if ($in_paragraph) { $html_output .= "</p>\n"; $in_paragraph = false; }
                // Collect all consecutive quote lines
                $quote_block = "";
                while ($i < count($lines) && strpos($lines[$i], '&gt;') === 0) {
                    $quote_block .= self::format_spans_wakabamark_style(ltrim(substr($lines[$i], strlen('&gt;'))), $quote_link_handler) . "<br>\n";
                    $i++;
                }
                $i--; // Adjust loop counter
                $html_output .= '<span class="quote">&gt;' . rtrim($quote_block, "<br>\n") . '</span><br>';
            } else {
                // Regular line, process for spans
                $html_output .= self::format_spans_wakabamark_style($line, $quote_link_handler);
                if ($i < count($lines) -1) { // Add line break if not the last line within a paragraph
                     $html_output .= "<br>\n";
                }
            }
        }
        if ($in_paragraph) {
            $html_output .= "</p>\n";
        }

        // Trim trailing <br> tags if a paragraph was closed right before them
        $html_output = preg_replace('/(<br\s*\/?>\n*)+(<\/p>)/s', '$2', $html_output);
        $html_output = rtrim($html_output, "<br>\n");


        return $html_output;
    }

    /**
     * Formats inline spans (bold, italic, code, links, quotes) within a line.
     * Simplified port of Perl's do_spans.
     *
     * @param string $line The line text (assumed to be htmlspecialchars'd).
     * @param callable|null $quote_link_handler Callback for >>quote links.
     * @return string Formatted line.
     */
    public static function format_spans_wakabamark_style(string $line, ?callable $quote_link_handler = null): string {
        // 1. URLs (simplified, does not handle complex edge cases of original regex)
        $line = preg_replace_callback(self::URL_RE, function ($matches) {
            $url = $matches[1];
            $trailing = $matches[count($matches)-1]; // Last capture group for trailing chars
            return "<a href=\"" . htmlspecialchars($url) . "\" rel=\"nofollow\">" . htmlspecialchars($url) . "</a>" . $trailing;
        }, $line);

        // 2. **Bold** and __Bold__ (strong)
        $line = preg_replace('/(?<![0-9a-zA-Z\*\_\x80-\x9f\xe0-\xfc])(\*\*|__)(?![<>\s\*_])(.+?)(?<![<>\s\*_\x80-\x9f\xe0-\xfc])\1(?![0-9a-zA-Z\*_])/s', '<strong>$2</strong>', $line);

        // 3. *Italic* and _Italic_ (em)
        $line = preg_replace('/(?<![0-9a-zA-Z\*\_\x80-\x9f\xe0-\xfc])(\*|_)(?![<>\s\*_])(.+?)(?<![<>\s\*_\x80-\x9f\xe0-\xfc])\1(?![0-9a-zA-Z\*_])/s', '<em>$2</em>', $line);

        // 4. `code` spans (inline code)
        // The original regex is complex due to Perl's features. PHP needs a simpler approach or a proper parser.
        // (?<![\x80-\x9f\xe0-\xfc]) (`+) ([^<>]+?) (?<![\x80-\x9f\xe0-\xfc]) \1
        // This simplified version handles single backticks.
        $line = preg_replace('/(?<!\w)`([^`]+?)`(?!`)/s', '<code>$1</code>', $line);

        // 5. >>Quotes (e.g., >>123 or >>>/board/123)
        if ($quote_link_handler) {
            // Regex for >>123 and >>>/board/123
            // &gt;&gt; is > , &gt;&gt;&gt; is >> because $line is already htmlspecialchar'd
            $line = preg_replace_callback(
                '/(&gt;&gt;&gt;\/([a-zA-Z0-9_-]+)\/(\d+)|&gt;&gt;(\d+))/',
                function ($matches) use ($quote_link_handler) {
                    if (!empty($matches[2])) { // Cross-board like >>>/b/123
                        return call_user_func($quote_link_handler, $matches[3], $matches[2]);
                    } elseif (!empty($matches[4])) { // Local board like >>123
                        return call_user_func($quote_link_handler, $matches[4], null);
                    }
                    return $matches[0]; // Should not happen
                },
                $line
            );
        }

        // 6. ^H (backspace for strikethrough) - This is tricky and context-dependent.
        // The Perl regex for ^H is complex and relies on recursive matching.
        // A simple PHP version might not fully replicate it.
        // For now, we'll skip direct porting of ^H due to its complexity.
        // $line = preg_replace('/(.)\^H/s', '<del>$1</del>', $line); // Very simplified, likely incorrect for many cases.

        return $line;
    }
}
