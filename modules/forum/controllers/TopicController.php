<?php

namespace app\modules\forum\controllers;


use Yii;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use app\components\Controller;
use app\modules\forum\models\Topic;
use app\modules\forum\models\Comment;
use app\modules\forum\models\CommentSearch;

/**
 * TopicController implements the CRUD actions for Topic model.
 */
class TopicController extends Controller
{
    public $defaultAction = 'view';

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
            'access' => [
                 'class' => AccessControl::className(),
                 'rules' => [
                     // 默认只能Get方式访问
                     [
                         'allow' => true,
                         'actions' => ['view'],
                         'verbs' => ['GET'],
                     ],
                     // 登录用户才能提交评论或其他内容
                     [
                         'allow' => true,
                         'actions' => ['view'],
                         'verbs' => ['POST'],
                         'roles' => ['@'],
                     ],
                     // 登录用户才能使用API操作(赞,踩,收藏)
                     [
                         'allow' => true,
                         'actions' => ['api', 'test'],
                         'roles' => ['@']
                     ],
                 ]
            ]
        ];
    }

//    /**
//     * Lists all Topic models.
//     * @return mixed
//     */
//    public function actionIndex()
//    {
//        $searchModel = new TopicSearch();
//        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
//
//        return $this->render('index', [
//            'searchModel' => $searchModel,
//            'dataProvider' => $dataProvider,
//        ]);
//    }

    /**
     * Displays a single Topic model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id, function($model){
            $model->with([
                'hate',
                'like',
                'favorite',
                'author',
                'author.avatar',
                'comments',
                'comments.hate',
                'comments.like',
                'comments.favorite',
                'comments.author',
                'comments.author.avatar'
            ]);
        });
        $comment = $this->newComment($model);
//        $commentSearchModel = new CommentSearch();
//        $commentDataProvider = $commentSearchModel->search(array_merge(Yii::$app->request->queryParams, [
//            $commentSearchModel->formName() => [
//                'tid' => $id
//            ]
//        ]));
//        $commentDataProvider->query->with('author', 'author.avatar');

        return $this->render('view', [
            'model' => $model,
//            'commentSearchModel' => $commentSearchModel,
//            'commentDataProvider' => $commentDataProvider,
            'comment' => $comment
        ]);
    }



//    /**
//     * Creates a new Topic model.
//     * If creation is successful, the browser will be redirected to the 'view' page.
//     * @return mixed
//     */
//    public function actionCreate()
//    {
//        $model = new Topic();
//
//        if ($model->load(Yii::$app->request->post()) && $model->save()) {
//            return $this->redirect(['view', 'id' => $model->id]);
//        } else {
//            return $this->render('create', [
//                'model' => $model,
//            ]);
//        }
//    }

//    /**
//     * Updates an existing Topic model.
//     * If update is successful, the browser will be redirected to the 'view' page.
//     * @param integer $id
//     * @return mixed
//     */
//    public function actionUpdate($id)
//    {
//        $model = $this->findModel($id);
//
//        if ($model->load(Yii::$app->request->post()) && $model->save()) {
//            return $this->redirect(['view', 'id' => $model->id]);
//        } else {
//            return $this->render('update', [
//                'model' => $model,
//            ]);
//        }
//    }
//
//    /**
//     * Deletes an existing Topic model.
//     * If deletion is successful, the browser will be redirected to the 'index' page.
//     * @param integer $id
//     * @return mixed
//     */
//    public function actionDelete($id)
//    {
//        $this->findModel($id)->delete();
//
//        return $this->redirect(['index']);
//    }

    /**
     * 收藏
     * @param $id
     */
    public function actionApi()
    {
        $request = Yii::$app->request;
        $model = $this->findModel($request->post('id'));
        $opeartions = ['favorite', 'like', 'hate'];
        if (!in_array($do = $request->post('do'), $opeartions)) {
            return $this->message('错误的操作', 'error');
        }
        $result = $model->{'toggle' . $do}(Yii::$app->user->getId());
        if ($result !== true) {
            return $this->message($result === false ? '操作失败' : $result, 'error');
        }
        return $this->message('操作成功', 'success');
    }

    public function actionTest($id)
    {
        $model = $this->findModel($id);
        $model->toggleLike(Yii::$app->user->getId());
        return $this->render('test', [
            'model' => $model
        ]);
    }

    /**
     * Finds the Topic model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Topic the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id, \Closure $func = null)
    {
        if ($id) {
            $model = Topic::find()->andWhere(['id' => $id])->active();
            $func !== null && $func($model);
            $model = $model->one();
            if ($model !== null) {
                return $model;
            }
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * 创建新评论
     * @param $topic
     * @return Comment
     */
    protected function newComment(Topic $topic)
    {
        $model = new Comment;
        if ($model->load(Yii::$app->request->post())) {
            $model->author_id = Yii::$app->user->id;
            if ($topic->addComment($model)) {
                $this->flash('发表评论成功!', 'success');
                Yii::$app->end(0, $this->refresh());
            }
        }
        return $model;
    }
}
