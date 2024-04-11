# HACKATHON API
API en php pour gerer les photos et l'authentification

## Fonctionnalité principales
- API RESTFul du portefolio

## Configuration requise
- docker
- git

## Instruction d'installation

### Via docker 

- cloner le dépot : `git clone https://github.com/Vivien-Parsis/hackathon`
- creer un fichier .env dans le répértoire avec comme contenu : `DB_URI={your_db_url}`
- pour le lancer l'api : `docker build -t hackathon . && docker run --rm -p 3000:80 --env-file .env --name hackathon hackathon`

## Adresse

`http://localhost:3000`

## Route

#### Réquete pour récuperer un user

GET `http://localhost:3000/user/get?mail={mail}`

```
Header HTTP
Authorization : Bearer {JWT_Here}
```
#### Réquete pour se connecter

POST `http://localhost:3000/user/signin`

```json
body HTTP 
{
    "mail":"{your_mail}",
    "password":"{your_password}"
}
```
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