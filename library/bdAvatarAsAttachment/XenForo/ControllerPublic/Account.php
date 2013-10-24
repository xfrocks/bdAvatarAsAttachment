<?php

class bdAvatarAsAttachment_XenForo_ControllerPublic_Account extends XFCP_bdAvatarAsAttachment_XenForo_ControllerPublic_Account
{
	protected $_bdAvatarAsAttachment_visitorOverride = array();

	public function actionAvatar()
	{
		$response = parent::actionAvatar();

		if ($response instanceof XenForo_ControllerResponse_View AND $response->subView)
		{
			if ($response->subView instanceof XenForo_ControllerResponse_View)
			{
				$subView = &$response->subView;
				$visitor = XenForo_Visitor::getInstance();

				if ($visitor['avatar_date'] == 1)
				{
					$visitorGravatar = '';
					$subView->params['gravatarEmail'] = $visitor['email'];
				}
				else
				{
					$visitorGravatar = $visitor['gravatar'];
				}

				$subView->params['_bdAvatarAsAttachment_visitorGravatar'] = $visitorGravatar;
			}
		}

		return $response;
	}

	public function actionAvatarUpload()
	{
		$GLOBALS[bdAvatarAsAttachment_Listener::XENFORO_CONTROLLERPUBLIC_ACCOUNT_AVATAR_UPLOAD] = $this;

		$response = parent::actionAvatarUpload();

		if ($response instanceof XenForo_ControllerResponse_View)
		{
			foreach ($this->_bdAvatarAsAttachment_visitorOverride as $key => $value)
			{
				$response->params[$key] = $value;
				$response->params['user'][$key] = $value;
			}
		}

		return $response;
	}

	public function bdAvatarAsAttachment_actionAvatarUpload(XenForo_DataWriter_User $userDw)
	{
		$this->_bdAvatarAsAttachment_visitorOverride['avatar_date'] = $userDw->get('avatar_date');
		$this->_bdAvatarAsAttachment_visitorOverride['gravatar'] = $userDw->get('gravatar');
	}

}
