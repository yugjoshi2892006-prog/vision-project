<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function index() {
        $this->load->view('admin/header');
        $this->load->view('admin/user_list');
        $this->load->view('admin/footer');
    }

    // ================= FETCH USERS =================
    public function fetch_users() {

        header('Content-Type: application/json');

        $search = $this->input->post('search');

        if (!empty($search)) {
            $this->db->like('name', $search);
            $this->db->or_like('mobile', $search);
        }

        $query = $this->db->get('users');

        echo json_encode([
            'data' => $query->result()
        ]);

        exit;
    }

    // ================= TOGGLE STATUS =================
    public function toggle_status() {

        header('Content-Type: application/json');

        $id = $this->input->post('id');
        $status = $this->input->post('status');

        $this->db->where('id', $id);
        $this->db->update('users', ['isActive' => $status]);

        echo json_encode([
            'success' => true
        ]);

        exit;
    }
}
