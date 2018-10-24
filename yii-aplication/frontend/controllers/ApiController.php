<?php
/**
 * Created by PhpStorm.
 * User: sanyochok
 * Date: 23.10.2018
 * Time: 17:32
 */

namespace frontend\controllers;


use common\models\User;
use frontend\models\SignupForm;
use yii\helpers\ArrayHelper;
use yii\web\Controller;

class ApiController extends Controller
{
    private $_user;
    private $_signupForm;

    public function beforeAction($action)
    {
        $action_list = [
            'update-user',
            'create-user',
            'delete-user'
        ];
        if (in_array($action->id, $action_list)) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    public function __construct($id, $module, User $user, SignupForm $signupForm, array $config = [])
    {
        $this->_user = $user;
        $this->_signupForm = $signupForm;
        parent::__construct($id, $module, $config);
    }

    public function init()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    }


    public function actionGetUsers()
    {
        return $this->_user->getUsers();
    }

    public function actionSearch()
    {
        return $this->_user->searchUser(\Yii::$app->request->get('search', ''));
    }

    public function actionUpdateUser()
    {
        if ($this->_user->updateUser(\Yii::$app->request->get('id'))){
            \Yii::$app->getResponse()->setStatusCode(204);
            return [];
        };

    }

    public function actionCreateUser()
    {
        if ($this->_signupForm->signup()){
            \Yii::$app->getResponse()->setStatusCode(201);
            return [];
        };
    }

    public function actionDeleteUser()
    {
        if ($this->_user->deleteUser(\Yii::$app->request->get('id'))){
            \Yii::$app->getResponse()->setStatusCode(204);
            return [];
        }
    }

}