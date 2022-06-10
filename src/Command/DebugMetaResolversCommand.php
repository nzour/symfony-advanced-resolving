<?php

declare(strict_types=1);

namespace AdvancedResolving\Command;

use AdvancedResolving\Core\Internal\MetaResolverStorage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function explode;

#[AsCommand(
    name: DebugMetaResolversCommand::NAME,
    description: DebugMetaResolversCommand::DESCRIPTION,
)]
final class DebugMetaResolversCommand extends Command
{
    public const NAME = 'debug:meta-resolvers';
    public const DESCRIPTION = 'View list of defined meta resolvers';

    public function __construct(private MetaResolverStorage $storage)
    {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this->setDescription(self::DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $verbose = (bool) $input->getOption('verbose');

        $classNameConverter = $verbose
            ? static fn(string $it) => $it
            : [self::class, 'shortenerClassName'];

        if ([] === $this->storage->resolvers) {
            $output->writeln('There are no meta resolvers defined.');

            return self::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['Attribute', 'Resolver']);

        foreach ($this->storage->resolvers as $attribute => $resolver) {
            $table->addRow([
                $classNameConverter($attribute),
                $classNameConverter($resolver),
            ]);
        }

        $table->render();

        return self::SUCCESS;
    }

    private static function shortenerClassName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts) ?: $className;
    }
}
