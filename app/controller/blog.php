<?php

namespace app\controller;

use common\BlogHelper;
use Webman\Http\Request;
use Webman\Http\Response;
use Support\Db;

class Blog
{

    /**
     * 获取博客列表
     *
     * @param Request $request
     * @return Response
     */
    public function list(Request $request): Response
    {
        $page   = $request->input('page', 1);    // 每页显示条数
        $limit  = $request->input('limit', 10);  // 偏移量
        $search = $request->input('search', ''); // 搜索关键字
        $tag    = $request->input('tag', '');    // 标签
        $tags   = explode(',', $tag);            // tags

        if (!is_numeric($page) || !is_numeric($limit)) {
            return api(false, 'invlid params');
        }
        if ($page < 1 || $limit < 1) {
            return api(false, 'invlid params');
        }
        if ($limit > 30) $limit = 30;

        // get count
        $countSql = Db::table('blogs');
        if ($search !== '') {
            $countSql = $countSql->where('name', 'like', '%' . $search . '%')
                ->orWhere('url', 'like', '%' . $search . '%');
        }
        $count = $countSql->count();

        // get data
        $sql = Db::table('blogs')->select('*')->forPage($page, $limit);
        if ($search !== '') {
            $sql = $sql->where('name', 'like', '%' . $search . '%')
                ->orWhere('url', 'like', '%' . $search . '%');
        }
        $data = $sql->get();

        // process
        foreach ($data as &$item) {
            $item->tags = BlogHelper::getTagByBlogId($item->idx);
            $item->enabled = $item->enabled === 1 ? true : false;
        }

        $pages = ceil($count / $limit);

        return api(data: [
            'pages' => $pages,
            'count' => $count,
            'data' => $data,
        ]);
    }

    /**
     * 获取随机博客
     *
     * @param Request $request
     * @return Response
     */
    public function random(Request $request): Response
    {
        $limit = $request->input('limit', 10);
        if ($limit > 20) $limit = 20;
        if (!is_numeric($limit) || $limit < 1) {
            return api(false, 'invlid params');
        }

        $data = Db::table('blogs')->select('*')->inRandomOrder()->take($limit)->get();

        foreach ($data as &$item) {
            $item->tags = BlogHelper::getTagByBlogId($item->idx);
            $item->enabled = $item->enabled === 1 ? true : false;
        }

        return api(data: $data);
    }

    /**
     * 获取tags
     *
     * @param Request $request
     * @return Response
     */
    public function tags(Request $request): Response
    {
        $page   = $request->input('page', 1);    // 每页显示条数
        $limit  = $request->input('limit', 100);  // 偏移量

        if (!is_numeric($page) || !is_numeric($limit)) {
            return api(false, 'invlid params');
        }
        if ($page < 1) {
            return api(false, 'invlid params');
        }
        $sql = Db::table('tag_map')->select('*');
        if ($limit > 0) {
            $sql = $sql->forPage($page, $limit);
        }
        $data = $sql->get();

        return api(data: $data);
    }
}

/*
SELECT * FROM `tags` AS `t`
LEFT JOIN `tags` AS `a`
ON `t`.`id` = `a`.`tag_id`
LEFT JOIN `blogs` AS `b`
ON `a`.`blog_id` = `b`.`id`
WHERE `t`.`id` = 79 or `t`.`id` = 80 group by t.`blog_id`;
 */
