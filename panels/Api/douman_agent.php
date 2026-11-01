<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../request.php';
date_default_timezone_set('Asia/Tehran');

function get_panel_list_douman(array $panel)
{
    $url = $panel['url_panel'] . '?actions=list_panel';
    $headers = array('accept: application/json');
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    return $req->get();
}

function create_user_douman(array $panel, int $data_limit_gb, int $expire_days, string $username)
{
    $url = $panel['url_panel'];
    $headers = array('accept: application/json');
    $data = json_encode(array(
        'actions' => "user_create",
        'username' => $username,
        'data_limit_gb' => $data_limit_gb,
        'expire_days' => $expire_days,
        'panel_id' => $panel['inbounds']
    ));
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    return $req->post($data);
}

function get_user_data_douman(array $panel, string $username)
{
    $url = $panel['url_panel'] . '?actions=get_user_data&username=' . urlencode($username);
    $headers = array('accept: application/json');
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    return $req->get();
}

function add_time_service_douman(array $panel, int $expire_days, string $username)
{
    $url = $panel['url_panel'];
    $headers = array('accept: application/json');
    $data = json_encode(array(
        'actions' => "add_time_service",
        'username' => $username,
        'time_day' => $expire_days,
    ));
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    return $req->put($data);
}

function add_volume_service_douman(array $panel, int $data_limit_gb, string $username)
{
    $url = $panel['url_panel'];
    $headers = array('accept: application/json');
    $data = json_encode(array(
        'actions' => "add_volume_service",
        'username' => $username,
        'data_limit_gb' => $data_limit_gb,
    ));
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    return $req->put($data);
}

function extend_service_douman(array $panel, int $data_limit_gb, int $time_day, string $username)
{
    $url = $panel['url_panel'];
    $headers = array('accept: application/json');
    $data = json_encode(array(
        'actions' => "extend_service",
        'username' => $username,
        'data_limit_gb' => $data_limit_gb,
        'time_day' => $time_day,
    ));
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    return $req->put($data);
}

function remove_service_douman(array $panel, string $username)
{
    $url = $panel['url_panel'];
    $headers = array('accept: application/json');
    $data = json_encode(array(
        'actions' => "user_delete",
        'username' => $username,
    ));
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    return $req->delete($data);
}

function revoke_service_douman(array $panel, string $username)
{
    $url = $panel['url_panel'];
    $headers = array('accept: application/json');
    $data = json_encode(array(
        'actions' => "change_link",
        'username' => $username,
    ));
    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($panel['password_panel']);
    return $req->put($data);
}

// Aliases for legacy
if (!function_exists('create_user_mirza')) {
    function create_user_mirza(array $panel, int $data_limit_gb, int $expire_days, string $username) {
        return create_user_douman($panel, $data_limit_gb, $expire_days, $username);
    }
    function get_user_data_mirza(array $panel, string $username) {
        return get_user_data_douman($panel, $username);
    }
    function add_time_service_mirza(array $panel, int $expire_days, string $username) {
        return add_time_service_douman($panel, $expire_days, $username);
    }
    function add_volume_service_mirza(array $panel, int $data_limit_gb, string $username) {
        return add_volume_service_douman($panel, $data_limit_gb, $username);
    }
    function extend_service_mirza(array $panel, int $data_limit_gb, int $time_day, string $username) {
        return extend_service_douman($panel, $data_limit_gb, $time_day, $username);
    }
    function remove_service_mirza(array $panel, string $username) {
        return remove_service_douman($panel, $username);
    }
    function revoke_service_mirza(array $panel, string $username) {
        return revoke_service_douman($panel, $username);
    }
}
