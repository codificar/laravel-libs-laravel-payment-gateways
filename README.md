# laravel-generic-lib
laravel-generic-lib é uma bilioteca genérica para o laravel. É um exemplo de uma Lib que possui rotas próprias, controllers, models, migrations, FormRequests, resources, arquivos de tradução, views (integração do blade do laravel com vue.js)

# Observações
- É importante sempre especificar qual middleware a biblioteca que você for desenvolver utiliza. Tais middleware deverão ser pré requisitos para os projetos que for instalar a sua lib. Exemplo: middleware para verificar se o admin fez login `'middleware' => 'auth.admin_api'`
- Utilizar preferencialmente os models criados na bibliotecas. Se utilizar models de um projeto especifíco, outro projeto pode não conter os mesmos models.
- Arquivos de traduções também devem ser feitos na biblioteca (evitar utilizar traduções de um projeto)
- Utilizar os seguintes prefixos nas rotas:
-- Rotas de api para apps: `localhost:8000/libs/nomedarota`
-- Rotas do painel : `localhost:8000/admin/libs/nomedarota`, e `localhost:8000/corp/libs/nomedarota` ...
- Preferencialmente, ao criar migrations, verificar se uma coluna, tabela ou row, já existe. Somente se não existir deverá ser criado.
- Deverá ser instalado o vue.js dentro da biblioteca
-  Gerar os arquivos minificados do vue dentro da própria biblioteca do laravel, e utilizar o `publishes` do laravel para colocar esses arquivos dentro da pasta public do projeto que for instalar essa lib. Depois é só adicionar o script no composer.json para quando rodar o comando `composer dump-autoload -o`, ele roda o publishes e copia esses arquivos minificados do vue da bilioteca e jogo dentro do projeto. É importante ficar atento ao tamanho do arquivo, evite utilizar modulos desnecessários no `package.json` e no fim, quando for da commit nas suas mudanças, rode `npm run prod` para gerar os arquivos minificados.
- Repare no arquivo GenericServiceProvider.php: 
```
$this->publishes([
    __DIR__.'/../public/js' => public_path('vendor/codificar/generic'),
], 'public_vuejs_libs'); 
```
- Aqui está sendo copiado os arquivos da pasta public/js da biblioteca e jogado para a pasta public/vendor/codificar/generic do projeto
- Abaixo, na parte de instalação, será mostrado como colocar o script no composer.json do projeto para fazer as mudanças sempre que rodar composer dump-autoload -o

# Rotas
| Tipo  | Retorno | Rota  | Description |
| :------------ |:---------------: |:---------------:| :-----|
| `get` | View/html | /admin/libs/example_vuejs | Api retorna um exemplo de uma página feita em vue.js |
| `get` | Api/json | /libs/generic/example | Api que os Apps poderão consumir | 
| `get` | Api/json | /libs/generic/lang.trans/{file} | Api retornará os arquivos de tradução do Laravel para serem usados dentro do vue.js |


# Estrutura
 ![alt text](https://i.imgur.com/PsahJHb.jpg)


# Instalação

- Adiciona o projeto no composer.json (direto do gitlab)

```

"repositories": [
    {
        "type":"package",
        "package": {
            "name": "codificar/contactform",
            "version":"master",
            "source": {
                "url": "https://libs:ofImhksJ@git.codificar.com.br/laravel-libs/laravel-generic-lib.git",
                "type": "git",
                "reference":"master"
            }
        }
    }
],

// ...

"require": {
    // ADD this
    "codificar/generic": "dev-master",
},

```

- Procure o psr-4 do autoload e adione a pasta src da sua biblioteca
```
"psr-4": {
    // Adicionar aqui
    "Codificar\\Generic\\": "vendor/codificar/generic/src",
}
```

- Agora, precisamos adicionar o novo Service Provider no arquivo `config/app.php` dentro do array `providers`:

```
'providers' => [
         ...,
            // The new package class
            Codificar\Generic\GenericServiceProvider::class,
        ],
```
- Precisamos copiar os arquivos da pasta public da biblioteca para a pasta public do projeto. Para isso adicione dentro do composer.json, no objeto `"scripts": {`. Repare que especificamos a tag. Nesse caso é public_vuejs_libs. Essa tag é a mesma que fica no arquivo GenericServiceProvider.php da biblioteca. Não tem problema várias bibliotecas utilizarem a mesma tag. Inclusive é bom que todos os componentes utilizem a mesma tag, para não ter que ficar adicionando isso a cada novo projeto que precisar da sua lib.
```
"post-autoload-dump": [
	"@php artisan vendor:publish --tag=public_vuejs_libs --force"
]
```

- Dump o composer autoloader

```
composer dump-autoload -o
```

- Rode as migrations

```
php artisan migrate
```

Por fim, teste se tudo está ok e acesse as rotas de exemplo:

```
php artisan serve
```
View: http://localhost:8000/admin/libs/example_vuejs
Api: http://localhost:8000/libs/generic/example