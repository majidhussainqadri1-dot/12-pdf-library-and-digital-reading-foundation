<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Citations {
    public static function export(int $edition_id, string $format, int $page = 0): array {
        $edition = PLDR_Future_Data::require_edition($edition_id);
        if (is_wp_error($edition)) return array('error' => $edition);
        $page = absint($page);
        if ($page > (int) $edition['pages']) return array('error' => PLDR_Core::machine_error('pldr_citation_page', 'Citation page is outside this document edition.', 400, array('pages'=>(int)$edition['pages'])));
        $format = sanitize_key($format ?: 'csl-json');
        $author = self::single_line((string) $edition['author_name']);
        $title = self::single_line((string) $edition['title']);
        $publisher = self::single_line((string) $edition['publisher']);
        $isbn = self::single_line((string) $edition['isbn']);
        $edition_label = self::single_line((string) $edition['edition_label']);
        $year = (int) $edition['publication_year'];
        $url = PLDR_Core::route_url('document', array('id' => $edition['public_id'], 'slug' => $edition['slug']));
        $url = add_query_arg(array_filter(array('edition'=>$edition_id,'page'=>$page ?: null), static fn($value): bool => null !== $value), $url);
        $url = esc_url_raw($url);
        $key = 'pldr-' . substr(str_replace('-', '', (string) $edition['public_id']), 0, 12) . '-e' . $edition_id . ($page ? '-p'.$page : '');
        $csl = array('id' => $key, 'type' => 'book', 'title' => $title, 'author' => array(array('literal' => $author)), 'issued' => array('date-parts' => array(array($year ?: null))), 'publisher' => $publisher, 'ISBN' => $isbn, 'URL' => $url, 'edition' => $edition_label, 'page' => $page ?: null, 'PLDR-edition-id'=>$edition_id);
        if ('csl-json' === $format) return array('format' => 'csl-json', 'content' => $csl, 'document_id'=>$edition['public_id'], 'edition_id'=>$edition_id, 'page'=>$page ?: null,'locator_bound'=>(bool)$page);
        if ('bibtex' === $format) {
            $page_field=$page?",\n  pages={".$page."}":'';
            return array('format' => 'bibtex', 'content' => "@book{{$key},\n  title={" . self::bibtex_escape($title) . "},\n  author={" . self::bibtex_escape($author) . "},\n  year={" . ($year ?: '') . "},\n  publisher={" . self::bibtex_escape($publisher) . "},\n  isbn={" . self::bibtex_escape($isbn) . "},\n  url={" . self::bibtex_escape($url) . "}".$page_field."\n}", 'document_id'=>$edition['public_id'], 'edition_id'=>$edition_id, 'page'=>$page ?: null,'locator_bound'=>(bool)$page);
        }
        if ('ris' === $format) {
            $page_line=$page?"\nSP  - {$page}":'';
            return array('format' => 'ris', 'content' => "TY  - BOOK\nTI  - {$title}\nAU  - {$author}\nPY  - " . ($year ?: '') . "\nPB  - {$publisher}\nSN  - {$isbn}\nUR  - {$url}".$page_line."\nER  -", 'document_id'=>$edition['public_id'], 'edition_id'=>$edition_id, 'page'=>$page ?: null,'locator_bound'=>(bool)$page);
        }
        if (in_array($format, array('apa','mla','sabri','plain'), true)) {
            $reader_format='plain'===$format?'sabri':$format;
            return array('format' => $format, 'content' => PLDR_Reader::citation($edition, $page, $reader_format), 'document_id'=>$edition['public_id'], 'edition_id'=>$edition_id, 'page'=>$page ?: null,'locator_bound'=>(bool)$page);
        }
        if ('chicago' === $format) return array('format' => 'chicago', 'content' => trim($author . '. ' . $title . '. ' . ($edition_label ? $edition_label . '. ' : '') . ($publisher ? $publisher . ', ' : '') . ($year ?: 'n.d.') . '. ' . $url . ($page ? ', ' . $page : '') . '.'), 'document_id'=>$edition['public_id'], 'edition_id'=>$edition_id, 'page'=>$page ?: null,'locator_bound'=>(bool)$page);
        return array('error' => PLDR_Core::machine_error('pldr_citation_format', 'Unsupported citation export format.', 400));
    }

    private static function single_line(string $text): string {
        $text=wp_strip_all_tags($text);
        $text=preg_replace('/[\x00-\x1F\x7F]+/u',' ',$text)??$text;
        $text=preg_replace('/\s+/u',' ',trim($text))??trim($text);
        return function_exists('mb_substr')?mb_substr($text,0,1000,'UTF-8'):substr($text,0,1000);
    }

    private static function bibtex_escape(string $text): string {
        return strtr($text,array('\\'=>'\\textbackslash{}','{'=>'\\{','}'=>'\\}','%'=>'\\%','#'=>'\\#','&'=>'\\&','_'=>'\\_'));
    }
}
