<?php

namespace app\api\controller;

use app\admin\model\Banner;
use app\common\controller\Api;
use app\common\model\Article;
use app\common\model\Follow;
use JPush\Client;
use think\Db;
use think\Exception;

/**
 * 首页接口
 */
class Index extends Api
{

    protected $noNeedLogin = ['index','findUser','test'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     * 
     */
    public function index()
    {
        $user = \app\admin\model\User::get('1');
        $this->success($user);
    }

    public function findUser()
    {
        Db::startTrans();
        try {
            $isSave = Db::table('User')->where('id','1')->setField('nickname','lilinGeger');
            if($isSave == 0){
                exception('更新用户信息失败,回滚!','10020');
            }
            Db::table('User')->where('id','1')->setField('username','linpang');
            Db::commit();
        } catch (Exception $e) {
            $this->error($e->getMessage());
            Db::rollback();
        }
        $this->success('修改信息成功!');
    }

    // 社区版块 热门页面
    public function communityHot()
    {
        // banner
        $data['banner'] = Banner::getBanner();
        // 我关注的版块
        $user = $this->auth->getUser();
        // 获取关注的版块分类id
        $category_id = $this->request->post('category_id');
        $data['myFollows'] = Follow::getUserFollows($user['id'],'2',$category_id);

        if(!is_array($data['myFollows'])){
            $this->error($data['myFollows'],'','40001');
        }
        // 获取page参数
        $page = $this->request->post('page/s');
        $data['hotArticle'] = model('Article')->getHotArticle(empty($data['myFollows'])?'': $data['myFollows']['0']['id'],$page);
        $this->success('请求数据成功',$data,'1');
    }

    // 社区 全部版块
    public function blockAll()
    {
        $data['banner'] = Banner::getBanner();
        // 已关注的版块
        $user = $this->auth->getUser();
        $data['myFollows'] = Follow::getUserFollows($user['id'],'2');
        // 剩余版块
        $data['blockArr'] = Follow::getUserNoFollows($user['id']);

        $this->success('请求数据成功',$data,'1');
    }

    // 板块详情
    public function getCategoryDetail()
    {
        $model = new Article();
        $user = $this->auth->getUser();
        // 获取版块id  页码
        $id = $this->request->post('category_id/s');
        $page = $this->request->post('page/s');
        // 推荐用户
        $data['recommend'] = $model->recommendUser($id,$user['id'],$page);
        // 文章
        $data['list'] = $model->getHotArticle($id,$page);

        $this->success('请求数据成功',$data);
    }

    // 获取文章详情
    public function getArticleDetail()
    {
        $user = $this->auth->getUser();
        $article_id = $this->request->post('article_id');
        if(empty($article_id)) {
            $this->error('参数不可为空 - article_id');
        }
        $detail = Article::getArticleDetail($article_id,$user['id']);

        $this->success('请求数据成功',$detail);
    }

    // 搜索文章
    public function search()
    {
        $param = [
            'keyWord'   => 'search/s',
            'page'      => 'page/s'
        ];
        $param = $this->buildParam($param);
        $model = new Article();
        $where['title'] = ['like','%'.$param['keyWord'].'%'];
        $list = $model->where($where)
                ->limit('10')
                ->page($param['page'])
                ->order('createtime','desc')
                ->select();
        $total = $model->where($where)->count();

        if(!empty($list)) {
            $list = $model->splicingUrl($list);
        }
        $data['list'] = $list;
        $data['total'] = $total;
        $this->success('请求成功',$data);
    }

    public function test()
    {
        $client = new Client('137258992','Assoofnoenandfnesdfdsf');
        $push = $client->push();
        $push->setCid('123123123')
            ->setPlatform('all')
            ->addAllAudience('all')
            ->setNotificationAlert('alert')
            ->iosNotification('hello')
            ->addWinPhoneNotification('hello')
            ->send();
        halt($push);
    }

    public function videoList()
    {

    }
}
