<?php
namespace app\webvimark\components;


use yii\db\ActiveRecord;
use Yii;

class BaseActiveRecord extends ActiveRecord
{
	public $thumbDirs = array();


	/**
	 * getUploadDir
	 *
	 * + Создаёт директории, если их нет
	 *
	 * @return string
	 */
	public function getUploadDir()
	{
		return Yii::getPathOfAlias('webroot.images.' . $this->tableName());
	}

	/**
	 * saveImage
	 *
	 * @param CUploadedFile $file
	 * @param string        $imageName
	 */
	public function saveImage($file, $imageName)
	{
		if ( ! $file )
			return;

		$uploadDir = $this->getUploadDir();

		$this->_prepareUploadDir($uploadDir);

		$ih = new CImageHandler;
		$ih->load($file->tempName)
			->thumb('500','500')
			->save($uploadDir.'/big/'.$imageName)
			->thumb('50','50')
			->save($uploadDir.'/for_grid/'.$imageName);
	}

	/**
	 * makeImageName
	 *
	 * @param string $imageName
	 * @return string
	 */
	public function makeImageName($imageName)
	{
		return uniqid() . $imageName;
	}

	/**
	 * deleteImages
	 *
	 * Delete images from all directories
	 *
	 * @param array $images
	 */
	public function deleteImages($images)
	{
		$uploadDir = $this->getUploadDir();

		if ( $this->thumbDirs === array() )
		{
			foreach ($images as $imageName)
				@unlink($uploadDir.'/'.$imageName);
		}
		else
		{
			foreach ($images as $imageName)
			{
				foreach ($this->thumbDirs as $thumbDir)
					@unlink($uploadDir.'/'.$thumbDir.'/'.$imageName);
			}
		}
	}

	/**
	 * getImageUrl
	 *
	 * @param string|null $dir
	 * @param string $attr
	 * @return string
	 */
	public function getImageUrl($dir = 'big', $attr = 'image')
	{
		if ( $dir )
			return Yii::app()->baseUrl."/images/{$this->tableName()}/{$dir}/".$this->{$attr};
		else
			return Yii::app()->baseUrl."/images/{$this->tableName()}/".$this->{$attr};
	}


	//=========== Rules ===========

	public function purgeXSS($attr)
	{
		$this->$attr = htmlspecialchars($this->$attr, ENT_QUOTES);
		return true;
	}

	//----------- Rules -----------



	//=========== Protected functions ===========

	/**
	 * _prepareUploadDir
	 *
	 * @param string $dir
	 */
	protected function _prepareUploadDir($dir)
	{
		if (! is_dir($dir))
		{
			mkdir($dir, 0777, true);
			chmod($dir, 0777);

			// Если есть нужны папки с thumbs
			if ( $this->thumbDirs !== array() )
			{
				foreach ($this->thumbDirs as $thumbDir)
				{
					if (! is_dir($dir.'/'.$thumbDir))
					{
						mkdir($dir.'/'.$thumbDir, 0777, true);
						chmod($dir.'/'.$thumbDir, 0777);
					}
				}
			}
		}
	}
} 