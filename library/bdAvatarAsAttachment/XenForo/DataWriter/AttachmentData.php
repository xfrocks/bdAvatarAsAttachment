<?php

class bdAvatarAsAttachment_XenForo_DataWriter_AttachmentData extends XFCP_bdAvatarAsAttachment_XenForo_DataWriter_AttachmentData
{
	protected function _preSave()
	{
		$prefix = bdAvatarAsAttachment_Option::getUploadFileNamePrefix();
		$fileName = $this->get('filename');
		if (strpos($fileName, $prefix) === 0)
		{
			$this->set('filename', substr($fileName, strlen($prefix)));
		}

		return parent::_preSave();
	}

}
