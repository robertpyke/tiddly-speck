<!--
Author: Robert Pyke
-->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>tiddlyspeck - Site creation status</title>
</head>
<body>
<?php
        $project_name = "TiddlySpeck";

        // Lookup each of the POST variables.
        // Bail if any of the requires POST variables are missing.
        $id = $_POST["id"] or die("<h3>Didn't receive site id in POST</h3></body></html>");
        $password = $_POST["password"] or die("<h3>Didn't receive password in POST</h3></body></html>");
        $password_conf = $_POST["password_conf"] or die("<h3>Didn't receive password_conf in POST</h3></body></html>");

        // TODO - Add template support, for now assume that the template is default.
        $template = "default";
        $site_id_dir_path = "";
        $chosen_template_dir_path = "";
        $project_name_URL = ScriptsFolderURL();
        $sites_url = $project_name_URL."sites/";

        // Gets the current directory, dies if can't get directory.
        function GetCurrentDirectory() {
            global $project_name;
            
            $curr_dir = getcwd(); // False if we couldn't get the current directory.
            if (!$curr_dir) {
                die ("<p>php couldn't get the current directory, this is likely caused ".
                    "by an improper ".$project_name." install.</p></body></html>");
            } else  {
                return $curr_dir;
            }
        }// end GetCurrentDirectory

        // Gets the server URL
        function ServerURL() {
            $serverURL = 'http';
            if ($_SERVER["HTTPS"] == "on") {$serverURL .= "s";}
            $serverURL .= "://";
            if ($_SERVER["SERVER_PORT"] != "80") {
                $serverURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
            } else {
                $serverURL .= $_SERVER["SERVER_NAME"];
            }
            return $serverURL;
        }// end ServerURL

        // Returns the URL of this script.
        function ScriptURL() {
            return ServerURL().$_SERVER["PHP_SELF"];
        }

        // Returns the URL to the folder in which this script is executing.
        // E.g
        //  If the script is running at: http://example.com/foo/test.php
        //  This will return http://example.com/foo/
        function ScriptsFolderURL() {
            global $project_name;
            $script_URL = ServerURL().$_SERVER["PHP_SELF"];
            if (ereg("([^/]*\/)+", $script_URL, $regs)) {            // Capture all characters up to and including the last forward-slash.
                return $regs[0];
            } else {
                // This should never happen.
                die("<p>".$project_name." Bug. Could not detect the directory ".
                    "in which the current php script is being run.</p></body></html>");
            }
        }// end ScriptsFolderURL

        // Confirm that the passwords match.
        function ValidatePasswords()
        {
            // Use the password global variables we received from the post.
            global $password, $password_conf;

            if ($password == $password_conf) {
                if (strlen($password) == 0) {
                    echo "<p>No password provided.</p>";
                    return false;
                }else {
                    echo "<p>Password valid.</p>";
                    return true;
                }
            } else {
                echo "<p>Passwords didn't match</p>";
                return false;
            }
        }// end ValidatePasswords

        // To validate the site id, all we need to do is check if we
        // have already created a site by that name. So, check
        // the sites folder for a directory by the name the user has provided.
        function ValidateSiteId()
        {
            global $id, $site_id_dir_path, $project_name;

            $sites_dir_to_create = GetCurrentDirectory()."/sites/";
            $sites_dir_exists = file_exists($sites_dir_to_create);
            if (!$sites_dir_exists) {
                // If this is the first time a site has ever been required to be built,
                // create the sites folder.
                $made_dir = mkdir($sites_dir_to_create);
                if (!$made_dir) {
                    echo "<p>php couldn't create the sites directory, this is likely caused ";
                    echo "by an improper ".$project_name." install.</p>";
                    return false;
                } else {
                    // Successfully created the sites directory.
                    echo "<p>Site id, <strong>".$id."</strong>, valid.</p>";
                    return true;
                }
            } else {
                $site_id_dir_path = $sites_dir_to_create.$id."/"; // Path to the user's site directory
                if (file_exists($site_id_dir_path)) {
                    echo "<p>This site id, <strong>".$id."</strong>, is in use.</p>";
                    return false;
                } else {
                    echo "<p>Site id, <strong>".$id."</strong>, valid.</p>";
                    return true;
                }
            }
        }// end ValidateSiteId

        // To validate the template, all we need to do is check if we
        // have a template in our templates folder by that name. So, check
        // the templates folder for a directory with the provided template's name
        // and an index.html inside.
        function ValidateTemplate()
        {
            global $template, $chosen_template_dir_path, $project_name;

            $templates_dir = GetCurrentDirectory()."/templates/";
            $templates_dir_exists = file_exists($templates_dir);
            if (!$templates_dir_exists) {
                // If the templates directory does not exist, create it.
                $made_dir = mkdir($templates_dir);
                if (!$made_dir) {
                    echo "<p>php couldn't create the templates directory, this is likely caused ";
                    echo "by an improper ".$project_name." install.</p>";
                    return false;
                } else {
                    // If we had to create the template directory, the template did not exist.
                    echo "<p>No templates installed, this is likely caused ";
                    echo "by an improper ".$project_name." install.</p>";
                    return false;
                }
            } else {
                $chosen_template_dir_path = $templates_dir.$template."/"; // Path to the chosen template's directory
                if (file_exists($chosen_template_dir_path)) {
                    // We have found the template directory, now confirm that it contains a index.html
                    if (file_exists($chosen_template_dir_path."index.html")) {
                        // It did contain an index.html
                        echo "<p>The template, <strong>".$template."</strong>, is valid.</p>";
                        return true;
                    } else {
                        // Template's folder didn't contain an index.html
                        echo "<p>Template, <strong>".$template."</strong>, ";
                        echo "is not installed. It has a template directory, ";
                        echo "but the directory does not contain an <strong>index.html</strong>.</p>";
                        return false;
                    }
                } else {
                    echo "<p>The template, <strong>".$template."</strong>, is not installed.</p>";
                    return false;
                }
            }
        }// end ValidateTemplate

        // Create the user's site.
        function CreateSite()
        {
            // Use the site $id to generate the user's site at $site_file_path.
            // Use the template to create the initial copy of the user's wiki.
            global $id, $password, $site_id_dir_path, $chosen_template_dir_path;
            // This function must:
            // 1. Create the user's directory.
            // 2. Create a string copy of the template.
            // 3. Manipulate the template string to add the upload plugins.
            // 4. Create the user's site using the modified template string.

            // 1. Create the user's directory.
            $made_site_dir = mkdir($site_id_dir_path);

            // 2. Create a string copy of the template.
            $template_file_as_a_string = file_get_contents($chosen_template_dir_path."index.html");

            // TODO 3. Manipulate the template string to add the upload plugins.

            // 4. Create the user's site using the modified template string.
            //
            // The following fopen mode may need to include 'b', e.g. mode= 'w+b'.
            // Windows requires b if it is a binary file not a text file.
            // TODO - Find out which it should be, I assume that it is a normal text file as it
            // is html.
            $dest_file_path = $site_id_dir_path."index.html";
            $dest_file_handle = fopen($dest_file_path, "w+"); // Get a handle on the file with a w+ read & write capabilities
            $wrote_file = fwrite($dest_file_handle, $template_file_as_a_string);

            return true;
        }// end CreateSite

        // *************
        // Start main
        // *************

        if (!ValidateSiteId() or !ValidatePasswords() or !ValidateTemplate()) {
            echo "<h3>Site creation failed.</h3>";
        } else {
            echo "<h3>Validation successfully completed, creating site.</h3>";

            // If we made it here we have a valid site id, template and password.
            // We are now able to create a site for the user.
            $created_site = CreateSite();
            if (!$created_site) {
                echo "<h3>Site creation failed.</h3>";
            } else {
                $users_site_url = $sites_url.$id;
                echo "<h3>Successfully created site.</h3>";
                echo "<p>Your site is available at: <a href=".$users_site_url.">".$users_site_url."</a>.</p>";
            }
        }

        // *************
        // End main
        // *************
        ?>
</body>
</html>
