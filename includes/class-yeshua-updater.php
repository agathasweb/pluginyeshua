<?php
if (!defined('ABSPATH')) {
    exit;
}

class Yeshua_Updater {
    private $slug;
    private $plugin_data;
    private $username;
    private $repo;
    private $plugin_file;
    private $github_token;
    private $github_api_result;

    public function __construct($plugin_file, $github_username, $github_repo, $github_token = '') {
        $this->plugin_file = $plugin_file;
        $this->username = $github_username;
        $this->repo = $github_repo;
        $this->github_token = $github_token;
        $this->slug = plugin_basename($this->plugin_file); // ex: yeshua-conversoes/yeshua-conversoes.php

        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_updates']);
        add_filter('plugins_api', [$this, 'plugin_popup'], 10, 3);
        add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);
        add_filter('upgrader_source_selection', [$this, 'fix_folder_name'], 10, 4);
        add_filter('http_request_args', [$this, 'add_github_auth_header'], 10, 2);
    }

    private function get_plugin_data() {
        if (isset($this->plugin_data)) {
            return $this->plugin_data;
        }
        
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $this->plugin_data = get_plugin_data($this->plugin_file);
        return $this->plugin_data;
    }

    private function get_repo_release_info() {
        if (!empty($this->github_api_result)) {
            return $this->github_api_result;
        }

        $url = "https://api.github.com/repos/{$this->username}/{$this->repo}/releases/latest";
        
        $args = [
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json'
            ]
        ];

        if (!empty($this->github_token)) {
            $args['headers']['Authorization'] = "token {$this->github_token}";

    public function fix_folder_name($source, $remote_source, $upgrader, $hook_extra = null) {
        if (isset($hook_extra['plugin']) && $hook_extra['plugin'] === $this->slug) {
            global $wp_filesystem;
            $correct_folder = dirname($this->slug); // yeshua-conversoes
            $new_source = trailingslashit($remote_source) . $correct_folder . '/';
            
            if (basename($source) !== $correct_folder) {
                if ($wp_filesystem->move($source, $new_source)) {
                    return $new_source;
                }
            }
        }
        return $source;
    }

        }

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $this->github_api_result = json_decode($body);

        return $this->github_api_result;
    }

    public function check_for_updates($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $release_info = $this->get_repo_release_info();

        if (!$release_info) {
            return $transient;
        }

        $plugin_data = $this->get_plugin_data();
        $do_update = version_compare($release_info->tag_name, $plugin_data['Version'], '>');

        if ($do_update) {
            $package = $release_info->zipball_url;
            
            $obj = new stdClass();
            $obj->slug = dirname($this->slug); // Use folder name as slug for properties
            $obj->plugin = $this->slug;
            $obj->new_version = $release_info->tag_name;
            $obj->url = $release_info->html_url;
            $obj->package = $package;
            $obj->icons = []; 
            $obj->banners = [];

            $transient->response[$this->slug] = $obj;
        }

        return $transient;
    }

    public function plugin_popup($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        // Check if slug matches folder name or full path
        if (!isset($args->slug) || ($args->slug !== $this->slug && $args->slug !== dirname($this->slug))) {
            return $result;
        }
        
        $release_info = $this->get_repo_release_info();
        if (!$release_info) {
            return $result;
        }

        $plugin_data = $this->get_plugin_data();

        $obj = new stdClass();
        $obj->name = $plugin_data['Name'];
        $obj->slug = dirname($this->slug);
        $obj->version = $release_info->tag_name;
        $obj->author = $plugin_data['AuthorName'];
        $obj->homepage = $plugin_data['PluginURI'];
        $obj->requires = '5.6'; 
        $obj->tested = '6.4'; 
        $obj->downloaded = 0;
        $obj->last_updated = $release_info->published_at;
        $obj->sections = [
            'description' => $plugin_data['Description'],
            'changelog' => nl2br($release_info->body) 
        ];
        $obj->download_link = $release_info->zipball_url;

        return $obj;
    }

    public function after_install($response, $hook_extra, $result) {
        return $response;
    }
    
    public function add_github_auth_header($args, $url) {
        if (empty($this->github_token)) {
            return $args;
        }
        if (strpos($url, 'api.github.com') !== false && strpos($url, $this->repo) !== false) {
             $args['headers']['Authorization'] = "token {$this->github_token}";
        }
        return $args;
    }
}
