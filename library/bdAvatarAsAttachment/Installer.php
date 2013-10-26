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

		while (true)
		{
			$users = $db->fetchAll('
				SELECT user_id
				FROM `xf_user`
				WHERE avatar_date = 1
				LIMIT 100;
			');

			if (empty($users))
			{
				break;
			}

			$userIds = array();
			$attachmentIds = array();
			$dataIds = array();

			foreach ($users as $user)
			{
				$userIds[] = $user['user_id'];

				$attachments = $db->fetchAll('
					SELECT attachment_id, data_id
					FROM `xf_attachment`
					WHERE content_type = ?
						AND content_id = ?
				', array(
					'bdavatarasattachment_user',
					$user['user_id']
				));

				foreach ($attachments as $attachment)
				{
					$attachmentIds[] = $attachment['attachment_id'];
					$dataIds[] = $attachment['data_id'];
				}
			}

			$db->update('xf_user', array(
				'avatar_date' => 0,
				'avatar_width' => 0,
				'avatar_height' => 0,
				'gravatar' => ''
			), 'user_id IN (' . $db->quote($userIds) . ')');
			$db->delete('xf_attachment', 'attachment_id IN (' . $db->quote($attachmentIds) . ')');
			$db->update('xf_attachment_data', array('attach_count' => 0), 'data_id IN (' . $db->quote($dataIds) . ')');
		}
	}

}
