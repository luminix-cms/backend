# Luminix Backend

Luminix Backend é um pacote poderoso para Laravel que gera automaticamente endpoints RESTful API para seus modelos, com segurança integrada, filtragem e gerenciamento de permissões.

## Funcionalidades

- 🚀 Geração Automática de Endpoints API
- 🔒 Controles de Segurança Robustos
- 🔍 Filtragem Avançada em APIs
- 🛡️ Integração com Gates do Laravel
- 🔧 Altamente Configurável

## Instalação

Instale o pacote via Composer:

```bash
composer require luminix/backend
```

## Começo Rápido

1. Adicione a trait `Luminix\Backend\Model\LuminixModel` aos seus modelos:

```php
use Illuminate\Database\Eloquent\Model;
use Luminix\Backend\Model\LuminixModel;

class User extends Model
{
    use LuminixModel;
    
    // Seu código do modelo aqui
}
```

2. Publique o arquivo de configuração:

```bash
php artisan vendor:publish --tag=luminix-config
```

3. Configure o pacote em `config/luminix/backend.php`

## Documentação

Acesse a [documentação](docs/getting-started/installation.md) para instruções detalhadas de uso do Luminix Backend.

## Contribuindo

Contribuições são bem-vindas! Envie pull requests ou abra issues em nosso repositório no GitHub.

## Licença

[MIT](https://opensource.org/licenses/MIT).

## Suporte

Para suporte, abra uma issue no repositório do GitHub.