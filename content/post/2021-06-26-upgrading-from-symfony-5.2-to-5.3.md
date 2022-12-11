+++
title="Upgrading from Symfony 5.2 to 5.3"
tags=["symfony", "upgrade", "PHP"]
date=2021-06-26T12:12:12
type="post"
slug="upgrading-from-symfony-5.2-to-5.3"
aliases = [
"/upgrading-from-symfony-5.2-to-5.3/"
]
+++

Upgrading to 5.3, and I suddenly get a few deprecations. Woe is me.
Let's get all of them resolved.

(These are only field-notes, the exact deprecations and problems you'll get will vary on what exactly you have installed and are using. This is what happened to me on this specific project).

(Also note, Deprecations are not Errors! There is nothing wrong in living with some deprecated code. At the same time, dealing with those makes your life easier when you need/want to upgrade).

Most, if not all, the solvable deprecations are related to Symfony moving towards the new authentication system. Most of the deprecation messages related to `symfony/security-*` packages (e.g. `symfony/security-core`, `symfony/security-bundle`, `symfony/security-guard`, etc) are simply resolved by enabling the new authentication system on your security settings:

```yaml
security:
    enable_authenticator_manager: true
```

Once we enable this, we need to take care of a few more things related to the new authentication system:

### `UserInterface` changes

On Symfony 6.0, `UserInterface` will implement a `getUserIdentifier()` method. More often than not, it's simply a replacement for `getUsername()`, which is deprecated and should disappear on 6 as well.

So simply implement the method according to your domain logic:

```php
public function getUserIdentifier(): string {
    return $this->username;
}
```

On top of that, if your "user" class had credentials information, it should implement `PasswordAuthenticatedUserInterface` on addition to `UserInterface`. This interface just includes the `getPassword()` method:

```php
interface PasswordAuthenticatedUserInterface
{
    public function getPassword(): ?string;
}
```

### Hashing !== Encoding

If you had any services that concerned themselves with password hasing, they would generally have had `Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface` injected for that. The "encoding" is a misnomer, and which creates confusion for some users new to the concept. For this version the service is deprecated in favor of the equivalent `UserPasswordHasherInterface`.

Note that instead of using `encodePassword()`, you'll have to use `hashPassword()`, naturally:

```php
use Symfony\Component\Security\Core\Encoder\UserPasswordHasherInterface

final class FooPassword
{
    public function __construct(private UserPasswordHasherInterface $hasher, private UserRepository $userRepository) {}

    public function foo(string $password, string $username) {
      
      $user = $this->userRepository->getUserByIdentifier($username)
      
      $user->setPassword($this->hasher->hashPassword($user, $password));
    } 
}
```

Correspondingly, in your security configuration, the reference to "encoders" need to change to "hashers".

Previously:

```yaml
security:
   password_encoders:
        App\Infrastructure\SecurityUser:
            algorithm: auto
            cost: 12
```

Now:

```yaml
security:
   password_hashers:
        App\Infrastructure\SecurityUser:
            algorithm: auto
            cost: 12
```

### Guards are gone

If you had implemented any custom `GuardAuthenticator`for your project, you'll need to `AuthenticatorIntefrace`. The transition is usually easy enough, since the most interesting changes luckily happen deeper down Symfony stack.

## Session changes

> The "session.storage.native" service is deprecated

This one is slightly weird. If you **have not** set a value for `framework.session.storage_factory_id`, in theory the default is `session.storage.factory.native` ( [docs](https://symfony.com/doc/current/reference/configuration/framework.html#storage-factory-id) ). But if you leave this value empty, it's made apparent this is not true.

So just simply set it explicitly to something like:

```yaml
framework:
    session:
        handler_id: null
        cookie_secure: auto
        cookie_samesite: lax
        storage_factory_id: session.storage.factory.native### Dependencies
```

## Dependencies

The other deprecations I had on my system were caused by other dependencies not being yet upgraded. Even Symfony dependencies. Some were updated while I was on the process of performing my own upgrade (e.g. `lexik/jwt-authentication-bundle` , which added support for the new authenticator system [here](https://github.com/lexik/LexikJWTAuthenticationBundle/pull/872) . By keeping an eye on those dependencies, you'll usually be fine by the time 6.0 come around. 
