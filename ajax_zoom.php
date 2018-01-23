<?php

add_action('wp_ajax_wzs21_zoom_ajax', 'wzs21_zoom_ajax');
add_action('wp_ajax_nopriv_wzs21_zoom_ajax', 'wzs21_zoom_ajax');

//param $query and optional arguments
function wzs21_zoom_ajax() {
    $zoom = new ZoomAPI();
    $query = $_POST["query"];

    switch ($query) {
        case "is_meeting_expired":
            $zoom_meeting_id = $_POST[ZoomMeetings::COL_ZOOM_MEETING_ID];
            $zoom_host_id = $_POST[ZoomMeetings::COL_ZOOM_HOST_ID];

            //check local db first
            $res = ZoomMeetings::isMeetingExpired($zoom_meeting_id, $zoom_host_id);

            //not set check with zoom api
            if ($res == "") {
                $res = $zoom->isMeetingExpired($zoom_meeting_id, $zoom_host_id);
                if ($res) {
                    $res = "1";
                    //update dbase
                    ZoomMeetings::updateIsExpired($res, $zoom_meeting_id, $zoom_host_id);
                } else {
                    $res = "0";
                }
            }
            break;

        case "create_meeting":
            $host_id = $_POST["host_id"];
            $session_id = $_POST["session_id"];
            $host = get_userdata($host_id);
            $res = array();

            //get zoom user id
            $zoom_id = get_user_meta($host_id, SiteInfo::USERMETA_REC_ZOOM_ID, true);

            if (empty($zoom_id)) { //if not exist create one
                $zoom_user = $zoom->custCreateAUser($host->user_email);
                if ($zoom_user != "") {
                    $zoom_user = json_decode($zoom_user);
                    $zoom_id = $zoom_user->id;
                    update_user_meta($host_id, SiteInfo::USERMETA_REC_ZOOM_ID, $zoom_id);
                } else {
                    $res = array("error" => "Could not create user in zoom");
                }
            }

            //create meeting
            if (!isset($res["error"])) {
                $meeting_topic = "Let's start a video call.";
                $meeting_type = "1";
                $res = $zoom->createAMeeting($zoom_id, $meeting_topic, $meeting_type);

                $result_zoom = json_decode($res);
                ZoomMeetings::createMeeting($host_id, $session_id, $result_zoom);
            }

            break;
    }

    echo $res;
    wp_die();
}
