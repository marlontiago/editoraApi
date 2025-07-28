<select name="produtos[__INDEX__][id]" class="form-select w-full" required>
    <option value="">Selecione um produto</option>
    @foreach($produtos as $produto)
        <option value="{{ $produto->id }}">{{ $produto->nome }} - R$ {{ number_format($produto->preco, 2, ',', '.') }}</option>
    @endforeach
</select>
<input type="number" name="produtos[__INDEX__][quantidade]" class="form-input w-24" placeholder="Qtd" min="1" required>
