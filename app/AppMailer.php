<?php

define("APP_AJAX_PATH", str_replace("\\", "/", plugin_dir_path(__FILE__)));

class AppMailer {
    const TYPE_STUDENT_REGISTRATION = "STUDENT_REGISTRATION";
    const TYPE_RESET_PASSWORD = "RESET_PASSWORD";
    const TYPE_NEW_RECRUITER = "NEW_RECRUITER";
    const TYPE_CUSTOM_EMAIL = "CUSTOM_EMAIL";
    const EMAIL_TEMPLATE = APP_AJAX_PATH . "email_template";

    public static function send_mail($to_email, $email_data, $type) {

        //** filter set to html **/
        function app_set_html_mail_content_type() {
            return 'text/html';
        }
 
        add_filter('wp_mail_content_type', 'app_set_html_mail_content_type');

        $apps_name = "Seeds Job Fair";

        if($type != self::TYPE_CUSTOM_EMAIL){
            $content = file_get_contents(self::EMAIL_TEMPLATE . "/$type.html");
        }

        $title = "";

        //** title and content generation using $email_data ***//
        switch ($type) {
            case self::TYPE_STUDENT_REGISTRATION:
                $title = "Welcome To $apps_name";

                //replace constant from template
                $search = array("{#first_name}", "{#last_name}", "{#activation_link}");
                $replace = array($email_data["first_name"]
                    , $email_data["last_name"]
                    , $email_data["activation_link"]);
                $content = str_replace($search, $replace, $content);
                break;

            case self::TYPE_RESET_PASSWORD:
                $title = "[$apps_name] Reset Password Link";

                //replace constant from template
                $search = array("{#first_name}", "{#link}");
                $replace = array($email_data["first_name"], $email_data["link"]);
                $content = str_replace($search, $replace, $content);
                break;

            case self::TYPE_NEW_RECRUITER:
                $title = "Welcome To $apps_name";

                //replace constant from template
                $search = array("{#company_name}", "{#app_name}", "{#set_password_link}");
                $replace = array($email_data["company_name"], $apps_name, $email_data["reset_password_link"]);
                $content = str_replace($search, $replace, $content);
                break;

            case self::TYPE_CUSTOM_EMAIL:
                $title = $email_data["title"];
                $content = $email_data["content"];
                break; 
        }

        // add footer
        $content .= "<br><br>Regards,<br><i>Innovaseeds Solutions</i>";

        $ret = wp_mail($to_email, $title, $content);
        remove_filter('wp_mail_content_type', 'app_set_html_mail_content_type');
        return $ret;
    }

}

//AppMailer::send_mail("zulsarhan.shaari@gmail.com", array("first_name" => "Wan", "last_name" => "Zul", "activation_link" => "Asads"), AppMailer::TYPE_STUDENT_REGISTRATION);
//exit();
?>

