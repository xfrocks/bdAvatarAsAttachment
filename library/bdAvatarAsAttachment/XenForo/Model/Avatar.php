<?php

class bdAvatarAsAttachment_XenForo_Model_Avatar extends XFCP_bdAvatarAsAttachment_XenForo_Model_Avatar
{
	protected static $_attachmentIds = array();
	protected static $_users = array();

	public function bdAvatarAsAttachment_getAttachmentIds($userId)
	{
		if (isset(self::$_attachmentIds[$userId]))
		{
			return self::$_attachmentIds[$userId];
		}

		return false;
	}

	protected function _bdAvatarAsAttachment_getUserById($userId)
	{
		if (!isset(self::$_users[$userId]))
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

			self::$_users[$userId] = $user;
		}

		return self::$_users[$userId];
	}

	public function getAvatarFilePath($userId, $size, $externalDataPath = null)
	{
		$user = $this->_bdAvatarAsAttachment_getUserById($userId);

		if (!empty($user['avatar_date']) AND $user['avatar_date'] == 1 AND !empty($user['gravatar']))
		{
			return bdAvatarAsAttachment_Helper_AvatarUrl::getPath($user, $size);
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

		$response = parent::recropAvatar($userId, $x, $y);

		if ($isCallable)
		{
			$attachmentModel->bdAttachmentStore_useTempFile(false);
		}

		return $response;
	}

	protected function _writeAvatar($userId, $size, $tempFile)
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

		self::$_attachmentIds[$userId][$size] = $attachmentId;

		return $dataId;
	}

}
