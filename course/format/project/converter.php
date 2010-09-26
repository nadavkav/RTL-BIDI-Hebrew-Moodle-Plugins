<?php

/**
 * サイトファイル (course_id == 1) を除くための
 * array_filter() 用コールバック関数
 */
function is_not_sitefile($relative_path)
{
	return strncmp('1/', $relative_path, 2) != 0;
}
/**
 * 暫定処置のためのスイッチ
 * Moodleライブラリによって複製されない問題バンクのリンクを
 * 誤って書き換えてしまわないようにフィルタリングする。
 */
define('DO_NOT_RENAME_UNIQUE_QUESTIONS', true);

// TODO:
//	quistionの(quizではなく)元IDとリストア先IDを比較して
//	同一ならリンク張り替えおよびファイルコピーをしない


/**
 * resource用画像リンク関数
 */
 
function resource_rename_links(&$course,&$mod,$oldname,$newname) {   
    global $CFG;

    $updates = false;
    
    // summaryとalltext
    $old = trim('file.php/'.$oldname);
    $new = trim('file.php/'.$newname);

    // reference
    $refold = preg_replace('|^'.$course->id.'/'.'|','',$oldname);
    $refnew = preg_replace('|^'.$course->id.'/'.'|','',$newname);
    
    $sql = "SELECT r.id, r.reference, r.name, r.summary, r.alltext, cm.id AS cmid
             FROM {$CFG->prefix}resource r,
                  {$CFG->prefix}course_modules cm,
                  {$CFG->prefix}modules m
             WHERE r.course    = '{$course->id}'
               AND m.name      = 'resource'
               AND cm.module   = m.id
               AND cm.instance = r.id
               AND cm.id = {$mod->id}";
    if ($resources = get_records_sql($sql)) {
        foreach ($resources as $resource) {
            $r = new object();
            $r->id = $resource->id;
            if ($resource->summary || $resource->alltext) {
                $r->summary = addslashes(preg_replace('|/'.preg_quote($old, '|').'|', '/'.$new, $resource->summary));
                $r->alltext = addslashes(preg_replace('|/'.preg_quote($old, '|').'|', '/'.$new, $resource->alltext));
            }
            if ($resource->reference) {
                $r->reference = str_replace($refold, $refnew, $resource->reference);
            }
            
            if ($r->summary !== '' || $r->alltext !== '' || $r->reference !== '') {
                $updates = true;
                if (!update_record('resource', $r)) {
                    error("Error updating resource with ID $r->id.");
                }
            }
        }
    }
    return $updates;
}

function resource_get_links(&$course, &$mod, $exclude_sitefile = true) {
    global $CFG;
    
    $sql = "SELECT r.id, r.reference, r.name, r.summary, r.alltext, cm.id AS cmid
             FROM {$CFG->prefix}resource r,
                  {$CFG->prefix}course_modules cm,
                  {$CFG->prefix}modules m
             WHERE r.course    = '{$course->id}'
               AND m.name      = 'resource'
               AND cm.module   = m.id
               AND cm.instance = r.id
               AND cm.id = {$mod->id}";
    if ($resources = get_records_sql($sql)) {
        // 各テキストの取得
        $links = array();
        foreach ($resources as $resource) {
            preg_match_all('|/file.php/([^\"]+)|',$resource->alltext,$matches);
            $links = array_merge($links, $matches[1]);
            preg_match_all('|/file.php/([^\"]+)|',$resource->summary,$matches);
            $links = array_merge($links, $matches[1]);
            if (trim($resource->reference) && stristr($resource->reference, 'http://') === false || stristr($resources->reference, 'file.php/')) {
                $links[] = $course->id.'/'.$resource->reference;
            }
        }

        // 2008-10-29 暫定処置
        if ($exclude_sitefile) {
          $links = array_filter($links, 'is_not_sitefile');
        }

        return $links;
    } else {
        return null;
    }
}


/**
 * コースセクション
 */
function course_section_rename_links(&$course,&$mod,$oldname,$newname) {
    $old = trim('file.php/'.$oldname);
    $new = trim('file.php/'.$newname);
    
    if (!$section = get_record("course_sections", "id", $mod->section) ) {
        return false;
    }
    
    $section->summary = addslashes(preg_replace('|/'.preg_quote($old, '|').'|', '/'.$new, $section->summary));
    
    if ($section->summary !== '') {
        $updates = true;
        if (!update_record('course_sections', $section)) {
            error("Error updating course section with ID $mod->section.");
        }
    }
    return $updates;
}

function course_section_get_links(&$course, &$mod, $exclude_sitefile = true) {
    if (!$section = get_record("course_sections", "id", $mod->section) ) {
        return false;
    }
    
    // 各テキストの取得
    if (preg_match_all('|/file.php/([^\"]+)|',$section->summary,$matches)) {
		
		// 2008-10-29 暫定処置
		if ($exclude_sitefile) {
			if (is_not_sitefile($matches[1])) {
				return null;
			}
		}
		
        return $matches[1];
    } else {
        return null;
    }    
}



/**
 * Quiz モジュール
 */
function quiz_rename_links(&$course,&$mod,$oldname,$newname) {
    $old = trim('file.php/'.$oldname);
    $new = trim('file.php/'.$newname);
    
    // 概要
    $select = "id = $mod->instance";
    if (!$quiz = get_record_select('quiz', $select, 'id,intro,questions')) {
        return false;
    }
    
    $quiz->intro = addslashes(preg_replace('|/'.preg_quote($old, '|').'|', '/'.$new, $quiz->intro));
    
    if ($quiz->intro !== '') {
        $updates = true;
        if (!update_record('quiz', $quiz)) {
            error("Error updating quiz module with ID $mod->instance.");
        }
    }
    
    //
    // 暫定処置: 問題バンクはインスタンスが複製されないので、リンク書き換えしない
    //
    return $updates;
    
    
    // 問題IDの取得
    $questionids = split(',',$quiz->questions);
    
    // 問題バンク
    foreach ($questionids as $id) {
    	
        // 各問題から抽出
        $select = "id = $id";
        if (!$questions = get_records_select('question', $select, 'id', 'id,questiontext,generalfeedback')) {
            continue;
        }
        if (is_array($questions)) {
            foreach ($questions as $question) {
                $question->questiontext = addslashes(preg_replace('|/'.preg_quote($old, '|').'|', '/'.$new, $question->questiontext));
                $question->generalfeedback = addslashes(preg_replace('|/'.preg_quote($old, '|').'|', '/'.$new, $question->generalfeedback));
            
                if ($question->questiontext !== '' || $question->generalfeedback !== '') {
                    $updates = true;
                    if (!update_record('question', $question)) {
                        error("Error updating question module with ID $mod->instance.");
                    }
                }
            }
        }
        
        // 各回答から抽出
        $select = "question = $id";
        if (!$answers = get_records_select('question_answers', $select, 'id', 'id,feedback,answer')) {
            continue;
        }
        if (is_array($answers)) {
            foreach ($answers as $answer) {
                $answer->feedback = addslashes(preg_replace('|/'.preg_quote($old, '|').'|', '/'.$new, $answer->feedback));
                $answer->answer = addslashes(preg_replace('|/'.preg_quote($old, '|').'|', '/'.$new, $answer->answer));
                if ($answer->feedback !== '' || $answer->answer !== '') {
                    $updates = true;
                    if (!update_record('question_answers', $answer)) {
                        error("Error updating answer module with ID $mod->instance.");
                    }
                }
            }
        }
    }            
    return $updates;
}

function quiz_get_links(&$course, &$mod, $exclude_sitefile = true) {
    $returns = array();
    
    // 概要
    $select = "id = $mod->instance";
    if (!$quiz = get_record_select('quiz', $select, 'id,intro,questions')) {
        return false;
    }
    
    // 概要の取得
    if (preg_match_all('|/file.php/([^\"]+)|',$quiz->intro,$matches)) {
        $returns = array_merge($returns, $matches[1]);
    }
    
    // 問題IDの取得
    $questionids = split(',',$quiz->questions);
    foreach ($questionids as $id) {
        // 各問題から抽出
        $select = "id = $id";
        if (!$questions = get_records_select('question', $select, 'id', 'id,questiontext,generalfeedback')) {
            continue;
        }
        if (is_array($questions)) {
            foreach ($questions as $question) {
                if (preg_match_all('|/file.php/([^\"]+)|',$question->questiontext,$matches)) {
                    $returns = array_merge($returns, $matches[1]);
                }
                if (preg_match_all('|/file.php/([^\"]+)|',$question->generalfeedback,$matches)) {
                    $returns = array_merge($returns, $matches[1]);
                }
            }
        }
        // 各回答から取得
        $select = "question = $id";
        if (!$answers = get_records_select('question_answers', $select, 'id', 'id,feedback,answer')) {
            continue;
        }
        if (is_array($answers)) {
            foreach ($answers as $answer) {
                if (preg_match_all('|/file.php/([^\"]+)|',$answer->feedback,$matches)) {
                    $returns = array_merge($returns, $matches[1]);
                }
                if (preg_match_all('|/file.php/([^\"]+)|',$answer->answer,$matches)) {
                    $returns = array_merge($returns, $matches[1]);
                }
            }
        }
    }
    if (count($returns) > 0) {
		
		// 2008-10-29 暫定処置
		if ($exclude_sitefile) {
			$returns = array_filter($returns, 'is_not_sitefile');
		}
		
        return $returns;
    } else {
        return null;
    }
}


/**
 * QuizSplit モジュール
 */
function quizsplit_rename_links(&$course,&$mod,$oldname,$newname) {
    $old = trim('file.php/'.$oldname);
    $new = trim('file.php/'.$newname);
    
    // 概要
    $select = "id = $mod->instance";
    if (!$quiz = get_record_select('quizsplit', $select, 'id,intro,questions')) {
        return false;
    }
    
    $quiz->intro = addslashes(preg_replace('|/'.preg_quote($old, '|').'|', '/'.$new, $quiz->intro));
    
    if ($quiz->intro !== '') {
        $updates = true;
        if (!update_record('quizsplit', $quiz)) {
            error("Error updating quiz split module with ID $mod->instance.");
        }
    }
    
    // 問題IDの取得
    $questionids = split(',',$quiz->questions);
    
    // チャプター
    foreach ($questionids as $id) {
        // 各問題から抽出
        $select = "id = $id";
        if (!$questions = get_records_select('question', $select, 'id', 'id,questiontext,generalfeedback')) {
            continue;
        }
        if (is_array($questions)) {
            foreach ($questions as $question) {
                $question->questiontext = addslashes(preg_replace('|/'.preg_quote($old, '|').'|', '/'.$new, $question->questiontext));
                $question->generalfeedback = addslashes(preg_replace('|/'.preg_quote($old, '|').'|', '/'.$new, $question->generalfeedback));
            
                if ($question->questiontext !== '' || $question->generalfeedback !== '') {
                    $updates = true;
                    if (!update_record('question', $question)) {
                        error("Error updating question module with ID $mod->instance.");
                    }
                }
            }
        }
        
        // 各回答から取得
        $select = "question = $id";
        if (!$answers = get_records_select('question_answers', $select, 'id', 'id,feedback,answer')) {
            continue;
        }
        if (is_array($answers)) {
            foreach ($answers as $answer) {
                $answer->feedback = addslashes(preg_replace('|/'.preg_quote($old, '|').'|', '/'.$new, $answer->feedback));
                $answer->answer = addslashes(preg_replace('|/'.preg_quote($old, '|').'|', '/'.$new, $answer->answer));

                if ($answer->feedback !== '') {
                    $updates = true;
                    if (!update_record('question_answers', $answer)) {
                        error("Error updating answer module with ID $mod->instance.");
                    }
                }
            }
        }
    }            
    return $updates;
}

function quizsplit_get_links(&$course, &$mod, $exclude_sitefile = true) {
    $returns = array();
    
    // 概要
    $select = "id = $mod->instance";
    if (!$quiz = get_record_select('quizsplit', $select, 'id,intro,questions')) {
        return false;
    }
    
    // 概要の取得
    if (preg_match_all('|/file.php/([^\"]+)|',$quiz->intro,$matches)) {
        $returns = array_merge($returns, $matches[1]);
    }
    
    // 問題IDの取得
    $questionids = split(',',$quiz->questions);
    foreach ($questionids as $id) {
        // 各問題から抽出
        $select = "id = $id";
        if (!$questions = get_records_select('question', $select, 'id', 'id,questiontext,generalfeedback')) {
            continue;
        }
        if (is_array($questions)) {
            foreach ($questions as $question) {
                if (preg_match_all('|/file.php/([^\"]+)|',$question->questiontext,$matches)) {
                    $returns = array_merge($returns, $matches[1]);
                }
                if (preg_match_all('|/file.php/([^\"]+)|',$question->generalfeedback,$matches)) {
                    $returns = array_merge($returns, $matches[1]);
                }
            }
        }
        // 各回答から取得
        $select = "question = $id";
        if (!$answers = get_records_select('question_answers', $select, 'id', 'id,feedback,answer')) {
            continue;
        }
        if (is_array($answers)) {
            foreach ($answers as $answer) {
                if (preg_match_all('|/file.php/([^\"]+)|',$answer->feedback,$matches)) {
                    $returns = array_merge($returns, $matches[1]);
                }
                if (preg_match_all('|/file.php/([^\"]+)|',$answer->answer,$matches)) {
                    $returns = array_merge($returns, $matches[1]);
                }
            }
        }
    }
    if (count($returns) > 0) {

        // 2008-10-29 暫定処置
        if ($exclude_sitefile) {
            $returns = array_filter($returns, 'is_not_sitefile');
        }

        return $returns;
    } else {
        return null;
    }
}


/**
 * Glossary モジュール
 */
function glossary_rename_links(&$course,&$mod,$oldname,$newname) {
    $old = trim('file.php/'.$oldname);
    $new = trim('file.php/'.$newname);
    
    // 概要
    $select = "id = $mod->instance";
    if (!$glossary = get_record_select('glossary', $select, 'id,intro')) {
        return false;
    }
    $glossary->intro = addslashes(preg_replace('|/'.preg_quote($old, '|').'|', '/'.$new, $glossary->intro));
    
    if ($glossary->intro !== '') {
        $updates = true;
        if (!update_record('glossary', $glossary)) {
            error("Error updating glossary module with ID $mod->instance.");
        }
    }
    
    
    // チャプター
    $select = "glossaryid = $mod->instance";
    if (!$entries = get_records_select('glossary_entries', $select, 'id', 'id,definition')) {
        return false;
    }
    
    if (is_array($entries)) {
        foreach ($entries as $entry) {
            $entry->definition = addslashes(preg_replace('|/'.preg_quote($old, '|').'|', '/'.$new, $entry->definition));
            
            if ($entry->definition !== '') {
                $updates = true;
                if (!update_record('glossary_entries', $entry)) {
                    error("Error updating glossary module with ID $mod->instance.");
                }
            }
        }
    }
    return $updates;
}
function glossary_get_links(&$course, &$mod, $exclude_sitefile = true) {
    $returns = array();
    
    // 概要
    $select = "id = $mod->instance";
    if (!$glossary = get_record_select('glossary', $select, 'id,intro')) {
        return false;
    }
    
    // 概要の取得
    if (preg_match_all('|/file.php/([^\"]+)|',$glossary->intro,$matches)) {
        $returns = array_merge($returns, $matches[1]);
    }
    
    // エントリから抽出
    $select = "glossaryid = $mod->instance";
    if (!$entries = get_records_select('glossary_entries', $select, 'id', 'id,definition')) {
        return false;
    }
    if (is_array($entries)) {
        foreach ($entries as $entry) {
            // 各テキストの取得
            if (preg_match_all('|/file.php/([^\"]+)|',$entry->definition,$matches)) {
                $returns = array_merge($returns, $matches[1]);
            }
        }
    }
    if (count($returns) > 0) {

        // 2008-10-29 暫定処置
        if ($exclude_sitefile) {
            $returns = array_filter($returns, 'is_not_sitefile');
        }

        return $returns;
    } else {
        return null;
    }
}


/**
 * Book モジュール
 */
function book_rename_links(&$course,&$mod,$oldname,$newname) {
    $updates = true;
    
    $old = trim('file.php/'.$oldname);
    $new = trim('file.php/'.$newname);

    // 概要
    $select = "id = $mod->instance";
    if (!$book = get_record_select('book', $select, 'id,summary')) {
        return false;
    }
    $book->summary = addslashes(preg_replace('|/'.preg_quote($old, '|').'|', '/'.$new, $book->summary));
    
    if ($book->summary !== '') {
        if (!update_record('book', $book)) {
            error("Error updating book module with ID $mod->instance.");
        }
    }
    
    // チャプター
    $select = "bookid = $mod->instance";
    if (!$chapters = get_records_select('book_chapters', $select, 'pagenum', 'id,content')) {
        return false;
    }
    
    if (is_array($chapters)) {
        foreach ($chapters as $chapter) {
            $chapter->content = addslashes(preg_replace('|/'.preg_quote($old, '|').'|', '/'.$new, $chapter->content));
            
            if ($chapter->content !== '') {
                if (!update_record('book_chapters', $chapter)) {
                    error("Error updating book module with ID $mod->instance.");
                }
            }
        }
    }
    return $updates;
}
function book_get_links(&$course, &$mod, $exclude_sitefile = true) {
    $returns = array();
    
    // 概要
    $select = "id = $mod->instance";
    if (!$book = get_record_select('book', $select, 'id,summary')) {
        return false;
    }
    
    // 概要の取得
    if (preg_match_all('|/file.php/([^\"]+)|',$book->summary,$matches)) {
        $returns = array_merge($returns, $matches[1]);
    }
    
    
    // チャプターから抽出
    $select = "bookid = $mod->instance";
    if (!$chapters = get_records_select('book_chapters', $select, 'pagenum', 'id,content')) {
        return false;
    }
    
    if (is_array($chapters)) {
        foreach ($chapters as $chapter) {
            // 各テキストの取得
            if (preg_match_all('|/file.php/([^\"]+)|',$chapter->content,$matches)) {
                $returns = array_merge($returns, $matches[1]);
            }
        }
    }
    if (count($returns) > 0) {

        // 2008-10-29 暫定処置
        if ($exclude_sitefile) {
            $returns = array_filter($returns, 'is_not_sitefile');
        }

        return $returns;
    } else {
        return null;
    }
}


/**
 * Feedback モジュール
 */
function feedback_rename_links(&$course,&$mod,$oldname,$newname) {
    $old = trim('file.php/'.$oldname);
    $new = trim('file.php/'.$newname);
    
    // 概要
    $select = "id = $mod->instance";
    if (!$feedback = get_record_select('feedback', $select, 'id,summary,page_after_submit')) {
        return false;
    }
    $feedback->summary = addslashes(preg_replace('|/'.preg_quote($old, '|').'|', '/'.$new, $feedback->summary));
    $feedback->page_after_submit = addslashes(preg_replace('|/'.preg_quote($old, '|').'|', '/'.$new, $feedback->page_after_submit));
    
    if ($feedback->summary !== '' || $feedback->page_after_submit !== '') {
        $updates = true;
        if (!update_record('feedback', $feedback)) {
            error("Error updating feedback module with ID $mod->instance.");
        }
    }
    
    return $updates;
}
function feedback_get_links(&$course, &$mod, $exclude_sitefile = true) {
    $returns = array();
    
    // 概要
    $select = "id = $mod->instance";
    if (!$feedback = get_record_select('feedback', $select, 'id,summary,page_after_submit')) {
        return false;
    }
    
    // 概要の取得
    if (preg_match_all('|/file.php/([^\"]+)|',$feedback->summary,$matches)) {
        $returns = array_merge($returns, $matches[1]);
    }
    if (preg_match_all('|/file.php/([^\"]+)|',$feedback->page_after_submit,$matches)) {
        $returns = array_merge($returns, $matches[1]);
    }
    
    if (count($returns) > 0) {

        // 2008-10-29 暫定処置
        if ($exclude_sitefile) {
            $returns = array_filter($returns, 'is_not_sitefile');
        }

        return $returns;
    } else {
        return null;
    }
}

?>
