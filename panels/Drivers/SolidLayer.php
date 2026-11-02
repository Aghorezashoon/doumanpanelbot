<?php

class SolidLayerPanelDriver extends BasePanelDriver
{
    public function create(array $vars)
    {
        $Get_Data_Panel = $vars['Get_Data_Panel'];
        $data_limit = $vars['data_limit'];
        $usernameC = $vars['usernameC'];
        $expire = $vars['expire'];
        $note = $vars['note'] ?? '';
        $Get_Data_Product = $vars['Get_Data_Product'];
        $Output = $vars['Output'] ?? [];
        $invoice = $vars['invoice'] ?? false;
        $domainhosts = $vars['domainhosts'] ?? '';

        $connect = add_user_solidlayer(
            $Get_Data_Panel['name_panel'],
            $data_limit,
            $usernameC,
            $expire,
            $note,
            $Get_Data_Product['name_product'] ?? null
        );

        if (!empty($connect['error'])) {
            return [
                'status' => 'Unsuccessful',
                'msg' => $connect['error']
            ];
        }

        if (empty($connect['status']) || $connect['status'] >= 400) {
            $msg = $connect['data']['message'] ?? $connect['body'] ?? 'HTTP ' . ($connect['status'] ?? 0);
            return [
                'status' => 'Unsuccessful',
                'msg' => is_string($msg) ? $msg : json_encode($msg, JSON_UNESCAPED_UNICODE)
            ];
        }

        $items = $connect['data'] ?? [];
        $sub = is_array($items) && isset($items[0]) ? $items[0] : $items;

        $subUrl = $sub['subscription_link'] ?? '';
        if ($invoice != false && !empty($domainhosts)) {
            $subUrl = "https://{$domainhosts}/sub/" . $invoice['id_invoice'];
        }

        // Fetch config links
        $links = get_user_links_solidlayer($Get_Data_Panel['name_panel'], $usernameC);

        return [
            'status' => 'successful',
            'username' => $usernameC,
            'subscription_url' => $subUrl,
            'configs' => $links,
        ];
    }

    public function read(array $vars)
    {
        $username = $vars['username'];
        $Get_Data_Panel = $vars['Get_Data_Panel'];
        $invoice = $vars['invoice'] ?? false;
        $domainhosts = $vars['domainhosts'] ?? '';

        $res = get_user_solidlayer($Get_Data_Panel['name_panel'], $username);
        if (!empty($res['error'])) {
            return [
                'status' => 'Unsuccessful',
                'msg' => $res['error']
            ];
        }

        if (empty($res['status']) || $res['status'] >= 400) {
            return [
                'status' => 'Unsuccessful',
                'msg' => 'HTTP ' . ($res['status'] ?? 0)
            ];
        }

        $items = $res['data']['items'] ?? [];
        if (empty($items) || !is_array($items)) {
            return [
                'status' => 'Unsuccessful',
                'msg' => 'User not found in panel'
            ];
        }

        $user = $items[0];
        $subUrl = $user['subscription_link'] ?? '';
        if ($invoice != false && !empty($domainhosts)) {
            $subUrl = "https://{$domainhosts}/sub/" . $invoice['id_invoice'];
        }

        // Determine user status
        $status = 'active';
        $now = time();
        if (empty($user['enabled'])) {
            $status = 'disabled';
        } elseif (!empty($user['limit_expire']) && $user['limit_expire'] > 0 && $now > $user['limit_expire']) {
            $status = 'expired';
        } elseif (!empty($user['limit_usage']) && $user['limit_usage'] > 0 && ($user['total_usage'] ?? 0) >= $user['limit_usage']) {
            $status = 'limited';
        }

        // Fetch links
        $links = get_user_links_solidlayer($Get_Data_Panel['name_panel'], $username);

        return [
            'status' => $status,
            'username' => $user['username'] ?? $username,
            'data_limit' => $user['limit_usage'] ?? 0,
            'used_traffic' => $user['total_usage'] ?? 0,
            'expire' => $user['limit_expire'] ?? 0,
            'subscription_url' => $subUrl,
            'links' => $links,
            'online_at' => !empty($user['last_online_at']) ? $user['last_online_at'] : null,
            'sub_updated_at' => !empty($user['updated_at']) ? $user['updated_at'] : null,
        ];
    }

    public function modify(array $vars)
    {
        $username = $vars['username'];
        $name_panel = $vars['name_panel'];
        $config = $vars['config'] ?? [];

        return modify_user_solidlayer($name_panel, $username, $config);
    }

    public function remove(array $vars)
    {
        $username = $vars['username'];
        $name_panel = $vars['name_panel'];

        $res = delete_user_solidlayer($name_panel, $username);
        return [
            'status' => $res['status'] ? 'successful' : 'Unsuccessful',
            'msg' => $res['msg'] ?? ''
        ];
    }

    public function resetUsage(array $vars)
    {
        $username = $vars['username'];
        $name_panel = $vars['name_panel'];

        return reset_user_solidlayer($name_panel, $username);
    }

    public function revokeSub(array $vars)
    {
        $username = $vars['username'];
        $name_panel = $vars['name_panel'];

        $res = revoke_sub_solidlayer($name_panel, $username);
        if ($res['status'] >= 200 && $res['status'] < 300) {
            $links = get_user_links_solidlayer($name_panel, $username);
            return [
                'status' => 'successful',
                'username' => $username,
                'configs' => $links
            ];
        }
        return [
            'status' => 'Unsuccessful',
            'msg' => $res['error'] ?? 'Revoke failed'
        ];
    }

    public function changeStatus(array $vars)
    {
        $username = $vars['username'];
        $name_panel = $vars['name_panel'];
        $DataUserOut = $vars['DataUserOut'] ?? [];

        $newStatus = ($DataUserOut['status'] ?? '') === 'active' ? 'disabled' : 'active';
        $res = change_status_solidlayer($name_panel, $username, $newStatus);

        return [
            'status' => $res['status'] ? 'successful' : 'Unsuccessful',
            'msg' => $res['msg'] ?? ''
        ];
    }

    public function extendService(array $vars)
    {
        $username = $vars['username'];
        $panel = $vars['panel'];
        $data_limit_new = $vars['data_limit_new'];
        $time_new = $vars['time_new'];

        $update = [
            'limit_usage' => $data_limit_new,
            'limit_expire' => $time_new,
            'enabled' => true
        ];

        return modify_user_solidlayer($panel['name_panel'], $username, $update);
    }

    public function extraVolume(array $vars)
    {
        $username_account = $vars['username_account'];
        $panel = $vars['panel'];
        $new_limit = $vars['new_limit'];

        return modify_user_solidlayer($panel['name_panel'], $username_account, [
            'limit_usage' => $new_limit,
            'enabled' => true
        ]);
    }

    public function extraTime(array $vars)
    {
        $username_account = $vars['username_account'];
        $panel = $vars['panel'];
        $new_time = $vars['new_time'];

        return modify_user_solidlayer($panel['name_panel'], $username_account, [
            'limit_expire' => $new_time,
            'enabled' => true
        ]);
    }
}
