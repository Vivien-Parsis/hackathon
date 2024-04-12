## Route

### User

#### Réquete pour récuperer ses infos

GET `http://localhost:3000/user/get`

```
Header HTTP
Authorization : Bearer {JWT_Here}
```

renvoie nos infos ou erreur

#### Réquete pour se connecter

POST `http://localhost:3000/user/signin`

```json
body HTTP 
{
    "mail":"{your_mail}",
    "password":"{your_password}"
}
```
renvoie un jwt de la forme {"jwt":"{created_token}"} ou erreur
#### Réquete pour s'inscrire

POST `http://localhost:3000/user/signup`

```json
body HTTP 
{
    "nom":"{your_name}",
    "mail":"{your_mail}",
    "password":"{your_password}"
}
```
renvoie un jwt de la forme {"jwt":"{created_token}"} ou erreur
### Réquete pour vérifier son jwt

POST `http://localhost:3000/user/jwt`

```
Header HTTP
Authorization : Bearer {JWT_Here}
```
renvoie notre jwt ou erreur
#### Réquete pour modifier un user

POST `http://localhost:3000/user/up`

```
Header HTTP
Authorization : Bearer {JWT_Here}
```
```json
body HTTP 
{
    "newNom":"{your_new_name}", ou "newMail":"{your_new_mail}"
    "mail":"{your_mail}",
    "password":"{your_password}"
}
```
renvoie notre nouveau jwt ou erreur
#### Réquete pour supprimer un user

POST `http://localhost:3000/user/up`

```
Header HTTP
Authorization : Bearer {JWT_Here}
```
```json
body HTTP 
{
    "mail":"{your_mail}",
    "password":"{your_password}"
}
```

### Image

#### Réquete pour récuper le logo

GET `http://localhost:3000/assets/img/icone/logocream.png`
