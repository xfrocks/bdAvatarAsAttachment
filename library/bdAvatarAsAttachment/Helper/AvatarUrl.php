<?php

class bdAvatarAsAttachment_Helper_AvatarUrl
{
	protected static $_attachments = array();
	protected static $_attachmentModel = null;

	protected $_attachmentId;
	protected $_size;
	protected $_canonical;

	protected function __construct($attachmentId, $size, $canonical)
	{
		$this->_attachmentId = $attachmentId;
		$this->_size = $size;
		$this->_canonical = $canonical;
	}
	
	public function getPath()
	{
		$attachment = self::_getAttachment($this->_attachmentId);
		
		if (empty($attachment))
		{
			return false;
		}
		
		return self::_getAttachmentModel()->getAttachmentDataFilePath($attachment);
	}

	public function __toString()
	{
		$attachment = self::_getAttachment($this->_attachmentId);

		if (empty($attachment))
		{
			$fakeUser = array('user_id' => 0);

			return XenForo_Template_Helper_Core::callHelper('avatar', array(
				$fakeUser,
				$this->_size
			));
		}

		$type = 'attachments';
		if ($this->_canonical)
		{
			$type = 'canonical:' . $type;
		}
		
		return XenForo_Link::buildPublicLink($type, $attachment);
	}

	public static function prepare(array $user, $size, $canonical)
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

		if (empty($attachmentId))
		{
			return false;
		}

		if (!isset(self::$_attachments[$attachmentId]))
		{
			self::$_attachments[$attachmentId] = false;
		}

		return new self($attachmentId, $size, $canonical);
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
		if (self::$_attachments[$attachmentId] === false)
		{
			$ids = array();
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

}
