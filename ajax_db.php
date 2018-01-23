<?php

add_action('wp_ajax_wzs21_insert_db', 'wzs21_insert_db');
add_action('wp_ajax_nopriv_wzs21_insert_db', 'wzs21_insert_db');

function wzs21_insert_db() {
    global $wpdb;
    $res = array();
    $table = (isset($_POST['table'])) ? sanitize_text_field($_POST['table']) : null;
    $data = $_POST;
    unset($data["action"]);
    unset($data["table"]);

    switch ($table) {
        case Vacancy::TABLE_NAME :

            if ($wpdb->insert(Vacancy::TABLE_NAME, $data)) {
                $res["status"] = SiteInfo::STATUS_SUCCESS;
                $res["data"] = $data;
            } else {
                ajax_return(SiteInfo::STATUS_ERROR, "Failed to create new vacancy.");
            }
            break;

        case Session::TABLE_NAME :
            Session::createSession($data);
            break;

        case SessionNote::TABLE_NAME :
            if ($wpdb->insert(SessionNote::TABLE_NAME, $data)) {
                $res["status"] = SiteInfo::STATUS_SUCCESS;

                $res["data"] = $wpdb->insert_id;
            } else {
                ajax_return(SiteInfo::STATUS_ERROR, "Failed to add new note.");
            }
            break;

        case PreScreen::TABLE_NAME :
            if ($wpdb->insert(PreScreen::TABLE_NAME, $data)) {
                $res["status"] = SiteInfo::STATUS_SUCCESS;

                $res["data"] = $wpdb->insert_id;
            } else {
                ajax_return(SiteInfo::STATUS_ERROR, "Failed to add new prescreen.");
            }
            break;

        case InQueue::TABLE_NAME :
            InQueue::startQueue($data);
            break;
        default:

            if ($wpdb->insert($table, $data)) {
                $insert_id = $wpdb->insert_id;
                ajax_return(SiteInfo::STATUS_SUCCESS, $insert_id);
            } else {
                ajax_return(SiteInfo::STATUS_ERROR, $wpdb->last_error);
            }
            break;
    }

    echo json_encode(myp_formatStringToHTMDeep($res));
    wp_die();
}

add_action('wp_ajax_wzs21_delete_db', 'wzs21_delete_db');
add_action('wp_ajax_nopriv_wzs21_delete_db', 'wzs21_delete_db');

function wzs21_delete_db() {
    global $wpdb;
    $res = array();
    $table = (isset($_POST['table'])) ? sanitize_text_field($_POST['table']) : null;
    $data = $_POST;
    unset($data["action"]);
    unset($data["table"]);

    switch ($table) {
        case SessionNote::TABLE_NAME:
            $session_note_id = $data[SessionNote::COL_ID];
            unset($data[SessionNote::COL_ID]);
            $id_arr = array(SessionNote::COL_ID => $session_note_id);
            if ($wpdb->delete(SessionNote::TABLE_NAME, $id_arr)) {
                $res["status"] = SiteInfo::STATUS_SUCCESS;
                $res["data"] = $data;
            } else {
                ajax_return(SiteInfo::STATUS_ERROR, "Failed to delete session note information.");
            }
            break;
        case Vacancy::TABLE_NAME:
            $_id = $data[Vacancy::COL_ID];
            unset($data[Vacancy::COL_ID]);
            $id_arr = array(Vacancy::COL_ID => $_id);
            $res = wzs21_delete_db_general($wpdb, Vacancy::TABLE_NAME, $data, $id_arr);
            break;
        default :
            ajax_return(SiteInfo::STATUS_ERROR, "Dataset to delete not specified in the request.");
            break;
    }

    echo json_encode(myp_formatStringToHTMDeep($res));
    wp_die();
}

function wzs21_delete_db_general($wpdb, $table_name, $data, $id_arr) {
    $res = array();
    if ($wpdb->delete($table_name, $id_arr)) {
        $res["status"] = SiteInfo::STATUS_SUCCESS;
        $res["data"] = $data;
    } else {
        ajax_return(SiteInfo::STATUS_ERROR, "Failed to delete $table_name information.");
    }

    return $res;
}

add_action('wp_ajax_wzs21_update_db', 'wzs21_update_db');
add_action('wp_ajax_nopriv_wzs21_update_db', 'wzs21_update_db');

function wzs21_update_db() {
    global $wpdb;
    $res = array();
    $table = (isset($_POST['table'])) ? sanitize_text_field($_POST['table']) : null;
    $data = $_POST;
    unset($data["action"]);
    unset($data["table"]);

    switch ($table) {
        case ZoomMeetings::TABLE_NAME :
            $zoom_meeting_id = $data[ZoomMeetings::COL_ZOOM_MEETING_ID];
            $zoom_host_id = $data[ZoomMeetings::COL_ZOOM_HOST_ID];
            unset($data[ZoomMeetings::COL_ZOOM_MEETING_ID]);
            $where = array(ZoomMeetings::COL_ZOOM_MEETING_ID => $zoom_meeting_id,
                ZoomMeetings::COL_ZOOM_HOST_ID => $zoom_host_id);
            if ($wpdb->update(ZoomMeetings::TABLE_NAME, $data, $where)) {
                $res["status"] = SiteInfo::STATUS_SUCCESS;
                $res["data"] = $data;
            } else {
                ajax_return(SiteInfo::STATUS_ERROR, "Failed to update zoom meetings information.");
            }
            break;
        case SessionNote::TABLE_NAME:
            $session_note_id = $data[SessionNote::COL_ID];
            unset($data[SessionNote::COL_ID]);
            $where = array(SessionNote::COL_ID => $session_note_id);
            if ($wpdb->update(SessionNote::TABLE_NAME, $data, $where)) {
                $res["status"] = SiteInfo::STATUS_SUCCESS;
                $res["data"] = $data;
            } else {
                ajax_return(SiteInfo::STATUS_ERROR, "Failed to update session note information.");
            }
            break;
        case Session::TABLE_NAME:
            $session_id = $data[Session::COL_ID];
            unset($data[Session::COL_ID]);
            if (empty($data)) {
                ajax_return(SiteInfo::STATUS_SUCCESS, "No Changes");
            }
            $where = array(Session::COL_ID => $session_id);
            if ($wpdb->update(Session::TABLE_NAME, $data, $where)) {
                $res["status"] = SiteInfo::STATUS_SUCCESS;
                $res["data"] = $data;
            } else {
                ajax_return(SiteInfo::STATUS_ERROR, "Failed to update session information.");
            }

            break;

        case InQueue::TABLE_NAME:
            $inqueue_id = $data[InQueue::COL_ID];
            unset($data[InQueue::COL_ID]);
            if (empty($data)) {
                ajax_return(SiteInfo::STATUS_SUCCESS, "No Changes");
            }
            $where = array(InQueue::COL_ID => $inqueue_id);
            if ($wpdb->update(InQueue::TABLE_NAME, $data, $where)) {
                $res["status"] = SiteInfo::STATUS_SUCCESS;
                $res["data"] = $data;
            } else {
                ajax_return(SiteInfo::STATUS_ERROR, "Failed to update queue information.");
            }

            break;

        case PreScreen::TABLE_NAME:
            unset($data["pre_screen_id"]);
            $data[PreScreen::COL_UPDATED_BY] = get_current_user_id();
            if (empty($data)) {
                ajax_return(SiteInfo::STATUS_SUCCESS, "No Changes");
            }
            $where = array(PreScreen::COL_ID => $_POST['pre_screen_id']);
            if ($wpdb->update(PreScreen::TABLE_NAME, $data, $where)) {
                $res["status"] = SiteInfo::STATUS_SUCCESS;
                $res["data"] = $data;
            } else {
                ajax_return(SiteInfo::STATUS_ERROR, "Failed to update pre-screen information.");
            }
            break;

        case Company::TABLE_NAME :
            unset($data["company_id"]);
            if (empty($data)) {
                ajax_return(SiteInfo::STATUS_SUCCESS, "No Changes");
            }
            $where = array(Company::COL_ID => $_POST['company_id']);

            if ($wpdb->update(Company::TABLE_NAME, $data, $where)) {
                $res["status"] = SiteInfo::STATUS_SUCCESS;
                $res["data"] = $data;
            } else {
                ajax_return(SiteInfo::STATUS_ERROR, "Failed to update company information.");
            }
            break;

        case Vacancy::TABLE_NAME :
            unset($data["vacancy_id"]);
            if (empty($data)) {
                ajax_return(SiteInfo::STATUS_SUCCESS, "No Changes");
            }
            $where = array(Vacancy::COL_ID => $_POST['vacancy_id']);

            if ($wpdb->update(Vacancy::TABLE_NAME, $data, $where)) {
                $res["status"] = SiteInfo::STATUS_SUCCESS;
                $res["data"] = $data;
            } else {
                ajax_return(SiteInfo::STATUS_ERROR, "Failed to update vacancy information.");
            }
            break;
        case ResumeDrop::TABLE_NAME:
            unset($data["resume_drop_id"]);
            if (empty($data)) {
                ajax_return(SiteInfo::STATUS_SUCCESS, "No Changes");
            }
            $where = array(ResumeDrop::COL_ID => $_POST['resume_drop_id']);

            if ($wpdb->update(ResumeDrop::TABLE_NAME, $data, $where)) {
                $res["status"] = SiteInfo::STATUS_SUCCESS;
                $res["data"] = $data;
            } else {
                ajax_return(SiteInfo::STATUS_ERROR, "Something went wrong. Failed to message.");
            }
            break;
    }

    echo json_encode(myp_formatStringToHTMDeep($res));
    wp_die();
}
