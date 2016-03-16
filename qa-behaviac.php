<?php

/*
	Question2Answer (c) Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/wysiwyg-editor/qa-wysiwyg-editor.php
	Version: See define()s at top of qa-include/qa-base.php
	Description: Editor module class for WYSIWYG editor plugin


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/


class qa_behaviac
{

    var $urltoroot;

    function load_module($directory, $urltoroot)
    {
        $this->urltoroot = $urltoroot;
    }


    function option_default($option)
    {
        if ($option == 'wysiwyg_editor_upload_max_size') {
            require_once QA_INCLUDE_DIR . 'qa-app-upload.php';

            return min(qa_get_max_upload_size(), 1048576);
        }
    }


    function bytes_to_mega_html($bytes)
    {
        return qa_html(number_format($bytes / 1048576, 1));
    }


    function admin_form(&$qa_content)
    {
        require_once QA_INCLUDE_DIR . 'qa-app-upload.php';

        $saved = false;

        if (qa_clicked('wysiwyg_editor_save_button')) {
            qa_opt('wysiwyg_editor_ui_color', qa_post_text('wysiwyg_editor_ui_color_field'));
            qa_opt('wysiwyg_editor_ace_theme', qa_post_text('wysiwyg_editor_ace_theme'));
            qa_opt('wysiwyg_editor_upload_images', (int)qa_post_text('wysiwyg_editor_upload_images_field'));
            qa_opt('wysiwyg_editor_upload_all', (int)qa_post_text('wysiwyg_editor_upload_all_field'));
            qa_opt('wysiwyg_editor_upload_max_size', min(qa_get_max_upload_size(), 1048576 * (float)qa_post_text('wysiwyg_editor_upload_max_size_field')));
            $saved = true;
        }

        qa_set_display_rules($qa_content, array(
            'wysiwyg_editor_upload_all_display' => 'wysiwyg_editor_upload_images_field',
            'wysiwyg_editor_upload_max_size_display' => 'wysiwyg_editor_upload_images_field',
        ));

        $editor_themes = array('ambiance', 'chaos', 'chrome', 'clouds', 'clouds_midnight', 'cobalt', 'crimson_editor', 'dawn', 'dreamweaver', 'eclipse', 'github', 'idle_fingers', 'katzenmilch', 'kr', 'kuroir', 'merbivore', 'merbivore_soft', 'monokai', 'mono_industrial', 'pastel_on_dark', 'solarized_dark', 'solarized_light', 'terminal', 'textmate', 'tomorrow', 'tomorrow_night', 'tomorrow_night_blue', 'tomorrow_night_bright', 'tomorrow_night_eighties', 'twilight', 'vibrant_ink', 'xcode',);
        $theme_arr = array();
        foreach ($editor_themes as $theme) {
            $theme_arr[$theme] = ucwords(preg_replace('/[^a-zA-Z0-9]+/', ' ', $theme));
        }

        return array(
            'ok' => $saved ? 'WYSIWYG editor settings saved' : null,

            'fields' => array(
                array(
                    'label' => 'UI Color',
                    'type' => 'number',
                    'value' => qa_opt('wysiwyg_editor_ui_color'),
                    'tags' => 'name="wysiwyg_editor_ui_color_field" id="wysiwyg_editor_ui_color_field"',
                ),
                array(
                    'label' => 'Allow images to be uploaded',
                    'type' => 'checkbox',
                    'value' => (int)qa_opt('wysiwyg_editor_upload_images'),
                    'tags' => 'name="wysiwyg_editor_upload_images_field" id="wysiwyg_editor_upload_images_field"',
                ),


                array(
                    'id' => 'wysiwyg_editor_upload_all_display',
                    'label' => 'Allow other content to be uploaded, e.g. Flash, PDF',
                    'type' => 'checkbox',
                    'value' => (int)qa_opt('wysiwyg_editor_upload_all'),
                    'tags' => 'name="wysiwyg_editor_upload_all_field"',
                ),

                array(
                    'id' => 'wysiwyg_editor_upload_max_size_display',
                    'label' => 'Maximum size of uploads:',
                    'suffix' => 'MB (max ' . $this->bytes_to_mega_html(qa_get_max_upload_size()) . ')',
                    'type' => 'number',
                    'value' => $this->bytes_to_mega_html(qa_opt('wysiwyg_editor_upload_max_size')),
                    'tags' => 'name="wysiwyg_editor_upload_max_size_field"',
                ),

                array(
                    'id' => 'wysiwyg_editor_ace_theme',
                    'label' => 'Choose a theme for ACE Editor',
                    'type' => 'select',
                    'value' => $theme_arr[qa_opt('wysiwyg_editor_ace_theme')],
                    'tags' => 'name="wysiwyg_editor_ace_theme"',
                    'options' => $theme_arr,
                ),

            ),

            'buttons' => array(
                array(
                    'label' => 'Save Changes',
                    'tags' => 'name="wysiwyg_editor_save_button"',
                ),
            ),
        );
    }


    function calc_quality($content, $format)
    {
        if ($format == 'html')
            return 1.0;
        elseif ($format == '')
            return 0.8;
        else
            return 0;
    }


    function get_field(&$qa_content, $content, $format, $fieldname, $rows /* $autofocus parameter deprecated */)
    {
        //$scriptsrc = "http://cdn-source.ckeditor.com/4.5.7/full-all/ckeditor.js";
        $scriptsrc = $this->urltoroot."ckeditor.js";
        $alreadyadded = false;

        if (isset($qa_content['script_src']))
            foreach ($qa_content['script_src'] as $testscriptsrc)
                if ($testscriptsrc == $scriptsrc)
                    $alreadyadded = true;

        if (!$alreadyadded) {
            $uploadimages = qa_opt('wysiwyg_editor_upload_images');
            $uploadall = $uploadimages && qa_opt('wysiwyg_editor_upload_all');

            $ui_color = strlen(qa_opt('wysiwyg_editor_ui_color')) ? qa_opt('wysiwyg_editor_ui_color') : '#eeeeee';

            $qa_content['script_src'][] = $scriptsrc;
            /*$qa_content['script_lines'][]=array(
                "qa_wysiwyg_editor_config={".
                    "uiColor: '".$ui_color."',".
                    "toolbar:[".
                    "['Bold','Italic','Underline','Strike'],".
                    //"['Font','FontSize'],".
                    //"['TextColor','BGColor'],".
                    "['Link','Unlink'],".
                    //"['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],".
                    "['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],".
                    "['Image','Flash','Smiley'],".
                    "['RemoveFormat', 'Maximize'],".
                    "['oEmbed']".
                "]".
                ", defaultLanguage:".qa_js(qa_opt('site_language')).
                ", skin:'moono'".
                ", toolbarCanCollapse:false".
                ", removePlugins:'elementspath'".
                ", resize_enabled:false".
                ", autogrow:true".
                ", entities:false".
                ($uploadimages ? (", filebrowserImageUploadUrl:".qa_js(qa_path('wysiwyg-editor-upload', array('qa_only_image' => true)))) : "").
                ($uploadall ? (", filebrowserUploadUrl:".qa_js(qa_path('wysiwyg-editor-upload'))) : "").
                "};"
            );*/

            $modes_arr = array('actionscript', 'apache_conf', 'asciidoc', 'batchfile', 'coffee', 'coldfusion', 'csharp', 'css', 'django', 'gitignore', 'groovy', 'html', 'html_ruby', 'ini', 'java', 'javascript', 'json', 'jsoniq', 'jsp', 'less', 'lua', 'markdown', 'mysql', 'objectivec', 'pgsql', 'php', 'plain_text', 'properties', 'python', 'ruby', 'sass', 'scala', 'snippets', 'sql', 'text', 'xml',);
            $modes = "[";
            foreach ($modes_arr as $mode) {
                $modes .= "['" . ucwords(preg_replace('/[^a-zA-Z0-9]+/', ' ', $mode)) . "','" . $mode . "'],";
            }
            $modes .= "]";

            $qa_content['script_lines'][] = array(
                "qa_wysiwyg_editor_config={
						uiColor: '" . $ui_color . "',
						title : false ,
//						removePlugins : 'specialchar, spellchecker, tabletools, pastetext, pastefromword' ,
						defaultLanguage : " . qa_js(qa_opt('site_language')) . " ,
						disableNativeSpellChecker : false ,
						extraPlugins : 'uploadimage,autolink',
						tabSpaces : 4 ,
						toolbar : [
									{ name: 'document', items: [ 'Source', '-', 'Save', 'NewPage', 'Preview', 'Print', '-', 'Templates' ] },
                                    { name: 'clipboard', items: [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ] },
                                    { name: 'editing', items: [ 'Find', 'Replace', '-', 'SelectAll', '-', 'Scayt' ] },
                                    { name: 'forms', items: [ 'Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField' ] },

                                    { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat' ] },
                                    { name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl', 'Language' ] },
                                    { name: 'links', items: [ 'Link', 'Unlink', 'Anchor' ] },
                                    { name: 'insert', items: [ 'Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe' ] },

                                    { name: 'styles', items: [ 'Styles', 'Format', 'Font', 'FontSize' ] },
                                    { name: 'colors', items: [ 'TextColor', 'BGColor' ] },
                                    { name: 'tools', items: [ 'Maximize', 'ShowBlocks' ] },
                                    { name: 'about', items: [ 'About' ] }
								] ,
						pbckcode : {
							    cls : '',
							    highlighter : 'PRETTIFY',
							    modes : " . $modes . ",
							    theme : " . qa_js(qa_opt('wysiwyg_editor_ace_theme')) . ",
							    tab_size : '4',
							    js : '" . qa_opt('site_url') . "qa-plugin/" . AMI_EXP_EDITOR_DIR_NAME . "/ace-min/'
							},
						removeButtons :'Cut,Copy,Paste,PasteText,PasteFromWord,Save,NewPage,Preview,Print,Templates,Form,Checkbox,Radio,TextField,Select,Textarea,Button,ImageButton,HiddenField,CreateDiv,Blockquote,BidiLtr,BidiRtl,Language,About,HorizontalRule,PageBreak,Iframe',

						toolbarCanCollapse:false ,
						removePlugins:'elementspath' ,
						resize_enabled:false ,
						autogrow:true ,
						entities:false ,
						imageUploadUrl:'',
						uploadUrl:" . qa_js(qa_path('wysiwyg-editor-upload', array('qa_only_image' => true, 'qa_json' => true))) . ",
						" . ($uploadimages ? ("filebrowserImageUploadUrl:" . qa_js(qa_path('wysiwyg-editor-upload', array('qa_only_image' => true)))) . "," : "") . "
						" . ($uploadall ? ("filebrowserUploadUrl:" . qa_js(qa_path('wysiwyg-editor-upload'))) . "," : "") . "
				};"
            );
        }

        if ($format == 'html')
            $html = $content;
        else
            $html = qa_html($content, true);

        return array(
            'tags' => 'name="' . $fieldname . '"',
            'value' => qa_html($html),
            'rows' => $rows,
        );
    }


    function load_script($fieldname)
    {
        return "qa_ckeditor_" . $fieldname . "=CKEDITOR.replace(" . qa_js($fieldname) . ", window.qa_wysiwyg_editor_config);";
    }


    function focus_script($fieldname)
    {
        return "qa_ckeditor_" . $fieldname . ".focus();";
    }


    function update_script($fieldname)
    {
        return "qa_ckeditor_" . $fieldname . ".updateElement();";
    }


    function read_post($fieldname)
    {
        $html = qa_post_text($fieldname);

        $htmlformatting = preg_replace('/<\s*\/?\s*(br|p)\s*\/?\s*>/i', '', $html); // remove <p>, <br>, etc... since those are OK in text

        if (preg_match('/<.+>/', $htmlformatting)) // if still some other tags, it's worth keeping in HTML
            return array(
                'format' => 'html',
                'content' => qa_sanitize_html($html, false, true), // qa_sanitize_html() is ESSENTIAL for security
            );

        else { // convert to text
            $viewer = qa_load_module('viewer', '');

            return array(
                'format' => '',
                'content' => $viewer->get_text($html, 'html', array())
            );
        }
    }

}


/*
	Omit PHP closing tag to help avoid accidental output
*/