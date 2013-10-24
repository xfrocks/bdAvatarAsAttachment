<?php

class bdAvatarAsAttachment_Helper_AvatarUrl
{
	protected static $_attachments = array();
	protected static $_attachmentModel = null;
	protected static $_delayedPrepare = false;
	protected static $_hashes = array();

	public static function getPath(array $user, $size)
	{
		$attachmentId = self::_getAttachmentId($user, $size);

		$attachment = self::_getAttachment($attachmentId);

		if (empty($attachment))
		{
			return false;
		}

		return self::_getAttachmentModel()->getAttachmentDataFilePath($attachment);
	}

	public static function getUrl($attachmentId, $size, $canonical)
	{
		$attachment = self::_getAttachment($attachmentId);

		if (empty($attachment))
		{
			$fakeUser = array('user_id' => 0);

			$url = XenForo_Template_Helper_Core::callHelper('avatar', array(
				$fakeUser,
				$size,
				'',
				$canonical,
			));
		}
		else
		{
			$type = 'attachments';
			if ($canonical)
			{
				$type = 'canonical:' . $type;
			}

			$url = XenForo_Link::buildPublicLink($type, $attachment);
		}

		return $url;
	}

	public static function prepare(array $user, $size, $canonical)
	{
		$attachmentId = self::_getAttachmentId($user, $size);

		if (empty($attachmentId))
		{
			return false;
		}

		if (!isset(self::$_attachments[$attachmentId]))
		{
			self::$_attachments[$attachmentId] = false;
		}

		if (self::$_delayedPrepare)
		{
			return self::_getHash($attachmentId, $size, $canonical);
		}
		else
		{
			return self::getUrl($attachmentId, $size, $canonical);
		}
	}

	public static function replaceHashes(&$html)
	{
		foreach (self::$_hashes as $hash => $hashInfo)
		{
			$url = self::getUrl($hashInfo[0], $hashInfo[1], $hashInfo[2]);

			$html = str_replace($hash, $url, $html);
		}
	}

	public static function setDelayedPrepare($enabled)
	{
		self::$_delayedPrepare = $enabled;
	}

	/**
	 * @return XenForo_Model_Attachment
	 */
	protected static function _getAttachmentModel()
	{
		if (self::$_attachmentModel === null)
		{
			self::$_attachmentModel = XenForo_Model::create('XenForo_Model_Attachment');
		}

		return self::$_attachmentModel;
	}

	protected static function _getAttachment($attachmentId)
	{
		if (!isset(self::$_attachments[$attachmentId]) OR self::$_attachments[$attachmentId] === false)
		{
			$ids = array($attachmentId);
			foreach (array_keys(self::$_attachments) as $id)
			{
				if (self::$_attachments[$id] === false)
				{
					$ids[] = $id;
					self::$_attachments[$id] = array();
				}
			}

			self::_getAttachmentModel();
			$attachments = XenForo_Application::getDb()->fetchAll('
				SELECT attachment.*,
				' . XenForo_Model_Attachment::$dataColumns . '
				FROM xf_attachment AS attachment
				INNER JOIN xf_attachment_data AS data ON
					(data.data_id = attachment.data_id)
				WHERE attachment_id IN (' . XenForo_Application::getDb()->quote($ids) . ')
			');

			foreach ($attachments as $attachment)
			{
				self::$_attachments[$attachment['attachment_id']] = $attachment;
			}
		}

		return self::$_attachments[$attachmentId];
	}

	protected static function _getAttachmentId(array $user, $size)
	{
		$attachmentId = 0;

		$sizesAndIds = explode(',', $user['gravatar']);
		while (count($sizesAndIds) > 0)
		{
			$_size = array_shift($sizesAndIds);
			$_id = array_shift($sizesAndIds);

			if ($_size === $size)
			{
				$attachmentId = $_id;
				break;
			}
		}

		return $attachmentId;
	}

	protected static function _getHash($attachmentId, $size, $canonical)
	{
		$hash = sprintf('<!-- %s / %s_%s_%d -->', __CLASS__, $attachmentId, $size, $canonical);

		if (empty(self::$_hashes[$hash]))
		{
			self::$_hashes[$hash] = array(
				$attachmentId,
				$size,
				$canonical
			);
		}

		return $hash;
	}

}
