<?php

class bdAvatarAsAttachment_Option
{
	public static function getUploadFileNamePrefix()
	{
		return md5(XenForo_Application::getConfig()->get('globalSalt'));
	}
}
