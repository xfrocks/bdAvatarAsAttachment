<?php

class bdAvatarAsAttachment_Installer
{
	public static function install($existingAddOn, $addOnData)
	{
		$db = XenForo_Application::getDb();

		$db->insert('xf_content_type', array(
			'content_type' => 'bdavatarasattachment_user',
			'addon_id' => 'bdAvatarAsAttachment',
			'fields' => 'a:0:{}'
		));
		$db->insert('xf_content_type_field', array(
			'content_type' => 'bdavatarasattachment_user',
			'field_name' => 'attachment_handler_class',
			'field_value' => 'bdAvatarAsAttachment_AttachmentHandler_User'
		));

		XenForo_Model::create('XenForo_Model_ContentType')->rebuildContentTypeCache();
	}

	public static function uninstall()
	{
		$db = XenForo_Application::getDb();

		$db->delete('xf_content_type', array('addon_id = ?' => 'bdAvatarAsAttachment'));
		$db->delete('xf_content_type_field', array('content_type = ?' => 'bdavatarasattachment_user'));
	}

}
