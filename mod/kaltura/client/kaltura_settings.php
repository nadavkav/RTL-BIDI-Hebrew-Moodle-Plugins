<?php
/*
 * @file
 * This file defines some data needed by the module
 *
 * changing data in this file might cause the module to stop working
 * or working not as expected.
 *
 * It is strongly advised not to change the settings in this file
 *
 */
define('ENTRY_TYPE_ROUGHCUT', 2);
define('WORK_WITH_KSHOW', FALSE);

  define('KalturaSettings_SERVER_URL', "http://www.kaltura.com/");
  define('KalturaSettings_UICONF_ID', 600);
  define('KalturaSettings_BASE_WIDGET_ID', 600);
  define('KalturaSettings_ANONYMOUS_USER_ID', "Anonymous");
  define('KalturaSettings_CW_REGULAR_UICONF_ID', 1002217);
  define('KalturaSettings_CW_MIX_UICONF_ID', 1002225);
  define('KalturaSettings_SE_UICONF_ID', 1002226);
  define('KalturaSettings_PLAY_MIX_DARK_UICONF_ID', 1002259);
  define('KalturaSettings_PLAY_MIX_LIGHT_UICONF_ID', 1002260);
  define('KalturaSettings_PLAY_REGULAR_DARK_UICONF_ID', 1002213);
  define('KalturaSettings_PLAY_REGULAR_LIGHT_UICONF_ID', 1002216);
  define('KalturaSettings_PLAY_VIDEO_PRESENTATION_UICONF_ID', 1003069);
  define('KalturaSettings_CW_COMMENTS_UICONF_ID', 610);
  define('KalturaSettings_DEFAULT_VIDEO_PLAYER_UICONF', 'dark');
  define('KalturaSettings_DEFAULT_AUDIO_PLAYER_UICONF', 'dark');
  define('KalturaSettings_DEFAULT_VIEWPLAYLIST_PLAYER_UICONF', 'dark');
  define('KalturaSettings_DEFAULT_RC_PLAYER_UICONF', 'dark');
  define('KalturaSettings_DEFAULT_COMMENT_PLAYER_UICONF', 'dark');

class KalturaSettings
{
  var $kdp_widgets = array(
      'audio' => array(
        'dark' => array( 'view_uiconf' => '605', 'remix_uiconf' => '604', 'preview_image' => 'dark-player.jpg' ),
        'gray' => array( 'view_uiconf' => '607', 'remix_uiconf' => '606', 'preview_image' => 'gray-player.jpg' ),
        'white-blue' => array( 'view_uiconf' => '609', 'remix_uiconf' => '608', 'preview_image' => 'white-blue-player.jpg' ),
      ),
      'viewplaylist' => array(
        'dark' => array( 'view_uiconf' => '605', 'remix_uiconf' => '604', 'preview_image' => 'dark-player.jpg' ),
        'gray' => array( 'view_uiconf' => '607', 'remix_uiconf' => '606', 'preview_image' => 'gray-player.jpg' ),
        'white-blue' => array( 'view_uiconf' => '609', 'remix_uiconf' => '608', 'preview_image' => 'white-blue-player.jpg' ),
      ),
      'video' => array(
        'dark' => array( 'view_uiconf' => '605', 'remix_uiconf' => '604', 'preview_image' => 'dark-player.jpg' ),
        'gray' => array( 'view_uiconf' => '607', 'remix_uiconf' => '606', 'preview_image' => 'gray-player.jpg' ),
        'white-blue' => array( 'view_uiconf' => '609', 'remix_uiconf' => '608', 'preview_image' => 'white-blue-player.jpg' ),
      ),              
      'roughcut' => array(
        'dark' => array( 'view_uiconf' => '605', 'remix_uiconf' => '604', 'preview_image' => 'dark-player.jpg' ),
        'gray' => array( 'view_uiconf' => '607', 'remix_uiconf' => '606', 'preview_image' => 'gray-player.jpg' ),
        'white-blue' => array( 'view_uiconf' => '609', 'remix_uiconf' => '608', 'preview_image' => 'white-blue-player.jpg' ),
      ),              
      'comment' => array(
        'dark' => array( 'view_uiconf' => '605', 'remix_uiconf' => '604', 'preview_image' => 'dark-player.jpg' ),
        'gray' => array( 'view_uiconf' => '607', 'remix_uiconf' => '606', 'preview_image' => 'gray-player.jpg' ),
        'white-blue' => array( 'view_uiconf' => '609', 'remix_uiconf' => '608', 'preview_image' => 'white-blue-player.jpg' ),
      ),
  );
  
  var $media_types_map = array(
    1 => 'Video',
    2 => 'Photo',
    5 => 'Audio',
    6 => 'Remix',
  );
} 
