<?php

namespace App\Services;

use App\Models\Produto;
use Illuminate\Support\Facades\Storage;
use Svg\Gradient\Stop;

class ProdutoService
{
    public function criar(array $data): Produto
    {
        if(isset($data['imagem']) && $data['imagem'] && $data['imagem']->isValid())
        {
            $data['imagem'] = $data['imagem']->store('produtos', 'public');
        }

        return Produto::create($data);
    }

    public function atualizar(Produto $produto, array $data): Produto
    {
        if(isset($data['imagem']) && $data['imagem']->isValid())
        {
            if($produto->imagem && Storage::disk('public')->exists($produto->imagem))
            {
                Storage::disk('public')->delete('produtos', 'public');
            }

            $data['imagem'] = $data['imagem']->store('produtos', 'public');
        } else {
            unset($data['imagem']);
        }

        $produto->update($data);
        return $produto;
    }

    public function deletar(Produto $produto): void
    {
        if($produto->imagem && Storage::disk('public')->exists($produto->imagem))
        {
            Storage::disk('public')->delete($produto->imagem);
        }

        $produto->delete();
    }
}