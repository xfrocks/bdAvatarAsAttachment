<?php

class bdAvatarAsAttachment_XenForo_DataWriter_AttachmentData extends XFCP_bdAvatarAsAttachment_XenForo_DataWriter_AttachmentData
{
	protected function _preSave()
	{
		if ($this->get('filename') == bdAvatarAsAttachment_Option::getUploadFileName())
		{
			$this->set('filename', 'avatar.jpg');
		}

		return parent::_preSave();
	}

}
