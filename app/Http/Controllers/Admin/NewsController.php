<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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
        
        // Filtrar apenas campos que existem na tabela
        $columns = Schema::getColumnListing('news');
        $validated = array_intersect_key($validated, array_flip($columns));
        
        // Se a coluna 'content' existir e for obrigatória, preencher com o valor de 'body' ou string vazia
        if (Schema::hasColumn('news', 'content') && !isset($validated['content'])) {
            $validated['content'] = $request->input('body', '');
        }
        
        // Se a coluna 'slug' existir e for obrigatória, gerar automaticamente baseado no título
        if (Schema::hasColumn('news', 'slug') && !isset($validated['slug'])) {
            $baseSlug = Str::slug($validated['title']);
            $slug = $baseSlug;
            $counter = 1;
            // Garantir que o slug seja único
            while (News::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $slug;
        }
        
        // Preencher outros campos obrigatórios que podem estar faltando
        $existingColumns = Schema::getColumnListing('news');
        foreach ($existingColumns as $column) {
            // Ignorar colunas que já foram preenchidas ou são auto-increment/timestamps
            if (in_array($column, ['id', 'created_at', 'updated_at']) || isset($validated[$column])) {
                continue;
            }
            
            // Verificar se a coluna é obrigatória (NOT NULL sem default)
            $columnInfo = \DB::select("SHOW COLUMNS FROM `news` WHERE Field = ?", [$column]);
            if (!empty($columnInfo)) {
                $info = $columnInfo[0];
                $isNullable = $info->Null === 'YES';
                $hasDefault = $info->Default !== null || $info->Extra === 'auto_increment';
                
                // Se for obrigatório e não tem default, preencher com valor padrão baseado no tipo
                if (!$isNullable && !$hasDefault) {
                    $fieldType = strtolower($info->Type);
                    if (strpos($fieldType, 'varchar') !== false || strpos($fieldType, 'text') !== false || strpos($fieldType, 'char') !== false) {
                        $validated[$column] = '';
                    } elseif (strpos($fieldType, 'int') !== false) {
                        $validated[$column] = 0;
                    } elseif (strpos($fieldType, 'decimal') !== false || strpos($fieldType, 'float') !== false || strpos($fieldType, 'double') !== false) {
                        $validated[$column] = 0.0;
                    } elseif (strpos($fieldType, 'bool') !== false || strpos($fieldType, 'tinyint(1)') !== false) {
                        $validated[$column] = false;
                    }
                }
            }
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
        // Validar título obrigatório
        $request->validate([
            'title' => 'required|string|max:255',
        ]);
        
        // Verificar quais colunas existem na tabela
        $columns = Schema::getColumnListing('news');
        
        // Inicializar array de dados para atualização
        $dataToUpdate = [];
        
        // Adicionar campos básicos que sempre existem
        if (in_array('title', $columns)) {
            $dataToUpdate['title'] = $request->input('title');
        }
        if (in_array('active', $columns)) {
            $dataToUpdate['active'] = (bool)($request->has('active') ? $request->input('active') : $news->active);
        }
        if (in_array('published_at', $columns)) {
            $publishedAt = $request->input('published_at');
            $dataToUpdate['published_at'] = $publishedAt ? \Carbon\Carbon::parse($publishedAt) : null;
        }
        
        // Processar upload de imagem
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $name = uniqid('news_').'.'.$file->getClientOriginalExtension();
            $target = public_path('news');
            if (!is_dir($target)) { @mkdir($target, 0775, true); }
            $file->move($target, $name);
            if (in_array('image_url', $columns)) {
                $dataToUpdate['image_url'] = url('news/'.$name);
            }
        } else {
            // Sempre processar image_url do formulário (mesmo se vazio)
            if (in_array('image_url', $columns)) {
                $imageUrl = trim($request->input('image_url', ''));
                // Validar URL apenas se não estiver vazia
                if (!empty($imageUrl) && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    return back()->withErrors(['image_url' => 'URL de imagem inválida.'])->withInput();
                }
                $dataToUpdate['image_url'] = $imageUrl ?: null;
            }
        }
        
        // Adicionar body/content conforme existência - sempre incluir mesmo se vazio
        if (in_array('body', $columns)) {
            $body = $request->input('body');
            $dataToUpdate['body'] = $body !== null ? $body : '';
        }
        if (in_array('content', $columns)) {
            $bodyValue = $request->input('body');
            $dataToUpdate['content'] = $bodyValue !== null ? $bodyValue : ($news->content ?? '');
        }
        
        // Adicionar link_url se a coluna existir - sempre incluir mesmo se vazio
        if (in_array('link_url', $columns)) {
            $linkUrl = trim($request->input('link_url', ''));
            // Validar URL apenas se não estiver vazia
            if (!empty($linkUrl) && !filter_var($linkUrl, FILTER_VALIDATE_URL)) {
                return back()->withErrors(['link_url' => 'URL do link inválida.'])->withInput();
            }
            $dataToUpdate['link_url'] = $linkUrl ?: null;
        }
        
        // Atualizar slug se o título mudou
        if (in_array('slug', $columns) && $request->input('title') !== $news->title) {
            $baseSlug = Str::slug($request->input('title'));
            $slug = $baseSlug;
            $counter = 1;
            while (News::where('slug', $slug)->where('id', '!=', $news->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            $dataToUpdate['slug'] = $slug;
        }
        
        // Filtrar apenas campos que existem na tabela
        $dataToUpdate = array_intersect_key($dataToUpdate, array_flip($columns));
        
        // Usar fill() + save() ao invés de update() para garantir que todos os campos sejam atualizados
        $news->fill($dataToUpdate);
        $news->save();
        
        return redirect()->route('admin.news.index')->with('success','Notícia atualizada.');
    }

    public function destroy(News $news)
    {
        $news->delete();
        return redirect()->route('admin.news.index')->with('success','Notícia excluída.');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'news_ids' => 'required|array',
            'news_ids.*' => 'exists:news,id',
        ]);

        $count = News::whereIn('id', $request->news_ids)->delete();
        
        return redirect()->route('admin.news.index')->with('success', "{$count} notícia(s) excluída(s) com sucesso.");
    }
}


