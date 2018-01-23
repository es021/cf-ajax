<?php

add_action('wp_ajax_check_has_meta', 'check_has_meta');
add_action('wp_ajax_nopriv_check_has_meta', 'check_has_meta');

//return true or false --- 1 or 0 only
function check_has_meta() {
    $meta_key = (isset($_POST["meta_key"])) ? sanitize_text_field($_POST["meta_key"]) : null;
    $user_id = (isset($_POST["user_id"])) ? sanitize_text_field($_POST["user_id"]) : "";

    if (!Users::hasMeta($meta_key, $user_id)) {
        echo 0;
    } else {
        echo 1;
    }

    wp_die();
}

add_action('wp_ajax_wzs21_save_user_info', 'wzs21_save_user_info');
add_action('wp_ajax_nopriv_wzs21_save_user_info', 'wzs21_save_user_info');

function wzs21_save_user_info() {
    $user_id = (isset($_POST["user_id"])) ? sanitize_text_field($_POST["user_id"]) : null;

    if ($user_id == null) {
        $res['status'] = SiteInfo::STATUS_ERROR;
        echo json_encode($res);
        wp_die();
    }

    if (isset($_POST["user_role"]) && $_POST["user_role"] == SiteInfo::ROLE_RECRUITER) {
        $meta_keys = SiteInfo::USERMETA_REC_KEYS;
    } else {
        $meta_keys = SiteInfo::USERMETA_KEYS;
    }

    foreach (json_decode($meta_keys) as $meta_key) {
        if (isset($_POST[$meta_key])) {
            $val = $_POST[$meta_key];
            update_user_meta($user_id, $meta_key, $val);
            $res[$meta_key] = $val;
        }
    }

    $user_data = array();
    $user_data["ID"] = $user_id;
    foreach (json_decode(SiteInfo::USERS_KEYS) as $meta_key) {
        if (isset($_POST[$meta_key])) {
            $val = $_POST[$meta_key];
            $user_data[$meta_key] = $val;
            $res[$meta_key] = $val;
        }
    }

    if (count($user_data) > 1) {
        wp_update_user($user_data);
    }

    $res['status'] = SiteInfo::STATUS_SUCCESS;
    echo json_encode(myp_formatStringToHTMDeep($res));
    wp_die();
}

add_action('wp_ajax_wzs21_register_user', 'wzs21_register_user');
add_action('wp_ajax_nopriv_wzs21_register_user', 'wzs21_register_user');

function wzs21_register_user() {

    if (isset($_POST["user_data"])) {
        $user_data = $_POST["user_data"];

        //filter spam
        if ($user_data[SiteInfo::USERS_EMAIL . "_CONFIRM"] != "") {
            $res = array();
            $res['status'] = SiteInfo::STATUS_ERROR;
            $res['data'] = "Our system detected unusual behaviour.<br>Are you sure you are not a robot?";

            //save to log
            Logs::insert("Spam Registration", json_encode($user_data));
            echo json_encode($res);
            wp_die();
        }

        $user_data[SiteInfo::USERS_LOGIN] = $user_data[SiteInfo::USERS_EMAIL];
        $reg = new Register();
        //   X($user_data);

        $res = $reg->create_user($user_data);
        echo json_encode($res);
    }

    wp_die();
}

add_action('wp_ajax_wzs21_set_session', 'wzs21_set_session');
add_action('wp_ajax_nopriv_wzs21_set_session', 'wzs21_set_session');

function wzs21_set_session() {
    if (isset($_POST["temp_role"])) {
        $_SESSION["temp_role"] = $_POST["temp_role"];
        echo json_encode(SiteInfo::STATUS_SUCCESS);
    }
    wp_die();
}

add_action('wp_ajax_wzs21_reset_password', 'wzs21_reset_password');
add_action('wp_ajax_nopriv_wzs21_reset_password', 'wzs21_reset_password');

function wzs21_reset_password() {
    $res["status"] = SiteInfo::STATUS_ERROR;
    if (isset($_POST["new_" . SiteInfo::USERS_PASS])) {
        $new_user_pass = sanitize_text_field($_POST["new_" . SiteInfo::USERS_PASS]);

        //use old password
        if (isset($_POST[SiteInfo::USERS_ID]) && isset($_POST[SiteInfo::USERS_PASS])) {
            $user_id = sanitize_text_field($_POST[SiteInfo::USERS_ID]);
            $user_pass = sanitize_text_field($_POST[SiteInfo::USERS_PASS]);
            $user = get_userdata($user_id);

            if (!wp_check_password($user_pass, $user->user_pass)) {
                myp_ajax_return_error("Old password entered is not corrent.<br>Please try again.");
            } else {
                wp_set_password($new_user_pass, $user_id);
                $res["status"] = SiteInfo::STATUS_SUCCESS;
            }
        }

        //use activation link
        elseif (isset($_POST[SiteInfo::FIELD_TOKEN]) && isset($_POST[SiteInfo::FIELD_ID]) && isset($_POST[SiteInfo::FIELD_USER_ID])) {

            $user_id = sanitize_text_field($_POST[SiteInfo::FIELD_USER_ID]);
            $token = sanitize_text_field($_POST[SiteInfo::FIELD_TOKEN]);
            $ID = sanitize_text_field($_POST[SiteInfo::FIELD_ID]);

            $r = Users::reset_password_check_token($token, $ID, $user_id);
            if (is_wp_error($r)) {
                myp_ajax_return_error($r->get_error_message());
            } else {
                wp_set_password($new_user_pass, $user_id);
                $res["status"] = SiteInfo::STATUS_SUCCESS;

                //set expired flag to reset password table
                if (!Users::reset_password_set_expired($ID)) {
                    myp_error_log("wzs21_reset_password", "Failed to set expired flag on reset password on row ID : $ID");
                }
            }
        }
    }

    echo json_encode(myp_formatStringToHTMDeep($res));
    wp_die();
}

add_action('wp_ajax_wzs21_request_password_reset', 'wzs21_request_password_reset');
add_action('wp_ajax_nopriv_wzs21_request_password_reset', 'wzs21_request_password_reset');

function wzs21_request_password_reset() {
    global $wpdb;
    $user_email = (isset($_POST[SiteInfo::USERS_EMAIL])) ? sanitize_text_field($_POST[SiteInfo::USERS_EMAIL]) : null;
    if (!$user_email) {
        myp_ajax_return_error("Invalid email");
    }

    $user = get_user_by("email", $user_email);
    if (!$user) {
        myp_ajax_return_error("This email does not belong to any registered users");
    }

    // create new pass_reset data
    $token = wp_generate_password(30);
    $data = array(
        SiteInfo::FIELD_USER_ID => $user->ID,
        SiteInfo::FIELD_TOKEN => $token
    );
    $format = array('%d', '%s');
    if ($wpdb->insert(SiteInfo::TABLE_PASSWORD_RESET, $data, $format)) {
        $id = $wpdb->insert_id;
    } else {
        myp_ajax_return_error("Failed to generate password reset link for unknown reason");
    }

    //generate link
    $param = array(
        "token" => $token,
        "ID" => $id,
        "user_id" => $user->ID
    );
    $link = myp_generate_link(SiteInfo::PAGE_RESET_PASSWORD, $param);

    //send link to requester
    $email_data = array(
        SiteInfo::USERMETA_FIRST_NAME => $user->first_name,
        "link" => $link
    );
    if (!myp_send_email($user_email, $email_data, SiteInfo::EMAIL_TYPE_RESET_PASSWORD)) {
        myp_ajax_return_error("Failed to send email for unknown reason");
    }

    $res = array(
        "status" => SiteInfo::STATUS_SUCCESS,
        SiteInfo::USERS_EMAIL => $user_email
    );

    echo json_encode($res);
    wp_die();
}
