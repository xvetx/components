<?php

namespace webvimark\components;
use webvimark\modules\UserManagement\components\AccessController;
use Yii;

class BaseController extends AccessController
{
	/**
	 * Render ajax or usual depends on request
	 *
	 * @param string $view
	 * @param array $params
	 *
	 * @return string|\yii\web\Response
	 */
	protected function renderIsAjax($view, $params = [])
	{
		if ( Yii::$app->request->isAjax )
		{
			return $this->renderAjax($view, $params);
		}
		else
		{
			return $this->render($view, $params);
		}
	}
}