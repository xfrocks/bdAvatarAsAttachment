<?php

class bdAvatarAsAttachment_CacheRebuilder extends XenForo_CacheRebuilder_Abstract
{
	public function getRebuildMessage()
	{
		return new XenForo_Phrase('users');
	}

	public function rebuild($position = 0, array &$options = array(), &$detailedMessage = '')
	{
		$options['batch'] = isset($options['batch']) ? $options['batch'] : 50;
		$options['batch'] = max(1, $options['batch']);

		if (empty($options['direction']))
		{
			// no `direction` option?
			return true;
		}

		/* @var $attachmentModel XenForo_Model_Attachment */
		$attachmentModel = XenForo_Model::create('XenForo_Model_Attachment');

		/* @var $avatarModel XenForo_Model_Avatar */
		$avatarModel = XenForo_Model::create('XenForo_Model_Avatar');

		/* @var $userModel XenForo_Model_User */
		$userModel = XenForo_Model::create('XenForo_Model_User');

		$userIds = $userModel->getUserIdsInRange($position, $options['batch']);
		if (sizeof($userIds) == 0)
		{
			return true;
		}

		$users = $userModel->getUsersByIds($userIds, array('join' => XenForo_Model_User::FETCH_USER_PROFILE));

		if (is_callable(array(
			$attachmentModel,
			'bdAttachmentStore_useTempFile'
		)))
		{
			$attachmentModel->bdAttachmentStore_useTempFile(true);
		}

		foreach ($users as $userId => &$user)
		{
			$position = $userId;

			if (empty($user['avatar_date']))
			{
				// no custom avatar, ignore
				continue;
			}

			switch ($options['direction'])
			{
				case 'avatar':
					if ($user['avatar_date'] > 1286313540)
					{
						// avatar is uploaded sometime after the first beta release of XenForo
						// http://xenforo.com/community/threads/xenforo-1-0-0-beta-1-released.4858/
						continue 2;
					}
					break;
				case 'attachment':
					if ($user['avatar_date'] == 1)
					{
						// avatar is on attachment already, ignore
						continue 2;
					}
					break;
			}

			$originalFilePath = $avatarModel->getAvatarFilePath($userId, 'l');
			if (!filesize($originalFilePath))
			{
				continue;
			}
			$filePath = tempnam(XenForo_Helper_File::getTempDir(), 'xf');
			if (!copy($originalFilePath, $filePath))
			{
				continue;
			}

			if ($options['direction'] == 'avatar')
			{
				$avatarModel->bdAvatarAsAttachment_setEnabled(false);
			}

			$avatarModel->applyAvatar($userId, $filePath);
			if (!empty($user['avatar_crop_x']) OR !empty($user['avatar_crop_y']))
			{
				$avatarModel->recropAvatar($userId, $user['avatar_crop_x'], $user['avatar_crop_y']);
			}

			if ($options['direction'] == 'avatar')
			{
				$avatarModel->bdAvatarAsAttachment_setEnabled(true);
			}

			@unlink($filePath);
		}

		$detailedMessage = XenForo_Locale::numberFormat($position);

		return $position;
	}

}
