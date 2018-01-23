<?php

add_action('wp_ajax_wzs21_customQuery', 'wzs21_customQuery');
add_action('wp_ajax_nopriv_wzs21_customQuery', 'wzs21_customQuery');


/*
 * This function created when implementing dashboard feature
 * Objective, able to fetch only the new based on the latest id the client already has
 * type of fetch 
 * -- get new based on latest id (get only id more than given id (init also use this, where id = 0
 * -- get load more -- normal pagination ?
 */

//
//function getPaginationNew($params) {
//
//    // get params documentation
//    //    ["type"]; -- get init, get new or load more,
//    //    ["latest_id"];
//    //    ["oldest_id"];
//    //    ["offset"];
//    //    ["action"];
//
//    $get_params = $params["get_params"];
//    $custom_params = $params["custom_params"];
//
//    $res_data = false;
//
//    switch ($get_params["action"]) {
//        case 'dashboard' :
//            $res_data = Dashboard::pn_getNewsFeed($get_params, $custom_params);
//            break;
//    }
//
//    return $res_data;
//}
//param $query and optional arguments
function wzs21_customQuery() {
    global $wpdb;

    $query = (isset($_POST['query'])) ? sanitize_text_field($_POST['query']) : null;
    $res = null;

    switch ($query) {
        case 'get_dashboard_newsfeed':
            $res = Dashboard::getDashboardNewsfeed($_POST);
            break;

        case 'create_recruiter':
            unset($_POST["action"]);
            unset($_POST["query"]);
            Users::createNewRecruiter($_POST);
            break;

        case 'add_dataset':
            Dataset::addDataset($_POST["id"], $_POST["data"]);
            break;

        case 'delete_dataset':
            Dataset::deleteDataset($_POST["id"], $_POST["data"]);
            break;

        case 'load_dataset':
            $res = Dataset::getValueFromDB($_POST["id"], false);
            echo $res;
            wp_die();
            break;

        case 'drop_resume_init':
            $res = ResumeDrop::dropResumeInit($_POST);
            break;

        case 'get_session_start_time':
            $session_id = (isset($_POST['session_id'])) ? sanitize_text_field($_POST['session_id']) : null;
            $res = Session::getStartTime($session_id);
            //we dont want to return the standard response here
            echo $res;
            wp_die();
            break;

        //for student home view ... main_student.js
        case 'get_company_inqueue_by_student':
            $res = getCompanyCareerFair($wpdb, InQueue::TABLE_NAME);
            break;

        case 'get_company_prescreen_by_student':
            $res = getCompanyCareerFair($wpdb, PreScreen::TABLE_NAME);
            break;

        case 'get_active_session_by_participant':
            $participant_id = (isset($_POST['participant_id'])) ? sanitize_text_field($_POST['participant_id']) : null;
            $select = array(Session::COL_ID, Session::COL_HOST_ID
                , QueryPrepare::generate_UNIXTIMESTAMP_select(Session::COL_CREATED_AT));
            $sql = Session::query_get_session_by_entity(Session::COL_PARTCPNT_ID, $participant_id, $select, Session::STATUS_ACTIVE);
            $session = $wpdb->get_results($sql, ARRAY_A);

            if (isset($session[0])) {
                $session = $session[0];
                $select = array(Company::COL_ID, Company::COL_NAME, Company::COL_IMG_URL, Company::COL_IMG_POSITION, Company::COL_IMG_SIZE);
                $sql = Company::query_get_company_by_rec_id($session[Session::COL_HOST_ID], $select);
                $company = $wpdb->get_results($sql, ARRAY_A);

                if (isset($company[0])) {
                    $res["status"] = SiteInfo::STATUS_SUCCESS;
                    $res["session"] = $session;
                    $res["company"] = $company[0];
                } else {
                    $res["status"] = SiteInfo::STATUS_ERROR;
                    $res["data"] = "Company Does Not Exist";
                }
            } else {
                $res["status"] = SiteInfo::STATUS_ERROR;
                $res["data"] = "Company Does Not Exist";
            }
            break;

        //for rec home view main_recruiter.js
        case 'get_student_prescreen_by_company':
            $res = getStudentCareerFair($wpdb, PreScreen::TABLE_NAME);
            break;
        case 'get_student_inqueue_by_company':
            $res = getStudentCareerFair($wpdb, InQueue::TABLE_NAME);
            break;

        case 'get_active_session_by_host':
            $host_id = (isset($_POST['host_id'])) ? sanitize_text_field($_POST['host_id']) : null;
            $select = array(Session::COL_ID, Session::COL_PARTCPNT_ID
                , QueryPrepare::generate_UNIXTIMESTAMP_select(Session::COL_CREATED_AT));
            $sql = Session::query_get_session_by_entity(Session::COL_HOST_ID, $host_id, $select, Session::STATUS_ACTIVE);
            $session = $wpdb->get_results($sql, ARRAY_A);
            $res = array();

            if (isset($session[0])) {
                $session = $session[0];

                $select = array(SiteInfo::USERMETA_FIRST_NAME
                    , SiteInfo::USERMETA_LAST_NAME
                    , SiteInfo::USERMETA_IMAGE_URL
                    , SiteInfo::USERMETA_IMAGE_POSITION
                    , SiteInfo::USERMETA_IMAGE_SIZE);

                $student_id = $session[Session::COL_PARTCPNT_ID];
                $where = array(SiteInfo::USERS_ID . " = '$student_id'");
                $user = Users::get_users(SiteInfo::ROLE_STUDENT, 1, 1, $select, $where);

                if (isset($user[0])) {
                    $res["status"] = SiteInfo::STATUS_SUCCESS;
                    $res["session"] = $session;
                    $res["student"] = $user[0];
                } else {
                    $res["status"] = SiteInfo::STATUS_ERROR;
                    $res["data"] = "Student Does Not Exist";
                }
            } else {
                $res["status"] = SiteInfo::STATUS_ERROR;
                $res["data"] = "No Active Session";
            }

            break;

        case 'register_prescreen':
            $student_id = (isset($_POST['student_id'])) ? sanitize_text_field($_POST['student_id']) : null;
            $company_ids = (isset($_POST['company_ids'])) ? $_POST['company_ids'] : array();
            $res = PreScreen::registerPreScreen($student_id, $company_ids);
            break;

        case 'get_prescreen_company':
            $query = Company::query_get_prescreen_company();
            $res = $wpdb->get_results($query);
            break;

        case 'get_notes_by_session':
            $session_id = (isset($_POST['session_id'])) ? sanitize_text_field($_POST['session_id']) : null;
            $query = SessionNote::query_get_note_by_session($session_id);
            $res = $wpdb->get_results($query);

            foreach ($res as $k => $r) {
                $res[$k] = myp_formatStringToHTMDeep($r, true);
            }
            break;

        case 'get_company_detail':
            $company_id = (isset($_POST['company_id'])) ? sanitize_text_field($_POST['company_id']) : null;
            $query = Company::query_get_company_detail($company_id);
            $res = $wpdb->get_row($query);
            $res = myp_formatStringToHTMDeep($res);
            break;

        case 'get_vacancy_detail':
            $vacancy_id = (isset($_POST['vacancy_id'])) ? sanitize_text_field($_POST['vacancy_id']) : null;
            $query = Vacancy::query_get_vacancy_detail($vacancy_id);
            $res = $wpdb->get_row($query);

            if (isset($_POST['isInput']) && $_POST['isInput']) {
                $res = stripslashes_deep($res);
            } else {
                $res = myp_formatStringToHTMDeep($res, true);
            }
            break;

        // used in career fair company listing card --not used anymore
        /*
          case 'get_vacancy_by_company_id':
          $company_id = (isset($_POST['company_id'])) ? sanitize_text_field($_POST['company_id']) : null;
          $page = 1;
          $offset = SiteInfo::PAGE_OFFSET_CAREER_FAIR_VACANCY;
          $query = Vacancy::query_get_vacancy_by_company_id($company_id, $page, $offset);
          $res["data"] = $wpdb->get_results($query);

          $query = Vacancy::query_get_vacancy_count_by_company_id($company_id);
          $res["total"] = $wpdb->get_row($query)->total;
          break;
         */

        //used in company pages
        case 'get_vacancy_details_by_company_id' :
            $select = array(Vacancy::COL_ID
                , Vacancy::COL_TITLE
                , Vacancy::COL_TYPE
                , Vacancy::COL_DESC
                , Vacancy::COL_APPLICATION_URL);
            $company_id = $_POST['data']['company_id'];

            $search_param = (isset($_POST['search_param'])) ? sanitize_text_field($_POST['search_param']) : "%";
            $page = (isset($_POST['page'])) ? sanitize_text_field($_POST['page']) : null;

            //get data
            $offset = SiteInfo::PAGE_OFFSET_DISPLAY_VACANCY;
            $query = Vacancy::query_get_vacancy_by_company_id($company_id, $page, $offset, $select, $search_param);
            $data_res = $wpdb->get_results($query);
            foreach ($res as $k => $d) {
                $data_res[$k] = myp_formatStringToHTMDeep($d, true);
            }

            //get count
            if ($page == 1 && count($data_res) < $offset) {
                $count = count($data_res);
            } else {
                $select = array("COUNT(*) as count");
                $query = Vacancy::query_get_vacancy_by_company_id($company_id, $page, null, $select, $search_param);
                $count = $wpdb->get_row($query, ARRAY_A)["count"];
            }

            $res["data"] = $data_res;
            $res["count"] = $count;
            break;

        case 'search_all_feedback':
            $search_param = (isset($_POST['search_param'])) ? sanitize_text_field($_POST['search_param']) : null;
            $page = (isset($_POST['page'])) ? sanitize_text_field($_POST['page']) : null;
            $is_export = (isset($_POST['is_export'])) ? true : false;
            $user_role = $_POST["data"]["user_role"];
            $offset = SiteInfo::PAGE_OFFSET_ADMIN_PANEL;

            $sql = Users::query_get_all_feedback($user_role, $page, $offset, $is_export, false);
            $data_res = $wpdb->get_results($sql);
            foreach ($data_res as $k => $r) {
                $data_res[$k] = myp_formatStringToHTMDeep($r, true);
            }

            if ($page == 1 && count($data_res) < $offset) {
                $count = count($data_res);
            } else {
                $sql = Users::query_get_all_feedback($user_role, $page, $offset, $is_export, true);
                $count = $wpdb->get_row($sql, ARRAY_A)["count"];
            }

            $res["data"] = $data_res;
            $res["count"] = $count;
            
            break;
        case 'search_all_sesison' :
            $search_param = (isset($_POST['search_param'])) ? sanitize_text_field($_POST['search_param']) : null;
            $page = (isset($_POST['page'])) ? sanitize_text_field($_POST['page']) : null;
            $is_export = (isset($_POST['is_export'])) ? true : false;
            $status = $_POST["data"]["status"];
            $offset = SiteInfo::PAGE_OFFSET_ADMIN_PANEL;


            $sql = Session::query_get_all_session($status, $page, $offset, $is_export, false);
            $data_res = $wpdb->get_results($sql);
            foreach ($data_res as $k => $r) {
                $data_res[$k] = myp_formatStringToHTMDeep($r, true);
            }

            if ($page == 1 && count($data_res) < $offset) {
                $count = count($data_res);
            } else {
                $sql = Session::query_get_all_session($status, $page, $offset, $is_export, true);
                $count = $wpdb->get_row($sql, ARRAY_A)["count"];
            }

            $res["data"] = $data_res;
            $res["count"] = $count;
            break;
        case 'search_session_by_student_id': //get companies
            $search_param = (isset($_POST['search_param'])) ? sanitize_text_field($_POST['search_param']) : null;
            $page = (isset($_POST['page'])) ? sanitize_text_field($_POST['page']) : null;
            $is_export = (isset($_POST['is_export'])) ? true : false;
            $student_id = $_POST["data"]["student_id"];
            $offset = SiteInfo::PAGE_OFFSET_ADMIN_PANEL;
            $sql = Session::query_get_company_details_by_student($student_id, $search_param, $page, $offset, $is_export, false);
            $data_res = $wpdb->get_results($sql);
            foreach ($data_res as $k => $r) {
                $data_res[$k] = myp_formatStringToHTMDeep($r, true);
            }

            if ($page == 1 && count($data_res) < $offset) {
                $count = count($data_res);
            } else {
                $sql = Session::query_get_company_details_by_student($student_id, $search_param, $page, $offset, $is_export, true);
                $count = $wpdb->get_row($sql, ARRAY_A)["count"];
            }

            $res["data"] = $data_res;
            $res["count"] = $count;
            break;
        case 'search_session_by_company_id' ://get students
            $search_param = (isset($_POST['search_param'])) ? sanitize_text_field($_POST['search_param']) : null;
            $page = (isset($_POST['page'])) ? sanitize_text_field($_POST['page']) : null;
            $is_export = (isset($_POST['is_export'])) ? true : false;
            $company_id = $_POST["data"]["company_id"];
            $offset = SiteInfo::PAGE_OFFSET_ADMIN_PANEL;
            $sql = Session::query_get_student_details_by_company($company_id, $search_param, $page, $offset, $is_export, false);
            $data_res = $wpdb->get_results($sql);
            foreach ($data_res as $k => $r) {
                $data_res[$k] = myp_formatStringToHTMDeep($r, true);
            }

            if ($page == 1 && count($data_res) < $offset) {
                $count = count($data_res);
            } else {
                $sql = Session::query_get_student_details_by_company($company_id, $search_param, $page, $offset, $is_export, true);
                $count = $wpdb->get_row($sql, ARRAY_A)["count"];
            }

            $res["data"] = $data_res;
            $res["count"] = $count;
            break;

        //used in session for recruiter
        case 'get_company_current_total_queue':
            $company_id = (isset($_POST['company_id'])) ? sanitize_text_field($_POST['company_id']) : null;
            $count = InQueue::get_count_by_entity(InQueue::COL_COMPANY_ID, $company_id, InQueue::STATUS_QUEUING);
            $res["count"] = $count;
            break;

        case 'search_pre_screen_by_student_id': //get company

            $search_param = (isset($_POST['search_param'])) ? sanitize_text_field($_POST['search_param']) : null;
            $page = (isset($_POST['page'])) ? sanitize_text_field($_POST['page']) : null;
            $is_export = (isset($_POST['is_export'])) ? true : false;
            $student_id = $_POST["data"]["student_id"];

            $offset = SiteInfo::PAGE_OFFSET_ADMIN_PANEL;
            $sql = PreScreen::query_get_student_details_by_student($student_id, $search_param, $page, $offset, $is_export, false);
            $data_res = $wpdb->get_results($sql);
            foreach ($data_res as $k => $r) {
                $data_res[$k] = myp_formatStringToHTMDeep($r, true);
            }

            if ($page == 1 && count($data_res) < $offset) {
                $count = count($data_res);
            } else {
                $sql = PreScreen::query_get_student_details_by_student($student_id, $search_param, $page, $offset, $is_export, true);
                $count = $wpdb->get_row($sql, ARRAY_A)["count"];
            }

            $res["data"] = $data_res;
            $res["count"] = $count;
            break;
        case 'search_pre_screen_by_company_id': //get students

            $search_param = (isset($_POST['search_param'])) ? sanitize_text_field($_POST['search_param']) : null;
            $page = (isset($_POST['page'])) ? sanitize_text_field($_POST['page']) : null;
            $is_export = (isset($_POST['is_export'])) ? true : false;
            $company_id = $_POST["data"]["company_id"];

            $offset = SiteInfo::PAGE_OFFSET_ADMIN_PANEL;
            $sql = PreScreen::query_get_student_details_by_company($company_id, $search_param, $page, $offset, $is_export, false);
            $data_res = $wpdb->get_results($sql);
            foreach ($data_res as $k => $r) {
                $data_res[$k] = myp_formatStringToHTMDeep($r, true);
            }

            if ($page == 1 && count($data_res) < $offset) {
                $count = count($data_res);
            } else {
                $sql = PreScreen::query_get_student_details_by_company($company_id, $search_param, $page, $offset, $is_export, true);
                $count = $wpdb->get_row($sql, ARRAY_A)["count"];
            }

            $res["data"] = $data_res;
            $res["count"] = $count;
            break;

        case 'search_resume_drop_by_student_id': //get company
            $search_param = (isset($_POST['search_param'])) ? sanitize_text_field($_POST['search_param']) : null;
            $page = (isset($_POST['page'])) ? sanitize_text_field($_POST['page']) : null;
            $is_export = (isset($_POST['is_export'])) ? true : false;
            $student_id = $_POST["data"]["student_id"];

            $offset = SiteInfo::PAGE_OFFSET_ADMIN_PANEL;
            $sql = ResumeDrop::query_get_student_details_by_student($student_id, $search_param, $page, $offset, $is_export, false);
            $data_res = $wpdb->get_results($sql);
            foreach ($data_res as $k => $r) {
                $data_res[$k] = myp_formatStringToHTMDeep($r, true);
            }

            if ($page == 1 && count($data_res) < $offset) {
                $count = count($data_res);
            } else {
                $sql = ResumeDrop::query_get_student_details_by_student($student_id, $search_param, $page, $offset, $is_export, true);
                $count = $wpdb->get_row($sql, ARRAY_A)["count"];
            }

            $res["data"] = $data_res;
            $res["count"] = $count;
            break;

        case 'search_resume_drop_by_company_id': //get students
            $search_param = (isset($_POST['search_param'])) ? sanitize_text_field($_POST['search_param']) : null;
            $page = (isset($_POST['page'])) ? sanitize_text_field($_POST['page']) : null;
            $is_export = (isset($_POST['is_export'])) ? true : false;
            $company_id = $_POST["data"]["company_id"];

            $offset = SiteInfo::PAGE_OFFSET_ADMIN_PANEL;
            $sql = ResumeDrop::query_get_student_details_by_company($company_id, $search_param, $page, $offset, $is_export, false);
            $data_res = $wpdb->get_results($sql);
            foreach ($data_res as $k => $r) {
                $data_res[$k] = myp_formatStringToHTMDeep($r, true);
            }

            if ($page == 1 && count($data_res) < $offset) {
                $count = count($data_res);
            } else {
                $sql = ResumeDrop::query_get_student_details_by_company($company_id, $search_param, $page, $offset, $is_export, true);
                $count = $wpdb->get_row($sql, ARRAY_A)["count"];
            }

            $res["data"] = $data_res;
            $res["count"] = $count;
            break;

        case 'get_recruiter_details_by_company_id':
            $company_id = $_POST['data']['company_id'];
            $page = (isset($_POST['page'])) ? sanitize_text_field($_POST['page']) : null;
            $search_param = (isset($_POST['search_param'])) ? sanitize_text_field($_POST['search_param']) : "%";

            $order_by = array(SiteInfo::USERS_DATE_REGISTER . " DESC");
            $where_str = sprintf(" %s = '%s' AND ( %s LIKE '%s' OR %s LIKE '%s' OR %s LIKE '%s')"
                    , SiteInfo::USERMETA_REC_COMPANY, $company_id
                    , SiteInfo::USERMETA_FIRST_NAME, "%$search_param%"
                    , SiteInfo::USERMETA_LAST_NAME, "%$search_param%"
                    , SiteInfo::USERS_EMAIL, "%$search_param%"
            );
            $where = array($where_str);

            //get data
            $select = array();
            $offset = SiteInfo::PAGE_OFFSET_DISPLAY_RECRUITER;
            $data_res = Users::get_users(SiteInfo::ROLE_RECRUITER, $page, $offset, $select, $where, $order_by);

            //get count
            if ($page == 1 && count($data_res) < $offset) {
                $count = count($data_res);
            } else {
                $count = Users::get_users(SiteInfo::ROLE_RECRUITER, $page, $offset, $select, $where, $order_by, false, true);
            }

            $res["data"] = $data_res;
            $res["count"] = $count;
            break;

        case 'search_companies':
            $search_param = (isset($_POST['search_param'])) ? sanitize_text_field($_POST['search_param']) : null;
            $page = (isset($_POST['page'])) ? sanitize_text_field($_POST['page']) : null;

            //$search_by_field = array("name", "tagline", "description");

            $search_field = array(Company::COL_NAME
                , Company::COL_TAGLINE
                , Company::COL_DESC);
            $offset = SiteInfo::PAGE_OFFSET_CAREER_FAIR;

            //get data
            $select = array(Company::COL_ID
                , Company::COL_NAME
                , Company::COL_TAGLINE
                , Company::COL_IMG_URL
                , Company::COL_IMG_POSITION
                , Company::COL_IMG_SIZE
                , Company::COL_ACCEPT_PRESCREEN
                , Company::COL_TYPE);

            $query = Company::query_search_companies($select, $search_param, $search_field, $page, $offset);
            $data_res = $wpdb->get_results($query, ARRAY_A);

            //get count
            if ($page == 1 && count($data_res) < $offset) {
                $count = count($data_res);
            } else {
                $select = array("COUNT(*) as count");
                $query = Company::query_search_companies($select, $search_param, $search_field, $page, $offset);
                $count = $wpdb->get_row($query, ARRAY_A)["count"];
            }

            //get recruiters
            foreach ($data_res as $k => $d) {
                $select = array(SiteInfo::USERS_ID
                    , SiteInfo::USERS_EMAIL
                );
                $data_res[$k]["recruiters"] = Company::getAllRecsByCompany($d[Company::COL_ID], $select);
            }

            $res["data"] = $data_res;
            $res["count"] = $count;
            break;

        case 'search_companies_by_name':
            $search_param = (isset($_POST['search_param'])) ? sanitize_text_field($_POST['search_param']) : null;
            $query = Company::query_search_companies_by_name($search_param);

            $res = array();

            foreach ($wpdb->get_results($query) as $r) {
                $res[] = $r->name;
            }

            break;
        case 'search_recruiters' :
            $search_param = (isset($_POST['search_param'])) ? sanitize_text_field($_POST['search_param']) : null;
            $page = (isset($_POST['page'])) ? sanitize_text_field($_POST['page']) : null;
            $is_export = (isset($_POST['is_export'])) ? true : false;
            $key_search = (isset($_POST['key_search'])) ? sanitize_text_field($_POST['key_search']) : null;
            $order_by = array(SiteInfo::USERMETA_REC_COMPANY . " ASC");

            $where = array();
            if (!$is_export && $search_param != "%" && $search_param != "") {
                switch ($key_search) {
                    case 'recruiter':
                        $where = array(
                            "CONCAT(" . SiteInfo::USERMETA_FIRST_NAME . ",' '," . SiteInfo::USERMETA_LAST_NAME . ")"
                            . "LIKE '%$search_param%' ");
                        break;
                }
            }

            $select = array();
            $offset = SiteInfo::PAGE_OFFSET_ADMIN_PANEL;
            $data_res = Users::get_users(SiteInfo::ROLE_RECRUITER, $page, $offset, $select, $where, $order_by, $is_export);
            foreach ($data_res as $k => $r) {
                $data_res[$k] = myp_formatStringToHTMDeep($r, true);
            }

            $count = Users::get_users(SiteInfo::ROLE_RECRUITER, $page, $offset, $select, $where, $order_by, $is_export, true);
            $res["data"] = $data_res;
            $res["count"] = $count;
            break;
        case 'search_students' :
            $search_param = (isset($_POST['search_param'])) ? sanitize_text_field($_POST['search_param']) : null;
            $page = (isset($_POST['page'])) ? sanitize_text_field($_POST['page']) : null;
            $is_export = (isset($_POST['is_export'])) ? true : false;
            $key_search = (isset($_POST['key_search'])) ? sanitize_text_field($_POST['key_search']) : null;
            $order_by = array(SiteInfo::USERS_DATE_REGISTER . " DESC");

            $where = array();
            if (!$is_export && $search_param != "%" && $search_param != "") {
                switch ($key_search) {
                    case 'student':
                        $where = array(
                            "CONCAT(" . SiteInfo::USERMETA_FIRST_NAME . ",' '," . SiteInfo::USERMETA_LAST_NAME . ")"
                            . "LIKE '%$search_param%' ");
                        break;
                    case 'university':
                        $where = array(
                            SiteInfo::USERMETA_UNIVERSITY
                            . " LIKE '%$search_param%' ");
                        break;
                    case 'major':
                        $where = array(
                            SiteInfo::USERMETA_MAJOR . " LIKE '%$search_param%' "
                            , SiteInfo::USERMETA_MINOR . " LIKE '%$search_param%' ");
                        break;
                }
            }

            $select = array();
            $offset = SiteInfo::PAGE_OFFSET_ADMIN_PANEL;
            $data_res = Users::get_users(SiteInfo::ROLE_STUDENT, $page, $offset, $select, $where, $order_by, $is_export);
            foreach ($data_res as $k => $r) {
                $data_res[$k] = myp_formatStringToHTMDeep($r, true);
            }

            $count = Users::get_users(SiteInfo::ROLE_STUDENT, $page, $offset, $select, $where, $order_by, $is_export, true);
            $res["data"] = $data_res;
            $res["count"] = $count;
            break;

        case 'search_students_by_key' :
            $search_param = (isset($_POST['search_param'])) ? sanitize_text_field($_POST['search_param']) : null;
            $key_search = (isset($_POST['key_search'])) ? sanitize_text_field($_POST['key_search']) : null;

            switch ($key_search) {
                case 'student':
                    $select = array(SiteInfo::USERMETA_FIRST_NAME,
                        SiteInfo::USERMETA_LAST_NAME);
                    $where = array(SiteInfo::USERMETA_FIRST_NAME . " LIKE '%$search_param%' ",
                        SiteInfo::USERMETA_LAST_NAME . " LIKE '%$search_param%' ");
                    $order_by = array(SiteInfo::USERMETA_FIRST_NAME,
                        SiteInfo::USERMETA_LAST_NAME);
                    break;
                case 'university':
                    $select = array(SiteInfo::USERMETA_UNIVERSITY);
                    $where = array(
                        SiteInfo::USERMETA_UNIVERSITY
                        . " LIKE '%$search_param%' ");
                    $order_by = array(SiteInfo::USERMETA_UNIVERSITY);
                    break;
                case 'major':
                    $select = array(SiteInfo::USERMETA_MAJOR);
                    $where = array(
                        SiteInfo::USERMETA_MAJOR
                        . " LIKE '%$search_param%' ");
                    $order_by = array(SiteInfo::USERMETA_MAJOR);
                    break;
            }

            $page = 1;
            $offset = SiteInfo::PAGE_OFFSET_SEARCH_SUGGEST;

            $students = Users::get_users(SiteInfo::ROLE_STUDENT, $page, $offset, $select, $where, $order_by);

            $res = array();
            foreach ($students as $s) {
                $s = (array) $s;
                switch ($key_search) {
                    case 'student':
                        $val = $s[SiteInfo::USERMETA_FIRST_NAME] . " " . $s[SiteInfo::USERMETA_LAST_NAME];
                        break;
                    case 'university':
                        $val = $s[SiteInfo::USERMETA_UNIVERSITY];
                        break;
                    case 'major':
                        $val = $s[SiteInfo::USERMETA_MAJOR];
                        break;
                }

                if (!in_array($val, $res)) {
                    $res[] = $val;
                }
            }

            break;
    }

    //echo json_encode(myp_formatStringToHTMDeep($res));
    echo json_encode($res);
    wp_die();
}
