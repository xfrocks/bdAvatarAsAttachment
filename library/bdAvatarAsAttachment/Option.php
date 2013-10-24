<?php

class bdAvatarAsAttachment_Option
{
	public static function getUploadFileName()
	{
		return md5('avatar_' . XenForo_Application::getConfig()->get('globalSalt'));
	}
}
