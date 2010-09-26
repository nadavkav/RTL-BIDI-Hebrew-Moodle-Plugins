<?php // $Id: editsection.php,v 1.00 2008/4/1 12:00:00 Akio Ohnishi Exp $
      // Edit the introduction of a section

    require_once("../../../config.php");
    require_once("./lib.php");
    require_once('./converter.php');

    $id = required_param('id', PARAM_INT);
    // セクションタイトルデータを取得
    if (! $sectiontitle = get_record('course_project_title', 'id', $id)) {
        error("Section title is incorrect");
    }

    // セクションとコースのデータを取得
    if (! $section = get_record("course_sections", "id", $sectiontitle->sectionid)) {
        error("Course section is incorrect");
    }
    if (! $course = get_record("course", "id", $section->course)) {
        error("Could not find the course!");
    }

    require_login($course->id);
    require_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $course->id));
    
    // ここからフォームの表示
    $stredit = get_string('edittitle', 'format_project');
    $sectionname  = get_string("name$course->format", 'format_project');
    $strsummaryof = get_string('summaryof', '', " $sectionname $section->section");
    print_header_simple($stredit, '', build_navigation(array(array('name' => $stredit, 'link' => null, 'type' => 'misc'))), 'theform.summary' );
    print_heading($strsummaryof);
    
    print_simple_box_start('center');
    
    // フォームからデータが送信されたときの処理
    if ($form = data_submitted() and confirm_sesskey()) {
        $directoryname = required_param('directoryname', PARAM_CLEANFILE);
    
        $timenow = time();
        
        if ($form->olddirectoryname != $directoryname && !project_format_check_directoryname($course->id, $directoryname)) {
            error(get_string('directoryalreadyexist','format_project',$directoryname), "edittitle.php?id=$id");
        }
        
        /* ディレクトリ名の変更 */
        // ディレクトリ名の取得
        if (! $basedir = make_upload_directory("$course->id")) {
            error("The site administrator needs to fix the file permissions");
        }
        
        // ファイル名のチェック
        if ($form->olddirectoryname != $directoryname) {
            // 古いディレクトリが存在しない
            if (!file_exists($basedir."/".$form->olddirectoryname)) {
                // DB内のディレクトリ名を変更
                if (! set_field("course_project_title", "directoryname", $directoryname, "id", $form->id)) {
                    error("Could not update the directory name!");
                }
            } else {
                $name = clean_filename($directoryname);
                if (file_exists($basedir."/".$name)) {
                    // 変更後のディレクトリが存在する
                    // echo(get_string('directoryalreadyexist','format_project',$directoryname));
                } else if (!rename($basedir."/".$form->olddirectoryname, $basedir."/".$name)) {
                    // ディレクトリをリネームできなかった
                    echo "<p align=\"center\">Error: could not rename {$form->olddirectoryname} to $name</p>";
                } else {
                    // DB内のディレクトリ名を変更
                    if (! set_field("course_project_title", "directoryname", $directoryname, "id", $form->id)) {
                        error("Could not update the directory name!");
                    }
                }
            }
            // セクション内のモジュールのリンクを書き換える
            project_format_rename_section_links($course, $section->id, $directoryname, true, true);
        }
        
        add_to_log($course->id, "course", "editprojecttitle", "format/project/edittitle.php?id=$sectiontitle->id", "$section->section");
        
        notify(get_string('updatesection', 'format_project'));
        
        redirect("../../view.php?id=$course->id");
    } else {
        $form = $sectiontitle;
        include('edittitle_form.php');
    }
    
    print_simple_box_end();

    print_footer($course);
?>
