# Luminix Backend

Inicie rapidamente seu projeto Laravel com o Luminix Backend.

## Instalação

```bash
composer require luminix/backend
```

## Uso

```php
use Luminix\Backend\Model\LuminixModel;

class User extends Authenticatable
{

    use LuminixModel;

    // ...

}
```

Com apenas essas linhas de código, você terá um CRUD completo para a model User.

| URL | Rota | Método | Descrição |
| --- | ---- | ------ | --------- |
| /api/users | luminix.users.index | GET | Listar os usuários |
| /api/users | luminix.users.store | POST | Criar um novo usuário |
| /api/users/{id} | luminix.users.show | GET | Exibir um usuário |
| /api/users/{id} | luminix.users.update | PUT | Atualizar um usuário |
| /api/users/{id} | luminix.users.destroy | DELETE | Deletar um usuário |
| /api/users | luminix.users.destroyMany | DELETE | Deletar vários usuários |
| /api/users | luminix.users.restoreMany | PUT | Restaurar vários usuários |

## Documentação

Para mais detalhes sobre como configurar e personalizar a API, consulte a [documentação](https://luminix.arandutech.com.br/docs).

## Licença

[MIT](https://opensource.org/licenses/MIT).

