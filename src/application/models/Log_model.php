<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Log_model extends CI_Model {

    public function ensure_session($session_id, $started_at) {
        // Insert if not exists
        $exists = $this->db->get_where('sessions', array('id' => $session_id))->row();
        if (!$exists) {
            $this->db->insert('sessions', array(
                'id' => $session_id,
                'started_at' => $started_at
            ));
        }
    }

    public function insert_page_view($session_id, $page, $viewed_at) {
        $this->db->insert('page_views', array(
            'session_id' => $session_id,
            'page' => $page,
            'viewed_at' => $viewed_at
        ));
    }

    public function get_sequences() {
        // return array of arrays: [ [page1,page2,...], ... ] grouped by session ordered by time
        $this->db->select('session_id, page, viewed_at')
                 ->from('page_views')
                 ->order_by('session_id ASC, viewed_at ASC');
        $q = $this->db->get();

        $sequences = array();
        $current_session = null;
        $current = array();

        foreach ($q->result_array() as $row) {
            if ($current_session !== $row['session_id']) {
                if ($current_session !== null) {
                    // deduplicate consecutive duplicates within a session
                    $clean = array();
                    $prev = null;
                    foreach ($current as $p) {
                        if ($p !== $prev) $clean[] = $p;
                        $prev = $p;
                    }
                    $sequences[] = $clean;
                }
                $current_session = $row['session_id'];
                $current = array();
            }
            $current[] = $row['page'];
        }
        if (!empty($current)) {
            $clean = array();
            $prev = null;
            foreach ($current as $p) {
                if ($p !== $prev) $clean[] = $p;
                $prev = $p;
            }
            $sequences[] = $clean;
        }
        return $sequences;
    }

    public function save_patterns($patterns) {
        if (empty($patterns)) return;
        $batch = array();
        $now = date('Y-m-d H:i:s');
        foreach ($patterns as $p) {
            $batch[] = array(
                'pattern' => implode('>', $p['pattern']),
                'support' => $p['support'],
                'created_at' => $now
            );
        }
        if (!empty($batch)) {
            // Clear previous results for demo purposes (optional)
            $this->db->empty_table('patterns');
            $this->db->insert_batch('patterns', $batch);
        }
    }
}
