<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Gsp extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper(array('form', 'url'));
        $this->load->library(array('session', 'gsp_lib'));
        $this->load->database();
        $this->load->model('Log_model', 'logm');
    }

    public function index() {
        $data = array(
            'title' => 'GSP Demo — Web Navigation Pattern Mining',
            'message' => $this->session->flashdata('message'),
            'error' => $this->session->flashdata('error'),
        );
        $this->load->view('gsp/index', $data);
    }

    public function upload() {
        if (empty($_FILES['csv']['name'])) {
            $this->session->set_flashdata('error', 'File CSV belum dipilih.');
            redirect('gsp');
            return;
        }

        $config['upload_path']   = FCPATH . 'uploads/';
        if (!is_dir($config['upload_path'])) { @mkdir($config['upload_path'], 0777, true); }
        $config['allowed_types'] = 'csv';
        $config['max_size']      = 2048;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('csv')) {
            $this->session->set_flashdata('error', $this->upload->display_errors());
            redirect('gsp');
            return;
        }

        $filedata = $this->upload->data();
        $path = $filedata['full_path'];

        // Expecting CSV header: session_id,viewed_at,page
        $handle = fopen($path, "r");
        if (!$handle) {
            $this->session->set_flashdata('error', 'Gagal membuka file.');
            redirect('gsp');
            return;
        }

        $header = fgetcsv($handle); // read header
        if (!$header || count($header) < 3) {
            fclose($handle);
            $this->session->set_flashdata('error', 'Format CSV tidak valid. Header harus: session_id,viewed_at,page');
            redirect('gsp');
            return;
        }

        $colmap = array_flip($header); // map header -> index
        $required = ['session_id','viewed_at','page'];
        foreach ($required as $r) {
            if (!isset($colmap[$r])) {
                fclose($handle);
                $this->session->set_flashdata('error', 'Kolom wajib tidak ditemukan: ' . $r);
                redirect('gsp');
                return;
            }
        }

        $rows = 0;
        $this->db->trans_begin();
        try {
            while (($row = fgetcsv($handle)) !== FALSE) {
                if (count($row) < 3) continue;
                $session_id = trim($row[$colmap['session_id']]);
                $viewed_at  = trim($row[$colmap['viewed_at']]);
                $page       = trim($row[$colmap['page']]);

                if ($session_id === '' || $page === '') continue;
                if ($viewed_at === '') $viewed_at = date('Y-m-d H:i:s');

                // ensure session exists
                $this->logm->ensure_session($session_id, $viewed_at);
                // insert page view
                $this->logm->insert_page_view($session_id, $page, $viewed_at);
                $rows++;
            }
            fclose($handle);
            $this->db->trans_commit();
        } catch (Exception $e) {
            $this->db->trans_rollback();
            fclose($handle);
            $this->session->set_flashdata('error', 'Gagal impor: ' . $e->getMessage());
            redirect('gsp');
            return;
        }

        $this->session->set_flashdata('message', "Impor selesai. $rows baris dimasukkan.");
        redirect('gsp');
    }

    public function run() {
        $min_support = (int)$this->input->post('min_support');
        $max_len     = (int)$this->input->post('max_len');
        if ($min_support <= 0) $min_support = 2;
        if ($max_len <= 0) $max_len = 5;

        // Build sequences grouped by session ordered by viewed_at
        $sequences = $this->logm->get_sequences();

        if (empty($sequences)) {
            $this->session->set_flashdata('error', 'Belum ada data. Silakan unggah CSV terlebih dahulu.');
            redirect('gsp');
            return;
        }

        $result = $this->gsp_lib->mine($sequences, $min_support, $max_len);

        // Save to DB (optional)
        $this->logm->save_patterns($result);

        $data = array(
            'title' => 'Hasil Mining GSP',
            'min_support' => $min_support,
            'max_len' => $max_len,
            'num_sequences' => count($sequences),
            'patterns' => $result
        );
        $this->load->view('gsp/results', $data);
    }
}
