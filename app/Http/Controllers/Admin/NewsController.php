<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index()
    {
        $items = News::orderByDesc('published_at')->paginate(10);
        return view('admin.news.index', compact('items'));
    }

    public function create()
    {
        return view('admin.news.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'image_url' => 'nullable|url',
            'image_file' => 'nullable|image|max:2048',
            'link_url' => 'nullable|url',
            'active' => 'sometimes|boolean',
            'published_at' => 'nullable|date',
        ]);
        $validated['active'] = (bool)($validated['active'] ?? true);
        $validated['published_at'] = $validated['published_at'] ?? now();
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $name = uniqid('news_').'.'.$file->getClientOriginalExtension();
            $target = public_path('news');
            if (!is_dir($target)) { @mkdir($target, 0775, true); }
            $file->move($target, $name);
            $validated['image_url'] = url('news/'.$name);
        }
        News::create($validated);
        return redirect()->route('admin.news.index')->with('success','Notícia criada.');
    }

    public function edit(News $news)
    {
        return view('admin.news.edit', compact('news'));
    }

    public function update(Request $request, News $news)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'image_url' => 'nullable|url',
            'image_file' => 'nullable|image|max:2048',
            'link_url' => 'nullable|url',
            'active' => 'sometimes|boolean',
            'published_at' => 'nullable|date',
        ]);
        $validated['active'] = (bool)($validated['active'] ?? $news->active);
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $name = uniqid('news_').'.'.$file->getClientOriginalExtension();
            $target = public_path('news');
            if (!is_dir($target)) { @mkdir($target, 0775, true); }
            $file->move($target, $name);
            $validated['image_url'] = url('news/'.$name);
        }
        $news->update($validated);
        return redirect()->route('admin.news.index')->with('success','Notícia atualizada.');
    }

    public function destroy(News $news)
    {
        $news->delete();
        return redirect()->route('admin.news.index')->with('success','Notícia excluída.');
    }
}


