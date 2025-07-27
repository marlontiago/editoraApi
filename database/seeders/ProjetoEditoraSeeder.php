<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Gestor;
use App\Models\Distribuidor;
use App\Models\Produto;
use App\Models\Venda;
use Spatie\Permission\Models\Role;

class ProjetoEditoraSeeder extends Seeder
{
    public function run(): void
    {
        
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $gestorRole = Role::firstOrCreate(['name' => 'gestor']);
        $distribuidorRole = Role::firstOrCreate(['name' => 'distribuidor']);
        
        $admin = User::create([
            'name' => 'Admin Geral',
            'email' => 'admin@admin.com',
            'password' => bcrypt('admin123'),
        ]);
        $admin->assignRole('admin');
        
        $gestorUser = User::create([
            'name' => 'Gestor Teste',
            'email' => 'gestor@teste.com',
            'password' => bcrypt('gestor123'),
        ]);
        $gestorUser->assignRole('gestor');

        $gestor = Gestor::create([
            'user_id' => $gestorUser->id,
            'nome_completo' => 'João Gestor',
            'telefone' => '41999999999'
        ]);
        
        $distrUser = User::create([
            'name' => 'Distribuidor Teste',
            'email' => 'distr@teste.com',
            'password' => bcrypt('distr123'),
        ]);
        $distrUser->assignRole('distribuidor');

        $distribuidor = Distribuidor::create([
            'user_id' => $distrUser->id,
            'gestor_id' => $gestor->id,
            'nome_completo' => 'Carlos Distri',
            'telefone' => '41988888888'
        ]);
        
        $produto = Produto::create([
            'nome' => 'Livro de Teste',
            'descricao' => 'Um livro qualquer de exemplo.',
            'preco' => 50,
            'quantidade_estoque' => 100,
        ]);
        
        Venda::create([
            'distribuidor_id' => $distribuidor->id,
            'produto_id' => $produto->id,
            'quantidade' => 2,
            'valor_total' => 100.00,
            'comissao' => 10.00
        ]);
        
    }
}
