<?php

class bdAvatarAsAttachment_XenForo_Model_Avatar extends XFCP_bdAvatarAsAttachment_XenForo_Model_Avatar
{
	protected static $_bdAvatarAsAttachment_enabled = 1;
	protected static $_bdAvatarAsAttachment_attachmentIds = array();
	protected static $_bdAvatarAsAttachment_users = array();

	public function bdAvatarAsAttachment_getAttachmentIds($userId)
	{
		if (isset(self::$_bdAvatarAsAttachment_attachmentIds[$userId]))
		{
			return self::$_bdAvatarAsAttachment_attachmentIds[$userId];
		}

		return false;
	}

	public function bdAvatarAsAttachment_isEnabled()
	{
		return $enabled;
	}

	public function bdAvatarAsAttachment_setEnabled($enabled)
	{
		self::$_bdAvatarAsAttachment_enabled += $enabled ? 1 : -1;
	}

	protected function _bdAvatarAsAttachment_getUserById($userId)
	{
		if (!isset(self::$_bdAvatarAsAttachment_users[$userId]))
		{
			$user = array();

			if ($userId == XenForo_Visitor::getUserId())
			{
				$user = XenForo_Visitor::getInstance()->toArray();
			}
			else
			{
				$user = $this->getModelFromCache('XenForo_Model_User')->getUserById($userId);
			}

			self::$_bdAvatarAsAttachment_users[$userId] = $user;
		}

		return self::$_bdAvatarAsAttachment_users[$userId];
	}

	public function applyAvatar($userId, $fileName, $imageType = false, $width = false, $height = false, $permissions = false)
	{
		$dwData = parent::applyAvatar($userId, $fileName, $imageType, $width, $height, $permissions);

		self::$_bdAvatarAsAttachment_users[$userId] = $dwData;

		return $dwData;
	}

	public function getAvatarFilePath($userId, $size, $externalDataPath = null)
	{
		if (self::$_bdAvatarAsAttachment_enabled)
		{
			$user = $this->_bdAvatarAsAttachment_getUserById($userId);

			if (!empty($user['avatar_date']) AND $user['avatar_date'] == 1 AND !empty($user['gravatar']))
			{
				return bdAvatarAsAttachment_Helper_AvatarUrl::getPath($user, $size);
			}
		}

		return parent::getAvatarFilePath($userId, $size, $externalDataPath);
	}

	public function recropAvatar($userId, $x, $y)
	{
		$attachmentModel = $this->getModelFromCache('XenForo_Model_Attachment');
		$isCallable = is_callable(array(
			$attachmentModel,
			'bdAttachmentStore_useTempFile'
		));

		if ($isCallable)
		{
			$attachmentModel->bdAttachmentStore_useTempFile(true);
		}

		$dwData = parent::recropAvatar($userId, $x, $y);

		if ($isCallable)
		{
			$attachmentModel->bdAttachmentStore_useTempFile(false);
		}

		self::$_bdAvatarAsAttachment_users[$userId] = $dwData;

		return $dwData;
	}

	protected function _writeAvatar($userId, $size, $tempFile)
	{
		if (self::$_bdAvatarAsAttachment_enabled)
		{
			$upload = new XenForo_Upload(bdAvatarAsAttachment_Option::getUploadFileNamePrefix() . sprintf('avatar%d%s.jpg', $userId, $size), $tempFile);
			$dataId = $this->getModelFromCache('XenForo_Model_Attachment')->insertUploadedAttachmentData($upload, $userId);

			$attachmentDw = XenForo_DataWriter::create('XenForo_DataWriter_Attachment');
			$attachmentDw->set('data_id', $dataId);
			$attachmentDw->set('content_type', 'bdavatarasattachment_user');
			$attachmentDw->set('content_id', $userId);
			$attachmentDw->set('unassociated', 0);
			$attachmentDw->save();
			$attachmentId = $attachmentDw->get('attachment_id');

			self::$_bdAvatarAsAttachment_attachmentIds[$userId][$size] = $attachmentId;

			return $dataId;
		}

		return parent::_writeAvatar($userId, $size, $tempFile);
	}

}
