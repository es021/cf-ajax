<?php

//*** Include all ajax function **************/
include_once 'ajax_custom_query.php';
include_once 'ajax_db.php';
include_once 'ajax_general.php';
include_once 'ajax_user.php';
include_once 'ajax_zoom.php';
include_once 'app/ajax_external.php';

add_filter( 'allowed_http_origins', 'add_allowed_origins' );
function add_allowed_origins( $origins ) {
    $origins[] = 'http://localhost:8080';
    return $origins;
}

//**** Ajax Helper ****************************/
function ajax_return($status, $data) {
    $res = array();

    $res["status"] = $status;
    $res["data"] = $data;

    echo json_encode($res);
    wp_die();
}

function myp_ajax_return_error($error_message) {
    $res = array(
        "status" => SiteInfo::STATUS_ERROR,
        "data" => $error_message
    );
    echo json_encode($res);
    wp_die();
}

function escapeDataString($res) {
    //X($res);

    if ($res["status"] == SiteInfo::STATUS_SUCCESS) {
        $res["data"] = stripslashes_deep($res["data"]);
    }
    //X($res);
    return $res;
}

function getCompanyCareerFair($wpdb, $table) {
    $student_id = (isset($_POST['student_id'])) ? sanitize_text_field($_POST['student_id']) : null;
    $status = (isset($_POST['status'])) ? sanitize_text_field($_POST['status']) : null;

    $com_id_col = "";
    $sql = "";
    switch ($table) {
        case PreScreen::TABLE_NAME:
            $sql = PreScreen::query_get_other_by_entity(PreScreen::COL_STUDENT_ID, $student_id, $status);
            $com_id_col = PreScreen::COL_COMPANY_ID;
            break;
        case InQueue::TABLE_NAME:
            $sql = InQueue::query_get_other_by_entity(InQueue::COL_STUDENT_ID, $student_id, $status);
            $com_id_col = InQueue::COL_COMPANY_ID;
            break;
        default:
            return false;
    }

    $com_ids_obj = $wpdb->get_results($sql, ARRAY_A);
    //get company information

    $company_ids = array();
    foreach ($com_ids_obj as $id) {
        $company_ids[] = $id[$com_id_col];
    }
    $select = array(Company::COL_ID
        , Company::COL_NAME
        , Company::COL_IMG_URL
        , Company::COL_IMG_POSITION
        , Company::COL_IMG_SIZE);

    $sql = Company::query_get_companies_detail($company_ids, $select);
    $companies = $wpdb->get_results($sql, ARRAY_A);

    $res = array();
    if (!empty($companies)) {
        $id_key_coms = array();
        foreach ($companies as $c) {
            $id_key_coms[$c[Company::COL_ID]] = $c;
        }
        $res["status"] = SiteInfo::STATUS_SUCCESS;
        $res["data"] = $com_ids_obj;
        $res["companies"] = $id_key_coms;
    } else {
        $res["status"] = SiteInfo::STATUS_ERROR;
        $res["data"] = "No Company";
    }

    return $res;
}

function getStudentCareerFair($wpdb, $table) {
    $company_id = (isset($_POST['company_id'])) ? sanitize_text_field($_POST['company_id']) : null;
    $status = (isset($_POST['status'])) ? sanitize_text_field($_POST['status']) : null;

    $student_id_col = "";
    $sql = "";
    switch ($table) {
        case PreScreen::TABLE_NAME:
            $sql = PreScreen::query_get_other_by_entity(PreScreen::COL_COMPANY_ID, $company_id, $status);
            $student_id_col = PreScreen::COL_STUDENT_ID;
            break;
        case InQueue::TABLE_NAME:
            $sql = InQueue::query_get_other_by_entity(InQueue::COL_COMPANY_ID, $company_id, $status);
            $student_id_col = InQueue::COL_STUDENT_ID;
            break;
        default:
            return false;
    }

    $student_ids = $wpdb->get_results($sql, ARRAY_A);

    if (!empty($student_ids)) {
//get student information
        $where = array();
        foreach ($student_ids as $id) {
            $where[] = SiteInfo::USERS_ID . " = '{$id[$student_id_col]}'";
        }

        $select = array(
            SiteInfo::USERS_ID
            , SiteInfo::USERMETA_FIRST_NAME
            , SiteInfo::USERMETA_LAST_NAME
            , SiteInfo::USERMETA_IMAGE_URL
            , SiteInfo::USERMETA_IMAGE_POSITION
            , SiteInfo::USERMETA_IMAGE_SIZE);
        $students = Users::get_users(SiteInfo::ROLE_STUDENT, 1, 99, $select, $where);

        $res = array();
        if (!empty($students)) {
            $id_key_students = array();
            foreach ($students as $s) {
                $s = (array) $s;
                $id_key_students[$s[SiteInfo::USERS_ID]] = $s;
            }
            $res["status"] = SiteInfo::STATUS_SUCCESS;
            $res["data"] = $student_ids;
            $res["students"] = $id_key_students;
            return $res;
        }
    }

    $res["status"] = SiteInfo::STATUS_ERROR;
    $res["data"] = "No Student";
    return $res;
}
