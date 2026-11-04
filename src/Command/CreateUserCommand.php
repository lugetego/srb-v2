<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-user', description: 'Create a new user')]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED)
            ->addArgument('email', InputArgument::OPTIONAL, '', '')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Grant ROLE_ADMIN');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');
        $existing = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);

        if ($existing) {
            $user = $existing;
            $output->writeln('<comment>User exists, updating password' . ($input->getOption('admin') ? ' and roles' : '') . ': ' . $username . '</comment>');
        } else {
            $user = new User();
            $user->setUsername($username);
        }

        $helper   = $this->getHelper('question');
        $question = new Question('Password: ');
        $question->setHidden(true)->setHiddenFallback(false);
        $password = $helper->ask($input, $output, $question);

        if (!$password) {
            $output->writeln('<error>Password cannot be empty.</error>');
            return Command::FAILURE;
        }

        $user->setEmail($input->getArgument('email') ?: $user->getEmail());
        $user->setPassword($this->hasher->hashPassword($user, $password));

        if ($input->getOption('admin')) {
            $user->setRoles(['ROLE_ADMIN']);
        }

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln('<info>User ' . ($existing ? 'updated' : 'created') . ': ' . $user->getUsername() . '</info>');
        return Command::SUCCESS;
    }
}
