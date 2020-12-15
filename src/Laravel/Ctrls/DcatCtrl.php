<?php namespace Bugcat\Gist\Laravel\Ctrls;

use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\{Form, Grid, Show, Admin};
use Bugcat\Gist\Laravel\Traits\CtrlTrait;

class DcatCtrl extends AdminController
{
    use CtrlTrait;

    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Example controller';

    protected $table = null; //数据库表名
    protected $model = null; //模型对象
    protected $user  = null; //当前用户对象


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //parent::__construct();
        if ( ! empty($this->table) ) {
            $this->model = $this->m($this->table);
        }
        $this->user = Admin::user();
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid($this->model);

        $grid->column('id', 'ID')->sortable();
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed   $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show($this->model->findOrFail($id));

        $show->field('id', 'ID');
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form($this->model);

        $form->display('id', 'ID');
        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));

        return $form;
    }

    //执行JS
    protected function js($script = '')
    {
        Admin::script($script);
    }

    //执行Css
    protected function css($style = '')
    {
        Admin::style($style);
    }

}
