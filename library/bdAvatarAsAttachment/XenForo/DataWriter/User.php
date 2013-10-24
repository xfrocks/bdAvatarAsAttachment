<?php

class bdAvatarAsAttachment_XenForo_DataWriter_User extends XFCP_bdAvatarAsAttachment_XenForo_DataWriter_User
{
	protected $_bdAvatarAsAttachment_deletedAttachmentIds = array();

	protected function _bdAvatarAsAttachment_getExistingAttachmentIds()
	{
		$existingAttachmentIds = array();
		$existingSizesAndIds = explode(',', $this->getExisting('gravatar'));
		while (count($existingSizesAndIds))
		{
			$_size = array_shift($existingSizesAndIds);
			$_id = array_shift($existingSizesAndIds);

			if (!empty($_size) AND !empty($_id))
			{
				$existingAttachmentIds[$_size] = $_id;
			}
		}

		return $existingAttachmentIds;
	}

	protected function _preSave()
	{
		if ($this->get('avatar_date') == XenForo_Application::$time)
		{
			$attachmentIds = $this->getModelFromCache('XenForo_Model_Avatar')->bdAvatarAsAttachment_getAttachmentIds($this->get('user_id'));
			if ($attachmentIds !== false)
			{
				$existingAttachmentIds = $this->_bdAvatarAsAttachment_getExistingAttachmentIds();

				$sizesAndIds = array();
				foreach (array_merge($existingAttachmentIds, $attachmentIds) as $size => $id)
				{
					$sizesAndIds[] = $size;
					$sizesAndIds[] = $id;
				}

				$this->set('avatar_date', 1);
				$this->set('gravatar', implode(',', $sizesAndIds), '', array('runVerificationCallback' => false));

				foreach ($existingAttachmentIds as $_size => $_id)
				{
					if (isset($attachmentIds[$_size]) AND $attachmentIds[$_size] != $_id)
					{
						$this->_bdAvatarAsAttachment_deletedAttachmentIds[] = $_id;
					}
				}
			}
		}
		elseif ($this->get('avatar_date') === 1)
		{
			if ($this->get('gravatar') == '')
			{
				// our sizes and ids information has been reset...
				// set it back!
				$this->set('gravatar', $this->getExisting('gravatar'));
			}
		}
		elseif ($this->get('avatar_date') == 0)
		{
			if ($this->getExisting('avatar_date') == 1)
			{
				$existingAttachmentIds = $this->_bdAvatarAsAttachment_getExistingAttachmentIds();

				foreach ($existingAttachmentIds as $_size => $_id)
				{
					$this->_bdAvatarAsAttachment_deletedAttachmentIds[] = $_id;
				}
			}
		}

		return parent::_preSave();
	}

	protected function _postSaveAfterTransaction()
	{
		if (!empty($this->_bdAvatarAsAttachment_deletedAttachmentIds))
		{
			foreach ($this->_bdAvatarAsAttachment_deletedAttachmentIds as $attachmentId)
			{
				$dw = XenForo_DataWriter::create('XenForo_DataWriter_Attachment');
				$dw->setExistingData($attachmentId);
				$dw->delete();
			}

			$this->_bdAvatarAsAttachment_deletedAttachmentIds = array();
		}

		if (isset($GLOBALS[bdAvatarAsAttachment_Listener::XENFORO_CONTROLLERPUBLIC_ACCOUNT_AVATAR_UPLOAD]))
		{
			$GLOBALS[bdAvatarAsAttachment_Listener::XENFORO_CONTROLLERPUBLIC_ACCOUNT_AVATAR_UPLOAD]->bdAvatarAsAttachment_actionAvatarUpload($this);
		}

		return parent::_postSaveAfterTransaction();
	}

}
