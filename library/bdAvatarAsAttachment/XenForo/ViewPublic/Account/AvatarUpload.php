<?php

class bdAvatarAsAttachment_XenForo_ViewPublic_Account_AvatarUpload extends XFCP_bdAvatarAsAttachment_XenForo_ViewPublic_Account_AvatarUpload
{
	public function renderJson()
	{
		$json = parent::renderJson();

		if ($this->_params['avatar_date'] === 1)
		{
			$decoded = json_decode($json, true);

			foreach (array_keys($decoded['urls']) as $sizeCode)
			{
				$decoded['urls'][$sizeCode] = XenForo_Template_Helper_Core::callHelper('avatar', array(
					$this->_params['user'],
					$sizeCode
				));
			}

			foreach (array_keys($decoded['urls']) as $sizeCode)
			{
				$decoded['urls'][$sizeCode] = strval($decoded['urls'][$sizeCode]);
			}

			$json = json_encode($decoded);
		}

		return $json;
	}

}
