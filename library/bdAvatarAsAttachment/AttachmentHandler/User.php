<?php

class bdAvatarAsAttachment_AttachmentHandler_User extends XenForo_AttachmentHandler_Abstract
{
	protected $_contentIdKey = 'user_id';
	protected $_contentRoute = 'members';
	protected $_contentTypePhraseKey = 'user';

	protected function _canUploadAndManageAttachments(array $contentData, array $viewingUser)
	{
		return true;
	}

	protected function _canViewAttachment(array $attachment, array $viewingUser)
	{
		return true;
	}

	public function attachmentPostDelete(array $attachment, Zend_Db_Adapter_Abstract $db)
	{
		$userDw = XenForo_DataWriter::create('XenForo_DataWriter_User');
		$userDw->setExistingData($attachment['content_id']);
		$userDw->set('avatar_date', 0);
		$userDw->set('gravatar', '');
		$userDw->save();
	}

	protected function _getContentRoute()
	{
		return 'members';
	}

}
