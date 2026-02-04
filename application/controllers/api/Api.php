<?php

defined('BASEPATH') or exit('No direct script access allowed');

use \Firebase\JWT\JWT;

use \Firebase\JWT\Key;
class Api extends CI_Controller
{
    private $jwt_secret = 'b8d2f4e5a9c7d3b1f6e8a2c4d5f9b0a7e1c3f2d496ab89ce07df12345678abcd';
    
    public function __construct()
    {

        parent::__construct();

        $this->load->model('general_model');

        $this->load->helper(['url', 'form']);


        header("Access-Control-Allow-Origin: *"); 
        require_once APPPATH . '../vendor/autoload.php';

        header("Content-Type: application/json; charset=UTF-8");
        $this->load->library('email');


    }

   public function get_category()
{
    // Fetch top-level categories
    $categories = $this->db
        ->where('isActive', 1)
        ->where('parent_id', NULL)
        ->get('categories')
        ->result_array();

    $result = [];

    foreach ($categories as $cat) {
        // Get subcategories
        $subcategories = $this->db
            ->where('isActive', 1)
            ->where('parent_id', $cat['id'])
            ->get('categories')
            ->result_array();

        if (!empty($subcategories)) {
            // If there are subcategories
            $subcat_count = count($subcategories);
            $subcat_ids   = array_column($subcategories, 'id');

            $song_count = 0;
            if (!empty($subcat_ids)) {
                $song_count = $this->db
                    ->where_in('category_id', $subcat_ids)
                    ->count_all_results('songs');
            }

            $result[] = [
                'id'                  => $cat['id'],
                'name'                => $cat['name'],
                'image'               => base_url($cat['image']),
                'has_subcategories'   => true,
                'total_subcategories' => $subcat_count,
                'total_songs'         => $song_count
            ];

        } else {
            // If no subcategories
            $song_count = $this->db
                ->where('category_id', $cat['id'])
                ->count_all_results('songs');

            $result[] = [
                'id'                => $cat['id'],
                'name'              => $cat['name'],
                'image'             => base_url($cat['image']),
                'has_subcategories' => false,
                'total_songs'       => $song_count
            ];
        }
    }

    echo json_encode([
        'status' => true,
        'code'   => 200,
        'data'   => $result
    ]);
}




public function getSubCategories()
{
    // Get category ID from GET params
    $parent_id = $this->input->get('id');

    // Validate input
    if (empty($parent_id)) {
        echo json_encode([
            'code'    => 400,
            'status'  => false,
            'message' => 'Category ID is required',
            'data'    => []
        ]);
        return;
    }

    // Fetch direct subcategories of this parent
    $subcategories = $this->db
        ->where('isActive', 1)
        ->where('parent_id', $parent_id)
        ->get('categories')
        ->result_array();

    if (empty($subcategories)) {
        echo json_encode([
            'code'    => 400,
            'status'  => false,
            'message' => 'No subcategories found',
            'data'    => []
        ]);
        return;
    }

    $result = [];

    foreach ($subcategories as $subcat) {
        // Fetch children of this subcategory
        $child_subcats = $this->db
            ->where('isActive', 1)
            ->where('parent_id', $subcat['id'])
            ->get('categories')
            ->result_array();

        if (!empty($child_subcats)) {
            // If this subcategory has further children
            $child_count = count($child_subcats);
            $child_ids   = array_column($child_subcats, 'id');

            // Count songs inside all child subcategories
            $song_count = 0;
            if (!empty($child_ids)) {
                $song_count = $this->db
                    ->where_in('category_id', $child_ids)
                    ->count_all_results('songs');
            }

            $result[] = [
                'id'                  => $subcat['id'],
                'name'                => $subcat['name'],
                'image'               => !empty($subcat['image']) ? base_url($subcat['image']) : '',
                'has_subcategories'   => true,
                'total_subcategories' => $child_count,
                'total_songs'         => $song_count
            ];
        } else {
            // No child categories → count songs directly in this subcategory
            $song_count = $this->db
                ->where('category_id', $subcat['id'])
                ->count_all_results('songs');

            $result[] = [
                'id'                => $subcat['id'],
                'name'              => $subcat['name'],
                'image'             => !empty($subcat['image']) ? base_url($subcat['image']) : '',
                'has_subcategories' => false,
                'total_songs'       => $song_count
            ];
        }
    }

    echo json_encode([
        'code'   => 200,
        'status' => true,
        'data'   => $result
    ]);
}



public function getSong()
{
    // Get category ID from GET params
    $category_id = $this->input->get('id');

    // Validate input
    if (empty($category_id)) {
        echo json_encode([
            'code'    => 400,
            'status'  => false,
            'message' => 'Category ID is required',
            'data'    => []
        ]);
        return;
    }

    // Fetch songs by category_id
    $conditions = ['category_id' => $category_id];
    $songs = $this->general_model->getAll('songs', $conditions);

    if (!empty($songs)) {
        $result = [];
        foreach ($songs as $song) {
            $result[] = [
                'id'          => $song->id,
                'title'       => $song->title,
                // 'description' => $song->description,
                // 'created_on'  => $song->created_on
            ];
        }

        echo json_encode([
            'code'   => 200,
            'status' => true,
            'data'   => $result
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'code'    => 404,
            'status'  => false,
            'message' => 'No songs found for this category',
            'data'    => []
        ]);
    }
}

public function song_details()
{
    // Get song ID from GET params
    $song_id = $this->input->get('id');

    // Validate input
    if (empty($song_id)) {
        echo json_encode([
            'code'    => 400,
            'status'  => false,
            'message' => 'Song ID is required',
            'data'    => []
        ]);
        return;
    }

    // Fetch song details
    $song = $this->general_model->getOne('songs', ['id' => $song_id]);

    if (!empty($song)) {
        $result = [
            'title'       => $song->title,
            'description' => $song->description,
            
        ];

        echo json_encode([
            'code'    => 200,
            'status'  => true,
            'message' => 'Song details fetched successfully',
            'data'    => $result
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'code'    => 404,
            'status'  => false,
            'message' => 'Song not found',
            'data'    => []
        ]);
    }
}

public function login()
{
    header('Content-Type: application/json');

    // Read raw JSON input
    $raw = $this->input->raw_input_stream;
    $input_data = json_decode($raw, true);

    // If empty JSON, return error
    if (empty($input_data)) {
        return $this->output
            ->set_status_header(400)
            ->set_output(json_encode([
                'status' => false,
                'code' => 400,
                'message' => 'No input data provided',
                'data' => null
            ]));
    }

    // Extract mobile
    $mobile = trim($input_data['mobile'] ?? '');

    if (empty($mobile)) {
        return $this->output
            ->set_status_header(400)
            ->set_output(json_encode([
                'status'  => false,
                'code'    => 400,
                'message' => 'Mobile number is required',
                'data'    => null
            ]));
    }

    // Find user by mobile
    $user = $this->db->get_where('users', ['mobile' => $mobile])->row();

    if (!$user) {
        return $this->output
            ->set_status_header(400)
            ->set_output(json_encode([
                'status' => false,
                'code' => 400,
                'message' => 'No user found with this mobile number',
                'data' => null
            ]));
    }

    // Check active status
    if ((int)$user->isActive !== 1) {
        return $this->output
            ->set_status_header(400)
            ->set_output(json_encode([
                'status' => false,
                'code' => 400,
                'message' => 'Your account is not active',
                'data' => null
            ]));
    }

    // Generate token
    $token = $this->generate_jwt($user);

    // Success response
    return $this->output
        ->set_status_header(200)
        ->set_output(json_encode([
            'status'  => true,
            'code'    => 200,
            'message' => 'Login successful',
            'data'    => [
                'token' => $token,
                'user' => [
                    'id'     => $user->id,
                    'name'   => $user->name,
                    'mobile' => $user->mobile,
                    'role'   => $user->role,
                ]
            ]
        ]));
}

public function register()
{
    header('Content-Type: application/json');

    // Read JSON raw input
    $raw = $this->input->raw_input_stream;
    $input_data = json_decode($raw, true);

    // If JSON is empty, try POST (form-data, urlencoded)
    if (empty($input_data) && !empty($_POST)) {
        $input_data = $_POST;
    }

    // If still empty → error
    if (empty($input_data)) {
        return $this->output
            ->set_status_header(400)
            ->set_output(json_encode([
                'status' => false,
                'code' => 400,
                'message' => 'No input data provided',
                'data' => null
            ]));
    }

    // Extract fields
    $name = trim($input_data['name'] ?? '');
    $mobile = trim($input_data['mobile'] ?? '');

    // Validation
    if (empty($name) || empty($mobile)) {
        return $this->output
            ->set_status_header(400)
            ->set_output(json_encode([
                'status' => false,
                'code' => 400,
                'message' => 'name and mobile are required',
                'data' => null
            ]));
    }

    // Duplicate check
    $existing = $this->db->get_where('users', ['mobile' => $mobile])->row();
    if ($existing) {
        return $this->output
            ->set_status_header(400)
            ->set_output(json_encode([
                'status' => false,
                'code' => 400,
                'message' => 'User already exists',
                'data' => null
            ]));
    }

    // Insert user
    $insertData = [
        'name'       => $name,
        'mobile'     => $mobile,
        'role'       => 0,
        'isActive'   => 1,
        'created_on' => date('Y-m-d H:i:s')
    ];

    $this->db->insert('users', $insertData);
    $user_id = $this->db->insert_id();

    // Generate token
    $user_data = $this->db->get_where('users', ['id' => $user_id])->row();
    $token = $this->generate_jwt($user_data);

    // Success response
    return $this->output
        ->set_status_header(200)
        ->set_output(json_encode([
            'status'  => true,
            'code'    => 200,
            'message' => 'User registered successfully',
            'token'   => $token,
            'data'    => [
                'name'     => $name,
                'mobile'   => $mobile,
                'isActive' => 1,
                'role'     => 0
            ]
        ]));
}

public function list_song()
{
    header('Content-Type: application/json');

    // Get token
    $authHeader = $this->input->get_request_header('Authorization', TRUE);
    $token = null;

    if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
    }

    // Verify token
    $decoded = $this->verify_jwt($token);
    if (!$decoded || empty($decoded->data->id)) {
        return $this->output
            ->set_status_header(400)
            ->set_output(json_encode([
                'status'  => false,
                'code'    => 400,
                'message' => 'Invalid or missing token',
                'data'    => null
            ]));
    }

    // Fetch active songs with ID + Title
    $query = $this->db
                ->select('id, title')
                ->from('songs')
                ->where('isActive', 1)
                ->order_by('id', 'DESC')
                ->get();

    $songs = $query->result();

    if (empty($songs)) {
        return $this->output
            ->set_status_header(400)
            ->set_output(json_encode([
                'status' => false,
                'code' => 400,
                'message' => 'No active songs found',
                'data' => []
            ]));
    }

    return $this->output
        ->set_status_header(200)
        ->set_output(json_encode([
            'status'  => true,
            'code'    => 200,
            'message' => 'Song list fetched successfully',
            'data'    => $songs
        ]));
}

public function search_song()
{
    header('Content-Type: application/json');

    // Read Bearer Token
    $authHeader = $this->input->get_request_header('Authorization', TRUE);
    $token = null;

    if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
    }

    // Verify Token
    $decoded = $this->verify_jwt($token);
    if (!$decoded || empty($decoded->data->id)) {
        return $this->output
            ->set_status_header(401)
            ->set_output(json_encode([
                'status'  => false,
                'code'    => 401,
                'message' => 'Invalid or missing token',
                'data'    => null
            ]));
    }

    // Read search parameter from URL
    $search = $this->input->get('query');
    if (empty($search)) {
        return $this->output
            ->set_status_header(400)
            ->set_output(json_encode([
                'status'  => false,
                'code'    => 400,
                'message' => 'Search query is required',
                'data'    => []
            ]));
    }

    // Fetch matching active songs
    $query = $this->db
                ->select('id, title')
                ->from('songs')
                ->like('title', $search)
                ->where('isActive', 1)
                ->order_by('id', 'DESC')
                ->get();

    $songs = $query->result();

    // If no results
    if (empty($songs)) {
        return $this->output
            ->set_status_header(200)
            ->set_output(json_encode([
                'status' => true,
                'code' => 200,
                'message' => 'No songs found',
                'data' => []
            ]));
    }

    // Success response
    return $this->output
        ->set_status_header(200)
        ->set_output(json_encode([
            'status'  => true,
            'code'    => 200,
            'message' => 'Search results fetched successfully',
            'data'    => $songs
        ]));
}


public function add_favorite()
{
    header('Content-Type: application/json');

    // Extract token
    $authHeader = $this->input->get_request_header('Authorization', TRUE);
    $token = null;

    if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
    }

    $decoded = $this->verify_jwt($token);

    if (!$decoded) {
        return $this->output->set_output(json_encode([
            'status' => false,
            'code'   => 400,
            'message' => 'Invalid token',
            'data'   => null
        ]));
    }

    $user_id = (int)$decoded->data->id;

    // Input JSON
    $input = json_decode($this->input->raw_input_stream, true);
    $song_id = $input['song_id'] ?? 0;

    if (!$song_id) {
        return $this->output->set_output(json_encode([
            'status' => false,
            'code'   => 400,
            'message' => 'Song ID required',
            'data'   => null
        ]));
    }

    // Check song exists + active
    $song = $this->db
                ->where('id', $song_id)
                ->where('isActive', 1)
                ->get('songs')
                ->row();

    if (!$song) {
        return $this->output->set_output(json_encode([
            'status' => false,
            'code'   => 400,
            'message' => 'Song not found or inactive',
            'data'   => null
        ]));
    }

    // Check if already added
    $exists = $this->db->get_where('user_favorites', [
        'user_id' => $user_id,
        'song_id' => $song_id
    ])->row();

    if ($exists) {
        return $this->output->set_output(json_encode([
            'status' => false,
            'code'   => 400,
            'message' => 'Already in favorite list',
            'data'   => null
        ]));
    }

    // Add to favorites
    $this->db->insert('user_favorites', [
        'user_id' => $user_id,
        'song_id' => $song_id,
        'created_on' => date('Y-m-d H:i:s')
    ]);

    return $this->output->set_output(json_encode([
        'status' => true,
        'code'   => 200,
        'message' => 'Song added to favorites successfully',
        'data'   => null
    ]));
}

public function list_favorite_songs()
{
    header('Content-Type: application/json');

    // Read Bearer token
    $authHeader = $this->input->get_request_header('Authorization', TRUE);
    $token = null;

    if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
    }

    // Verify token
    $decoded = $this->verify_jwt($token);
    if (!$decoded || empty($decoded->data->id)) {
        return $this->output->set_output(json_encode([
            'status' => false,
            'code'   => 400,
            'message' => 'Invalid token',
            'data'   => null
        ]));
    }

    $user_id = (int)$decoded->data->id;

    // Fetch favorite songs including song_id
    $favorites = $this->db->select('songs.id as song_id, songs.title')
        ->from('user_favorites')
        ->join('songs', 'songs.id = user_favorites.song_id')
        ->where('user_favorites.user_id', $user_id)
        ->where('songs.isActive', 1)
        ->order_by('user_favorites.id', 'DESC')
        ->get()
        ->result();

    if (empty($favorites)) {
        return $this->output->set_output(json_encode([
            'status' => false,
            'code'   => 400,
            'message' => 'No favorite songs found',
            'data'   => []
        ]));
    }

    // Format result: return song id + title
    $song_list = array_map(function($row) {
        return [
            'song_id' => (int)$row->song_id,
            'title'   => $row->title
        ];
    }, $favorites);

    return $this->output->set_output(json_encode([
        'status' => true,
        'code'   => 200,
        'message' => 'Favorite songs fetched successfully',
        'data'   => $song_list
    ]));
}

public function profile()
{
    header('Content-Type: application/json');

    // Read Bearer token
    $authHeader = $this->input->get_request_header('Authorization', TRUE);
    $token = null;

    if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
    }

    // Verify token
    $decoded = $this->verify_jwt($token);
    if (!$decoded || empty($decoded->data->id)) {
        return $this->output->set_output(json_encode([
            'status'  => false,
            'code'    => 400,
            'message' => 'Invalid token',
            'data'    => null
        ]));
    }

    $user_id = (int)$decoded->data->id;

    // Fetch user from DB
    $user = $this->db->select('name, mobile')
                     ->where('id', $user_id)
                     ->get('users')
                     ->row_array();

    if (!$user) {
        return $this->output->set_output(json_encode([
            'status'  => false,
            'code'    => 400,
            'message' => 'User not found',
            'data'    => null
        ]));
    }

    // SUCCESS RESPONSE
    return $this->output->set_output(json_encode([
        'status'  => true,
        'code'    => 200,
        'message' => 'Profile fetched successfully',
        'data'    => [
            'name'   => $user['name'],
            'mobile' => $user['mobile']
        ]
    ]));
}


  private function verify_jwt($token)
{
    if (empty($token)) {
        $this->output
            ->set_status_header(401)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => false,
                'code' => 400,
                'message' => 'Authorization header missing or invalid',
                'data' => null
            ]))
            ->_display();
        exit;
    }

    try {
        $decoded = JWT::decode($token, new Key($this->jwt_secret, 'HS256'));

        // 🔹 Check if token is blacklisted
        $query = $this->db->get_where('token_blacklist', ['token' => $token]);
        if ($query->num_rows() > 0) {
            $this->output
                ->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'code' => 400,
                    'message' => 'Token has been invalidated. Please log in again.',
                    'data' => null
                ]))
                ->_display();
            exit;
        }

        return $decoded;

    } catch (Exception $e) {
        $this->output
            ->set_status_header(401)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => false,
                'code' => 400,
                'message' => 'Invalid token: ' . $e->getMessage(),
                'data' => null
            ]))
            ->_display();
        exit;
    }
}

        private function generate_jwt($user)
    {
        $payload = [
            'iss' => base_url(),
            'iat' => time(),
            'exp' => time() + (365 * 24 * 60 * 60), // 1 year expiry

            // 'exp' => time() + (3600 * 24), // 24 hours expiry
            // 'exp' => time() + 3600, // 1hours expiry


            'data' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email ?? '',
                'mobile'     => $user->mobile,
                'store_name' => $user->gym_name ?? '',
                'role'       => $user->role ?? '0'
            ]
        ];
        return JWT::encode($payload, $this->jwt_secret, 'HS256');
    }


}