<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;

#[AsCommand(name: 'app:migrate-from-srb', description: 'Migrate data from the old SRB database')]
class MigrateFromSrbCommand extends Command
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
            ->addOption('db-host',     null, InputOption::VALUE_OPTIONAL, 'Old DB host',     '127.0.0.1')
            ->addOption('db-name',     null, InputOption::VALUE_OPTIONAL, 'Old DB name',     'SRB')
            ->addOption('db-user',     null, InputOption::VALUE_OPTIONAL, 'Old DB user',     'root')
            ->addOption('db-password', null, InputOption::VALUE_REQUIRED, 'Old DB password')
            ->addOption('temp-password', null, InputOption::VALUE_OPTIONAL, 'Temporary password for migrated users', 'ChangeMe!')
            ->addOption('clear', null, InputOption::VALUE_NONE, 'Clear new DB tables before migrating');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $input->getOption('db-host'),
            $input->getOption('db-name'),
        );

        try {
            $old = new \PDO($dsn, $input->getOption('db-user'), $input->getOption('db-password'));
            $old->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            $io->error('Cannot connect to old database: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $new = $this->em->getConnection()->getNativeConnection();

        if ($input->getOption('clear')) {
            $io->warning('Clearing new database tables...');
            $new->exec('SET FOREIGN_KEY_CHECKS=0');
            $new->exec('TRUNCATE TABLE referencia_author');
            $new->exec('TRUNCATE TABLE referencia');
            $new->exec('TRUNCATE TABLE author');
            $new->exec('TRUNCATE TABLE `user`');
            $new->exec('SET FOREIGN_KEY_CHECKS=1');
        }

        $tempPassword = $input->getOption('temp-password');

        // ── 1. Users ──────────────────────────────────────────────────────────
        $io->section('Migrating users');

        $dummyUser = new User();
        $hashedTemp = $this->hasher->hashPassword($dummyUser, $tempPassword);

        $users = $old->query('SELECT * FROM fos_user')->fetchAll(\PDO::FETCH_ASSOC);
        $userStmt = $new->prepare(
            'INSERT IGNORE INTO `user` (id, username, email, display_name, password, roles) VALUES (?,?,?,?,?,?)'
        );

        $migratedUsers = 0;
        foreach ($users as $u) {
            $roles = $this->convertRoles($u['roles']);
            // Map ROLE_SUPER_ADMIN → ROLE_ADMIN
            $roles = str_replace('ROLE_SUPER_ADMIN', 'ROLE_ADMIN', $roles);
            $userStmt->execute([
                $u['id'],
                $u['username'],
                $u['email'],
                $u['username'],
                $hashedTemp,
                $roles,
            ]);
            $migratedUsers++;
        }
        $io->success("Migrated $migratedUsers users (temporary password: $tempPassword)");

        // ── 2. Authors ────────────────────────────────────────────────────────
        $io->section('Migrating authors');

        $authors = $old->query('SELECT * FROM Author')->fetchAll(\PDO::FETCH_ASSOC);
        $authorStmt = $new->prepare(
            'INSERT IGNORE INTO author (id, name, alias, user_id) VALUES (?,?,?,?)'
        );

        foreach ($authors as $a) {
            $authorStmt->execute([$a['id'], $a['name'], $a['alias'], $a['user_id']]);
        }
        $io->success('Migrated ' . count($authors) . ' authors');

        // ── 3. References ─────────────────────────────────────────────────────
        $io->section('Migrating references');

        $total = (int) $old->query('SELECT COUNT(*) FROM Referencia')->fetchColumn();
        $io->progressStart($total);

        $refStmt = $new->prepare('
            INSERT IGNORE INTO referencia
            (id, author, title, type, year_preprint, year_pub, publication, journal,
             issue, volume, pages, corporate_author, school, address, keywords, abst,
             publisher, place_pub, editor, issn, isbn, medium, advisor, thesis_type,
             notas, revision, file, url, doi, arxiv, mathscinet, zmath, inspires,
             report_number, msc, mr_number, book_title, created, modified, user_id)
            VALUES
            (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ');

        $batch = 200;
        $offset = 0;
        $migrated = 0;

        while (true) {
            $rows = $old->query("SELECT * FROM Referencia LIMIT $batch OFFSET $offset")->fetchAll(\PDO::FETCH_ASSOC);
            if (empty($rows)) break;

            $new->beginTransaction();
            foreach ($rows as $r) {
                $refStmt->execute([
                    $r['id'],
                    $r['author'],
                    $r['title'],
                    $r['type'],
                    $r['yearPreprint'],
                    $r['yearPub'],
                    $r['publication'],
                    $r['journal'],
                    $r['issue'],
                    $r['volume'],
                    $r['pages'],
                    $r['corporateAuthor'],
                    $r['school'] ?? null,
                    $r['address'],
                    $r['keywords'],
                    $r['abst'],
                    $r['publisher'],
                    $r['placePub'],
                    $r['editor'],
                    $r['issn'],
                    $r['isbn'],
                    $r['medium'],
                    $r['advisor'] ?? null,
                    $r['thesisType'] ?? null,
                    $r['notas'],
                    $r['revision'],
                    $r['file'],
                    $r['url'],
                    $r['doi'],
                    $r['arxiv'],
                    $r['mathscinet'],
                    $r['zmath'],
                    $r['inspires'],
                    $r['reportNumber'],
                    $r['msc'],
                    $r['mrNumber'],
                    $r['bookTitle'],
                    $r['created'],
                    $r['modified'],
                    $r['user_id'],
                ]);
                $migrated++;
                $io->progressAdvance();
            }
            $new->commit();

            $offset += $batch;
            if (count($rows) < $batch) break;
        }

        $io->progressFinish();
        $io->success("Migrated $migrated references");

        // ── 4. Reference–Author links ─────────────────────────────────────────
        $io->section('Migrating reference–author links');

        $links = $old->query('SELECT * FROM referencia_author')->fetchAll(\PDO::FETCH_ASSOC);
        $linkStmt = $new->prepare(
            'INSERT IGNORE INTO referencia_author (referencia_id, author_id) VALUES (?,?)'
        );

        $new->beginTransaction();
        foreach ($links as $l) {
            $linkStmt->execute([$l['referencia_id'], $l['author_id']]);
        }
        $new->commit();

        $io->success('Migrated ' . count($links) . ' reference–author links');

        $io->section('Summary');
        $io->table(['Table', 'Rows'], [
            ['user',               $migratedUsers],
            ['author',             count($authors)],
            ['referencia',         $migrated],
            ['referencia_author',  count($links)],
        ]);

        $io->note([
            'All users were imported with the temporary password: ' . $tempPassword,
            'Ask each user to log in and change their password.',
            'The admin account "admin" created earlier was preserved (use --clear to reset it).',
        ]);

        return Command::SUCCESS;
    }

    private function convertRoles(string $serialized): string
    {
        // Convert PHP serialized array to JSON
        // e.g. a:1:{i:0;s:16:"ROLE_SUPER_ADMIN";} → ["ROLE_SUPER_ADMIN"]
        $roles = @unserialize($serialized);
        if (!is_array($roles)) {
            $roles = [];
        }
        return json_encode(array_values($roles));
    }
}
