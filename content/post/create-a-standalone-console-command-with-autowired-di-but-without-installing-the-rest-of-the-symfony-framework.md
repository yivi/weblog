+++
type="post"
date=2021-08-16T12:12:12
title="Create a standalone console command, with autowired DI, but without installing the rest of the Symfony framework"
tags=["symfony", "PHP", "console"]
+++

`symfony/skeleton` is not a particularly heavy set of dependency set, but it's nice to be able to create a command with only the minimal set of required components.

At the same time, if the command or commands provided are relatively complex, relying on autowiring for dependency injection instead of creating the config manually it's a nice perk.

We'll use the following `composer.json` to define the minimum requirements for the application:

```json
{
    "require": {
        "symfony/dependency-injection": "^5.3",
        "symfony/console": "^5.3",
        "symfony/config": "^5.3"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src"
        }
    }
}
```

The first thing to do is create our front-controller. The script that will take control whenever we execute we application, in lieu of the usual `bin/console`.

I'll name mine `app`, for the sake of simplicity:

```php
#!/usr/bin/env php
<?php declare(strict_types=1);

// ./app

use Symfony\Component;

// - 1
require __DIR__ . '/vendor/autoload.php';

// - 2
$container = new Component\DependencyInjection\ContainerBuilder();

// - 3
(new Component\DependencyInjection\Loader\PhpFileLoader($container, new Component\Config\FileLocator(__DIR__ . '/config')))
    ->load('services.php');

// - 4
$container->compile();

// - 5
($container->get(App\App::class))
    ->run();
```

It's pretty bare-bones:

1. Reads the autoloader
2. Instantiate a container builder
3. Use the Config component to read the configuration
4. Compile the container
5. Get the application from the container, and run it directly

Now we'll create `App\App`, that we execute on the last step:

```php
<?php declare(strict_types=1);
// src/App.php

namespace App;

use Symfony\Component\Console;

class App extends Console\Application
{

    public function __construct(iterable $commands)
    {
        $commands = $commands instanceof \Traversable ? \iterator_to_array($commands) : $commands;

        foreach ($commands as $command) {
            $this->add($command);
        }

        parent::__construct();
    }
}
```

This `App` class simply extends the basic Symfony Console application. On its constructor, it expects an `iterable` of commands that it will add to the application.

To define that `iterable`, we'll need to write the `services.php` configuration file:

```php
<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $configurator) {
    $services = $configurator->services()->defaults()
                             ->autowire(true);

    $services->instanceof(\Symfony\Component\Console\Command\Command::class)
             ->tag('app.command');

    $services->load('App\\', '../src/*');

    $services->set(\App\App::class)
             ->public()
             ->args([tagged_iterator('app.command')]);
};
```

Once on this step, you should be able to execute `php app` and you'd get a "working", although empty, console application:

![Application output showing the application is working, but without any available commands](/images/single-file-command-empty-output.png)

Now we'll need to create the meat and potatoes of this application:
- An utility service
- A command, so that our application has something to do.

The service will be basic and boring, for demonstration purposes only:

```php
<?php declare(strict_types=1);
// src/Text/Reverser.php
namespace App\Text;

class Reverser
{
    public function exec(string $in): string
    {
        return \strrev($in);
    }
}
```

And the actual command, that has this service as a dependency:

```php
<?php declare(strict_types=1);

namespace App\ConsoleCommand;

use App\Text\Reverser;
use Symfony\Component\Console;

class FooCommand extends Console\Command\Command
{

    protected static $defaultName = 'reverse';

    public function __construct(private Reverser $reverser)
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure()
    {
        $this->setDescription('Reverses a string');
        $this->addArgument('input', Console\Input\InputArgument::REQUIRED, 'A string that will be reversed');
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {

        $output->writeln($this->reverser->exec($input->getArgument('input')));

        return self::SUCCESS;
    }
}
```

Now when running `php app` our new command will be automatically be added to our application:

![Application output showing the new command made available](/images/single-file-command-output.png)

And on executing `php app reverse "the quick brown fox"` we can verify the command is working with dependencies correctly autowired:

![imagen.png](https://cdn.hashnode.com/res/hashnode/image/upload/v1629094953004/Qz38C0gR3.png)

You can clone the whole thing from Github  [here](https://github.com/yivi/standalone_symfony_console) .

(Note: I first posted this  [as an answer in SO](https://stackoverflow.com/questions/68754974/how-to-use-dependencyinjection-from-symfony-in-stand-alone-application-with-comm/68792899#68792899), but in that one I use YAML configuration because the asker preferred it that way).
