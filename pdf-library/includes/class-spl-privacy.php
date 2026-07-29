<?php
defined('ABSPATH') || exit;

final class SPL_Privacy {
    const PAGE_SIZE = 100;

    public function hooks() {
        add_filter('wp_privacy_personal_data_exporters', array($this, 'exporters'));
        add_filter('wp_privacy_personal_data_erasers', array($this, 'erasers'));
    }

    public function exporters($exporters) {
        $exporters['pdf-library'] = array(
            'exporter_friendly_name' => 'PDF Library Reading Data',
            'callback' => array($this, 'export'),
        );
        return $exporters;
    }

    public function export($email, $page = 1) {
        $user = get_user_by('email', $email);
        if (!$user) {
            return array('data' => array(), 'done' => true);
        }

        global $wpdb;
        $page = max(1, absint($page));
        $offset = ($page - 1) * self::PAGE_SIZE;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spl_user_data WHERE user_id=%d ORDER BY id ASC LIMIT %d OFFSET %d",
            $user->ID,
            self::PAGE_SIZE,
            $offset
        ));
        $reports = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spl_reports WHERE user_id=%d ORDER BY id ASC LIMIT %d OFFSET %d",
            $user->ID,
            self::PAGE_SIZE,
            $offset
        ));

        $data = array();
        foreach ($rows as $row) {
            $data[] = array(
                'group_id' => 'pdf-library',
                'group_label' => 'PDF Library',
                'item_id' => 'reading-' . $row->id,
                'data' => array(
                    array('name' => 'Document', 'value' => get_the_title($row->document_id)),
                    array('name' => 'Type', 'value' => $row->data_type),
                    array('name' => 'Page', 'value' => $row->page_number),
                    array('name' => 'Private note', 'value' => $row->note),
                    array('name' => 'Updated', 'value' => $row->updated_at),
                ),
            );
        }
        foreach ($reports as $report) {
            $data[] = array(
                'group_id' => 'pdf-library-reports',
                'group_label' => 'PDF Library Reports',
                'item_id' => 'report-' . $report->id,
                'data' => array(
                    array('name' => 'Document', 'value' => get_the_title($report->document_id)),
                    array('name' => 'Reason', 'value' => $report->reason),
                    array('name' => 'Details', 'value' => $report->details),
                    array('name' => 'Status', 'value' => $report->status),
                    array('name' => 'Created', 'value' => $report->created_at),
                ),
            );
        }

        return array(
            'data' => $data,
            'done' => count($rows) < self::PAGE_SIZE && count($reports) < self::PAGE_SIZE,
        );
    }

    public function erasers($erasers) {
        $erasers['pdf-library'] = array(
            'eraser_friendly_name' => 'PDF Library Reading Data',
            'callback' => array($this, 'erase'),
        );
        return $erasers;
    }

    public function erase($email, $page = 1) {
        $user = get_user_by('email', $email);
        if (!$user) {
            return array('items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true);
        }

        global $wpdb;
        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}spl_user_data WHERE user_id=%d ORDER BY id ASC LIMIT %d",
            $user->ID,
            self::PAGE_SIZE
        ));
        $items_removed = false;
        if ($ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            $deleted = $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}spl_user_data WHERE id IN ({$placeholders})", $ids));
            $items_removed = $deleted > 0;
        }

        $reports_updated = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}spl_reports SET user_id=0, details='[Removed through privacy request]', updated_at=%s WHERE user_id=%d",
            current_time('mysql', true),
            $user->ID
        ));
        $items_removed = $items_removed || $reports_updated > 0;

        $remaining = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}spl_user_data WHERE user_id=%d",
            $user->ID
        ));

        return array(
            'items_removed' => $items_removed,
            'items_retained' => false,
            'messages' => array(),
            'done' => 0 === $remaining,
        );
    }
}
