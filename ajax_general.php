<?php

/* * ****** Panel Load Function ************************** */
add_action('wp_ajax_wzs21_load_page', 'wzs21_load_page');
add_action('wp_ajax_nopriv_wzs21_load_page', 'wzs21_load_page');

//param $query and optional arguments
function wzs21_load_page() {
    if (isset($_POST["file_path"])) {
        $file_path = $_POST["file_path"] . ".php";

        //this data can be use to load up page
        if (isset($_POST["data"])) {
            $data = $_POST["data"];
        }

        if (!(include_once $file_path)) {
            echo "<small>File Not Found</small><br>($file_path)";
        }
    } else {
        echo "No File Path Provided";
    }
    wp_die();
}

add_action('wp_ajax_wzs21_upload_file', 'wzs21_upload_file');
add_action('wp_ajax_nopriv_wzs21_upload_file', 'wzs21_upload_file');

function wzs21_upload_file() {
    $res = array();

    if (isset($_POST['user_id']) || isset($_POST['company_id'])) {
        if (isset($_FILES[SiteInfo::FILE_INDEX_IMAGE])) {
            $file = $_FILES[SiteInfo::FILE_INDEX_IMAGE];
            $type = explode("/", $file['type'])[1];

            // for user image
            if (isset($_POST['user_id'])) {
                $user_id = sanitize_text_field($_POST['user_id']);
                $save_filename = "user_{$user_id}_profile_image.{$type}";
                //do the uploading here
                $temp = wp_upload_bits($save_filename, NULL, file_get_contents($file['tmp_name']));

                if (!$temp['error']) {
                    $res[SiteInfo::USERMETA_IMAGE_URL] = $temp['url'];
                }
            }

            //for company image
            else if (isset($_POST['company_id'])) {
                $company_id = sanitize_text_field($_POST['company_id']);
                $save_filename = "company_{$company_id}_profile_image.{$type}";
                //do the uploading here
                $temp = wp_upload_bits($save_filename, NULL, file_get_contents($file['tmp_name']));

                if (!$temp['error']) {
                    $res[Company::COL_IMG_URL] = $temp['url'];
                }
            }
        } else {
            $res[SiteInfo::USERMETA_IMAGE_URL] = "";
        }

        if (isset($_FILES[SiteInfo::FILE_INDEX_RESUME])) {
            $file = $_FILES[SiteInfo::FILE_INDEX_RESUME];
            $type = explode("/", $file['type'])[1];
            $user_id = sanitize_text_field($_POST['user_id']);
            $save_filename = "user_{$user_id}_resume.{$type}";
            //do the uploading here
            $temp = wp_upload_bits($save_filename, NULL, file_get_contents($file['tmp_name']));

            if (!$temp['error']) {
                $res[SiteInfo::USERMETA_RESUME_URL] = $temp['url'];
            }
        } else {
            $res[SiteInfo::USERMETA_RESUME_URL] = "";
        }
    } else { //for public upload
        if (isset($_FILES[SiteInfo::FILE_INDEX_RESUME_DROP])) {
            $file = $_FILES[SiteInfo::FILE_INDEX_RESUME_DROP];

            $email = sanitize_text_field($_POST['email']);

            $save_filename = date("Ymd") . "_" . date("His") . "_{$file['name']}";
            //X($save_filename);
            //do the uploading here
            $temp = wp_upload_bits($save_filename, NULL, file_get_contents($file['tmp_name']));

            if (!$temp['error']) {
                $res["status"] = SiteInfo::STATUS_SUCCESS;
                $res["data"] = $temp['url'] . " " . $email;
            } else {
                //X($temp);
                $res["status"] = SiteInfo::STATUS_ERROR;
                $res["data"] = "Upload failed";
            }
        }
    }

    echo json_encode(myp_formatStringToHTMDeep($res));
    wp_die();
}
