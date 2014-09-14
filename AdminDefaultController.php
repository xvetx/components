<?php
namespace webvimark\components;


use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Cookie;
use yii\web\NotFoundHttpException;
use Yii;

class AdminDefaultController extends BaseController
{
	/**
	 * @var ActiveRecord
	 */
	public $modelClass;

	/**
	 * @var ActiveRecord
	 */
	public $modelSearchClass;

	/**
	 * If false then 'index', 'update', 'grid-sort', etc. will be disabled
	 *
	 * @var bool
	 */
	protected $enableBaseActions = true;

	public $enableCsrfValidation = true;

	/**
	 * Actions that will be disable on enableBaseActions = false;
	 *
	 * @var array
	 */
	protected $baseActions = ['index', 'update', 'create', 'view', 'delete', 'toggleAttribute', 'bulkActivate',
				  'bulkDeactivate', 'bulkDelete', 'gridSort', 'gridPageSize'];


	/**
	 * @var string
	 */
	public $layout = '//back';


	public function behaviors()
	{
		return [
			'verbs' => [
				'class' => VerbFilter::className(),
				'actions' => [
					'delete' => ['post'],
				],
			],
		];
	}

	/**
	 * Lists all models.
	 * @return mixed
	 */
	public function actionIndex()
	{
		$searchModel  = $this->modelSearchClass ? new $this->modelSearchClass : null;

		if ( $searchModel )
		{
			$dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());
		}
		else
		{
			$modelClass = $this->modelClass;
			$dataProvider = new ActiveDataProvider([
				'query' => $modelClass::find(),
			]);
		}

		return $this->render('index', compact('dataProvider', 'searchModel'));
	}

	/**
	 * Displays a single model.
	 *
	 * @param integer $id
	 *
	 * @return mixed
	 */
	public function actionView($id)
	{
		return $this->renderIsAjax('view', [
			'model' => $this->findModel($id),
		]);
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 * @return mixed
	 */
	public function actionCreate()
	{
		$model = new $this->modelClass;

		if ( $model->load(Yii::$app->request->post()) && $model->save() )
		{
			return $this->redirect($this->getRedirectPage('create', $model));
		}

		return $this->renderIsAjax('create', compact('model'));
	}

	/**
	 * Updates an existing model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 *
	 * @param integer $id
	 *
	 * @return mixed
	 */
	public function actionUpdate($id)
	{
		$model = $this->findModel($id);

		if ( $model->load(Yii::$app->request->post()) AND $model->save())
		{
			return $this->redirect($this->getRedirectPage('update', $model));
		}

		return $this->renderIsAjax('update', compact('model'));
	}

	/**
	 * Deletes an existing model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 *
	 * @param integer $id
	 *
	 * @return mixed
	 */
	public function actionDelete($id)
	{
		$model = $this->findModel($id);
		$model->delete();

		return $this->redirect($this->getRedirectPage('delete', $model));
	}
	/**
	 * @param string $attribute
	 * @param int $id
	 */
	public function actionToggleAttribute($attribute, $id)
	{
		$model = $this->findModel($id);
		$model->{$attribute} = ($model->{$attribute} == 1) ? 0 : 1;
		$model->save(false);
	}


	/**
	 * Activate all selected grid items
	 */
	public function actionBulkActivate()
	{
		if ( Yii::$app->request->post('selection') )
		{
			$modelClass = $this->modelClass;

			$modelClass::updateAll(
				['active'=>1],
				['id'=>Yii::$app->request->post('selection', [])]
			);
		}
	}


	/**
	 * Deactivate all selected grid items
	 */
	public function actionBulkDeactivate()
	{
		if ( Yii::$app->request->post('selection') )
		{
			$modelClass = $this->modelClass;

			$modelClass::updateAll(
				['active'=>0],
				['id'=>Yii::$app->request->post('selection', [])]
			);
		}
	}

	/**
	 * Deactivate all selected grid items
	 */
	public function actionBulkDelete()
	{
		if ( Yii::$app->request->post('selection') )
		{
			$modelClass = $this->modelClass;

			foreach (Yii::$app->request->post('selection', []) as $id)
			{
				$model = $modelClass::findOne($id);

				if ( $model )
					$model->delete();
			}
		}
	}


	/**
	 * Sorting items in grid
	 */
	public function actionGridSort()
	{
		if ( Yii::$app->request->post('sorter') )
		{
			$sortArray = Yii::$app->request->post('sorter',[]);

			$modelClass = $this->modelClass;

			$models = $modelClass::findAll(array_keys($sortArray));

			foreach ($models as $model)
			{
				$model->sorter = $sortArray[$model->id];
				$model->save(false);
			}

		}
	}


	/**
	 * Set page size for grid
	 */
	public function actionGridPageSize()
	{
		if ( Yii::$app->request->post('grid-page-size') )
		{
			$cookie = new Cookie([
				'name' => '_grid_page_size',
				'value' => Yii::$app->request->post('grid-page-size'),
				'expire' => time() + 86400 * 365, // 1 year
			]);

			Yii::$app->response->cookies->add($cookie);
		}
	}

	/**
	 * Render ajax or usual depends on request
	 *
	 * @param string $view
	 * @param array $params
	 *
	 * @return string|\yii\web\Response
	 */
	protected function renderIsAjax($view, $params)
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

	/**
	 * Finds the model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param mixed $id
	 *
	 * @return ActiveRecord the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id)
	{
		$modelClass = $this->modelClass;

		if ( ($model = $modelClass::findOne($id)) !== null )
		{
			return $model;
		}
		else
		{
			throw new NotFoundHttpException('The requested page does not exist.');
		}
	}


	/**
	 * Define redirect page after update, create, delete, etc
	 *
	 * @param string       $action
	 * @param ActiveRecord $model
	 *
	 * @return string|array
	 */
	protected function getRedirectPage($action, $model = null)
	{
		switch ($action)
		{
			case 'delete':
				return ['index'];
				break;
			case 'update':
				return ['view', 'id'=>$model->id];
				break;
			case 'create':
				return ['view', 'id'=>$model->id];
				break;
			default:
				return ['index'];
		}
	}

	/**
	 * @inheritdoc
	 */
	public function beforeAction($action)
	{
		if ( parent::beforeAction($action) )
		{
			if ( !$this->enableBaseActions AND in_array($action->id, $this->baseActions) )
			{
				throw new NotFoundHttpException('Page not found');
			}

			return true;
		}

		return false;

	}
}