<?php
/**
 * TechHub - 高校技术团队展示与管理平台
 *
 * 文件：app/Controllers/Admin/CategoryController.php
 * 模块：后台文章分类管理
 * 职责：分类增删改查、层级校验、启用状态和安全删除。
 * 作者：待团队填写
 * 创建日期：2026-11-25
 * 最后修改：2026-11-25
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Category;
use App\Support\Slugger;
use Core\Response;

final class CategoryController extends AdminController
{
    public function index(): Response
    {
        return $this->page('admin/categories/index', '文章分类', 'categories', [
            'categories' => (new Category())->adminAll(),
        ], [
            ['label' => '后台首页', 'url' => '/admin'],
            ['label' => '内容管理'],
            ['label' => '文章分类'],
        ]);
    }

    public function create(): Response
    {
        return $this->formPage(null);
    }

    public function store(): Response
    {
        [$data, $errors] = $this->validateInput(null);
        if ($errors !== []) {
            return $this->backWithErrors('/admin/categories/create', $errors, $data['old']);
        }

        $id = (new Category())->createCategory(
            $data['parent_id'],
            $data['name'],
            $data['slug'],
            $data['description'],
            $data['sort_order'],
            $data['status']
        );
        $this->logAction('categories', 'create', '创建文章分类：' . $data['name'], 'category', $id);
        $this->success('文章分类已创建。');

        return $this->redirect('/admin/categories');
    }

    public function edit(string $id): Response
    {
        $category = (new Category())->find((int) $id);
        if ($category === null) {
            abort(404, '分类不存在。');
        }

        return $this->formPage($category);
    }

    public function update(string $id): Response
    {
        $categoryId = (int) $id;
        $model = new Category();
        $category = $model->find($categoryId);
        if ($category === null) {
            abort(404, '分类不存在。');
        }

        [$data, $errors] = $this->validateInput($category);
        if ($errors !== []) {
            return $this->backWithErrors('/admin/categories/' . $categoryId . '/edit', $errors, $data['old']);
        }

        $model->updateCategory(
            $categoryId,
            $data['parent_id'],
            $data['name'],
            $data['slug'],
            $data['description'],
            $data['sort_order'],
            $data['status']
        );
        $this->logAction('categories', 'update', '更新文章分类：' . $data['name'], 'category', $categoryId);
        $this->success('分类已更新。');

        return $this->redirect('/admin/categories');
    }

    public function status(string $id): Response
    {
        $categoryId = (int) $id;
        $model = new Category();
        $category = $model->find($categoryId);
        if ($category === null) {
            abort(404, '分类不存在。');
        }

        $target = $category['status'] === 'enabled' ? 'disabled' : 'enabled';
        $model->setStatus($categoryId, $target);
        $this->logAction('categories', 'status', '分类状态改为 ' . $target . '：' . $category['name'], 'category', $categoryId);
        $this->success($target === 'enabled' ? '分类已启用。' : '分类已停用。');

        return $this->redirect('/admin/categories');
    }

    public function destroy(string $id): Response
    {
        $categoryId = (int) $id;
        $model = new Category();
        $category = $model->find($categoryId);
        if ($category === null) {
            abort(404, '分类不存在。');
        }

        if ($model->articleCount($categoryId) > 0) {
            $this->error('该分类下仍有文章，不能删除。');
            return $this->redirect('/admin/categories');
        }

        if ($model->childCount($categoryId) > 0) {
            $this->error('该分类仍有子分类，不能删除。');
            return $this->redirect('/admin/categories');
        }

        $model->deleteCategory($categoryId);
        $this->logAction('categories', 'delete', '删除文章分类：' . $category['name'], 'category', $categoryId);
        $this->success('分类已删除。');

        return $this->redirect('/admin/categories');
    }

    /**
     * @param array<string, mixed>|null $category
     */
    private function formPage(?array $category): Response
    {
        $editing = $category !== null;
        $all = (new Category())->adminAll();
        if ($editing) {
            $all = array_values(array_filter(
                $all,
                static fn (array $row): bool => (int) $row['id'] !== (int) $category['id']
            ));
        }

        return $this->page('admin/categories/form', $editing ? '编辑分类' : '新增分类', 'categories', [
            'category' => $category,
            'editing' => $editing,
            'parentOptions' => $all,
        ], [
            ['label' => '后台首页', 'url' => '/admin'],
            ['label' => '文章分类', 'url' => '/admin/categories'],
            ['label' => $editing ? '编辑分类' : '新增分类'],
        ]);
    }

    /**
     * @param array<string, mixed>|null $category
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    private function validateInput(?array $category): array
    {
        $name = $this->text('name', 80);
        $slug = Slugger::normalize($this->text('slug', 100));
        if ($slug === '') {
            $slug = Slugger::fromTitle($name, 'category');
        }
        $description = $this->nullableText('description', 255);
        $parentId = $this->integer('parent_id', 0);
        $parentId = $parentId > 0 ? $parentId : null;
        $sortOrder = $this->integer('sort_order', 0);
        $status = $this->text('status', 20);
        $errors = [];
        $model = new Category();

        if (mb_strlen($name) < 2) {
            $errors['name'] = '分类名称至少 2 个字符。';
        }
        if (!Slugger::isValid($slug)) {
            $errors['slug'] = '分类别名只能使用小写字母、数字和连字符。';
        } elseif ($model->slugExists($slug, $category === null ? null : (int) $category['id'])) {
            $errors['slug'] = '分类别名已被占用。';
        }
        if ($parentId !== null && $model->find($parentId) === null) {
            $errors['parent_id'] = '父分类不存在。';
        } elseif ($category !== null && $model->wouldCreateCycle((int) $category['id'], $parentId)) {
            $errors['parent_id'] = '不能把分类移动到自身或子分类下。';
        }
        if ($sortOrder < -999 || $sortOrder > 999) {
            $errors['sort_order'] = '排序值需在 -999 到 999 之间。';
        }
        if (!in_array($status, ['enabled', 'disabled'], true)) {
            $errors['status'] = '分类状态不合法。';
        }

        return [[
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'parent_id' => $parentId,
            'sort_order' => $sortOrder,
            'status' => $status,
            'old' => [
                'name' => $name,
                'slug' => $slug,
                'description' => $description ?? '',
                'parent_id' => $parentId ?? 0,
                'sort_order' => $sortOrder,
                'status' => $status,
            ],
        ], $errors];
    }
}
