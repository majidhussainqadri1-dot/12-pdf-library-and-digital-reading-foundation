<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Citations {
    public static function export(int $edition_id, string $format, int $page = 0): array {
        $edition = PLDR_Future_Data::require_edition($edition_id);
        if (is_wp_error($edition)) return array('error' => $edition);
        $page = absint($page);
        if ($page > (int) $edition['pages']) return array('error' => PLDR_Core::machine_error('pldr_citation_page', 'Citation page is outside this document edition.', 400, array('pages'=>(int)$edition['pages'])));
        $format = sanitize_key($format ?: 'csl-json');
        $author = trim((string) $edition['author_name']);
        $title = trim((string) $edition['title']);
        $year = (int) $edition['publication_year'];
        $url = PLDR_Core::route_url('document', array('id' => $edition['public_id'], 'slug' => $edition['slug']));
        $url = add_query_arg(array_filter(array('edition'=>$edition_id,'page'=>$page ?: null), static fn($value): bool => null !== $value), $url);
        $key = 'pldr-' . substr(str_replace('-', '', (string) $edition['public_id']), 0, 12) . '-e' . $edition_id;
        $csl = array('id' => $key, 'type' => 'book', 'title' => $title, 'author' => array(array('literal' => $author)), 'issued' => array('date-parts' => array(array($year ?: null))), 'publisher' => (string) $edition['publisher'], 'ISBN' => (string) $edition['isbn'], 'URL' => $url, 'edition' => (string) $edition['edition_label'], 'page' => $page ?: null, 'PLDR-edition-id'=>$edition_id);
        if ('csl-json' === $format) return array('format' => 'csl-json', 'content' => $csl, 'document_id'=>$edition['public_id'], 'edition_id'=>$edition_id, 'page'=>$page ?: null);
        if ('bibtex' === $format) return array('format' => 'bibtex', 'content' => "@book{{$key},\n  title={" . self::escape($title) . "},\n  author={" . self::escape($author) . "},\n  year={" . ($year ?: '') . "},\n  publisher={" . self::escape((string) $edition['publisher']) . "},\n  isbn={" . self::escape((string) $edition['isbn']) . "},\n  url={" . self::escape($url) . "}\n}", 'document_id'=>$edition['public_id'], 'edition_id'=>$edition_id, 'page'=>$page ?: null);
        if ('ris' === $format) return array('format' => 'ris', 'content' => "TY  - BOOK\nTI  - {$title}\nAU  - {$author}\nPY  - " . ($year ?: '') . "\nPB  - {$edition['publisher']}\nSN  - {$edition['isbn']}\nUR  - {$url}\nER  -", 'document_id'=>$edition['public_id'], 'edition_id'=>$edition_id, 'page'=>$page ?: null);
        if (in_array($format, array('apa','mla','sabri'), true)) return array('format' => $format, 'content' => PLDR_Reader::citation($edition, $page, $format), 'document_id'=>$edition['public_id'], 'edition_id'=>$edition_id, 'page'=>$page ?: null);
        if ('chicago' === $format) return array('format' => 'chicago', 'content' => trim($author . '. ' . $title . '. ' . ($edition['edition_label'] ? $edition['edition_label'] . '. ' : '') . ($edition['publisher'] ? $edition['publisher'] . ', ' : '') . ($year ?: 'n.d.') . '. ' . $url . ($page ? ', ' . $page : '') . '.'), 'document_id'=>$edition['public_id'], 'edition_id'=>$edition_id, 'page'=>$page ?: null);
        return array('error' => PLDR_Core::machine_error('pldr_citation_format', 'Unsupported citation export format.', 400));
    }
    private static function escape(string $text): string { return str_replace(array('{','}'), array('\\{','\\}'), $text); }
}
