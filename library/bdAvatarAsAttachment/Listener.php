<?php

class bdAvatarAsAttachment_Listener
{
	const XENFORO_CONTROLLERPUBLIC_ACCOUNT_AVATAR_UPLOAD = 'bdAvatarAsAttachment_XenForo_ControllerPublic_Account::actionAvatarUpload';

	protected static $_helperCallbackAvatar;

	public static function load_class($class, array &$extend)
	{
		static $classes = array(
			'XenForo_ControllerPublic_Account',
			'XenForo_DataWriter_AttachmentData',
			'XenForo_DataWriter_User',
			'XenForo_Model_Avatar',
			'XenForo_ViewPublic_Account_AvatarUpload',
		);

		if (in_array($class, $classes))
		{
			$extend[] = 'bdAvatarAsAttachment_' . $class;
		}
	}

	public static function init_dependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
	{
		self::$_helperCallbackAvatar = XenForo_Template_Helper_Core::$helperCallbacks['avatar'];
		if (self::$_helperCallbackAvatar[0] === 'self')
		{
			self::$_helperCallbackAvatar[0] = 'XenForo_Template_Helper_Core';
		}

		XenForo_Template_Helper_Core::$helperCallbacks['avatar'] = array(
			__CLASS__,
			'helperAvatarUrl'
		);
		
		$contentTypes = XenForo_Application::get('contentTypes');
		$contentTypes['bdavatarasattachment_user'] = array(
			'attachment_handler_class' => 'bdAvatarAsAttachment_AttachmentHandler_User',
		);
		XenForo_Application::set('contentTypes', $contentTypes);
	}
	
	public static function front_controller_pre_view(XenForo_FrontController $fc, XenForo_ControllerResponse_Abstract &$controllerResponse, XenForo_ViewRenderer_Abstract &$viewRenderer, array &$containerParams)
	{
		if ($viewRenderer instanceof XenForo_ViewRenderer_HtmlPublic)
		{
			bdAvatarAsAttachment_Helper_AvatarUrl::setDelayedPrepare(true);
		}
	}
	
	public static function front_controller_post_view(XenForo_FrontController $fc, &$output)
	{
		bdAvatarAsAttachment_Helper_AvatarUrl::replaceHashes($output);
	}

	public static function file_health_check(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
	{
		$hashes += bdAvatarAsAttachment_FileSums::getHashes();
	}

	public static function helperAvatarUrl($user, $size, $forceType = null, $canonical = false)
	{
		if (!empty($user['avatar_date']) AND $user['avatar_date'] == 1 AND !empty($user['gravatar']))
		{
			if (empty($forceType) OR $forceType == 'custom' OR $forceType == 'true')
			{
				// TODO: $forceType == 'true' looks like a bug within template member_view
				$avatarUrl = bdAvatarAsAttachment_Helper_AvatarUrl::prepare($user, $size, $canonical);

				if (!empty($avatarUrl))
				{
					return $avatarUrl;
				}
				else
				{
					// unable to generate avatar url -> use default url instead
					// do not let built-in method handle this because `avatar_date` is incorrect
					$forceType = 'default';
				}
			}
		}

		return call_user_func(self::$_helperCallbackAvatar, $user, $size, $forceType, $canonical);
	}

}
