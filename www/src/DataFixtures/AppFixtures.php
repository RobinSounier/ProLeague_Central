<?php

namespace App\DataFixtures;

use App\Entity\Game;
use App\Entity\Media;
use App\Entity\Team;
use App\Entity\Tournament;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher) {}

    public function load(ObjectManager $manager): void
    {
        $this->loadUser($manager);
        $this->loadGame($manager);
        $this->loadMedia($manager);
        $this->loadTournaments($manager);
        $this->loadTeams($manager);
        $manager->flush();
    }


    public function loadUser(ObjectManager $manager): void
    {
        // Création de l'utilisateur administrateur avec droits d'administration
        $admin = new User();
        $admin->setEmail('admin@admin.com');
        $admin->setPseudo('Admin');
        // Le mot de passe 'admin' est haché pour sécuriser l'authentification
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin'));
        // Les rôles définissent les permissions: ROLE_ADMIN pour les droits admin, ROLE_USER pour les droits basiques
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $admin->setCreatedAt(new DateTime());
        $admin->setIsActive(true);
        $admin->setBio('Administrateur de CreativeHub');

        $manager->persist($admin);

        // Création de 5 utilisateurs standards pour tester l'application
        $arrayUser = [
            ['email' => 'user1@user.com', 'pseudo' => 'User1'],
            ['email' => 'user2@user.com', 'pseudo' => 'User2'],
            ['email' => 'user3@user.com', 'pseudo' => 'User3'],
            ['email' => 'user4@user.com', 'pseudo' => 'User4'],
            ['email' => 'user5@user.com', 'pseudo' => 'User5'],
        ];

        foreach ($arrayUser as $key => $value) {
            $user = new User();
            $user->setEmail($value['email']);
            $user->setPseudo($value['pseudo']);
            // Tous les utilisateurs standards ont le mot de passe 'user'
            $user->setPassword($this->passwordHasher->hashPassword($user, 'user'));
            // Ils ont uniquement le rôle ROLE_USER (pas d'accès administrateur)
            $user->setRoles(['ROLE_USER']);
            $user->setCreatedAt(new DateTime());
            $user->setIsActive(true);
            $user->setBio('Utilisateur de CreativeHub');

            $manager->persist($user);

            // Stocke la référence avec la clé 'user_0', 'user_1', etc. pour utilisation ultérieure
            $this->addReference('user_' . $key, $user);
        }
    }

    public function loadGame(ObjectManager $manager) : void
    {
        $arrayGame = [
            "League of Legends",        // Le roi des MOBA, indétrônable avec ses Worlds.
            "Counter-Strike 2",        // La référence absolue du FPS tactique.
            "Valorant",                // Le FPS tactique de Riot Games, très populaire chez les jeunes pros.
            "Dota 2",                  // Connu pour "The International" et ses cashprizes records.
            "Rocket League",           // Le mélange unique de foot et de voitures, très spectaculaire.
            "Fortnite",                // Le Battle Royale qui continue de se renouveler avec ses FNCS.
            "Apex Legends",            // Un Battle Royale nerveux basé sur des héros et des capacités.
            "Street Fighter 6",        // Pilier du Versus Fighting, star de l'EVO.
            "Rainbow Six Siege",       // FPS tactique basé sur la destruction et la stratégie d'équipe.
            "Mobile Legends: Bang Bang" // Le géant de l'esport mobile, dominant en Asie et en pleine croissance.
        ];

        foreach ($arrayGame as $key => $value) {
            $game = new Game();
            $game->setLabel($value);

            $manager->persist($game);

            $this->addReference('game_' . $key, $game);
            // Référence par nom pour faciliter la récupération
            $this->addReference('game_name_' . $value, $game);
        }
    }

    public function loadMedia(ObjectManager $manager): void
    {
        $games = [
            "League of Legends",
            "Counter-Strike 2",
            "Valorant",
            "Dota 2",
            "Rocket League",
            "Fortnite",
            "Apex Legends",
            "Street Fighter 6",
            "Rainbow Six Siege",
            "Mobile Legends: Bang Bang"
        ];

        foreach ($games as $gameName) {
            $media = new Media();
            $media->setPath('images/games/' . strtolower(str_replace([' ', ':', '-'], '_', $gameName)) . '.jpg');

            $manager->persist($media);

            $this->addReference('media_game_' . $gameName, $media);
        }
    }

    public function loadTournaments(ObjectManager $manager): void
    {
        $arrayTournament = [
            ['title' => 'Masters Valorant', 'game' => 'Valorant', 'link' => 'https://valorant.esports.com'],
            ['title' => 'LoL Open Cup', 'game' => 'League of Legends', 'link' => 'https://lolesports.com'],
            ['title' => 'Rocket League Dash', 'game' => 'Rocket League', 'link' => 'https://rocketleague.com'],
            ['title' => 'CS2 Global Pro', 'game' => 'Counter-Strike 2', 'link' => 'https://blast.tv'],
            ['title' => 'SF6: King of the Hill', 'game' => 'Street Fighter 6', 'link' => 'https://capcomprotour.com'],
            ['title' => 'Dota 2: Aegis Quest', 'game' => 'Dota 2', 'link' => 'https://dota2.com'],
            ['title' => 'Fortnite: Zero Build', 'game' => 'Fortnite', 'link' => 'https://fortnite.com/esports'],
            ['title' => 'Apex Legends Cup', 'game' => 'Apex Legends', 'link' => 'https://ea.com/apex-legends'],
            ['title' => 'R6: Opération Crystal', 'game' => 'Rainbow Six Siege', 'link' => 'https://r6esports.com'],
            ['title' => 'MLBB: Mobile Mayhem', 'game' => 'Mobile Legends: Bang Bang', 'link' => 'https://mobilelegends.com'],
        ];

        foreach ($arrayTournament as $i => $data) {
            $tournament = new Tournament();
            $tournament->setTitle($data['title']);
            $tournament->setDescription("Rejoignez le tournoi " . $data['title'] . ". Compétition acharnée, cashprize et gloire à la clé !");
            $tournament->setLink($data['link']);
            $tournament->setIsActive(true);

            // --- Gestion des dates ---
            $createdAt = new \DateTime();
            $createdAt->modify('-' . rand(10, 30) . ' days');
            $tournament->setCreatedAt($createdAt);

            // Date du tournoi (deadline) : entre 10 et 40 jours dans le futur
            $deadline = new \DateTime();
            $deadline->modify('+' . rand(10, 40) . ' days');
            $tournament->setDeadline($deadline);

            // Date limite d'inscription (deadlineJoin) : 2 jours AVANT le tournoi
            $deadlineJoin = clone $deadline;
            $deadlineJoin->modify('-2 days');
            $tournament->setDeadlineJoin($deadlineJoin);

            $tournament->setOwner($this->getReference('user_' . rand(0, 4), User::class));

            // --- Relations ---
            // On récupère le jeu via la référence par nom
            $tournament->setGame($this->getReference('game_name_' . $data['game'], Game::class));

            // Ajout d'un média (image) basé sur le jeu pour l'illustration
            $tournament->addMedia($this->getReference('media_game_' . $data['game'], Media::class));

            $manager->persist($tournament);

            // Référence pour lier des équipes (Team) ou des votes plus tard
            $this->addReference('tournament_' . $i, $tournament);
        }
    }

    public function loadTeams(ObjectManager $manager): void
    {
        $arrayTeam = [
            ['name' => 'Karmine Corp', 'game' => 'League of Legends'],
            ['name' => 'Team Vitality', 'game' => 'Counter-Strike 2'],
            ['name' => 'Gentle Mates', 'game' => 'Valorant'],
            ['name' => 'Solary', 'game' => 'Rocket League'],
            ['name' => 'G2 Esports', 'game' => 'Rainbow Six Siege'],
            ['name' => 'Fnatic', 'game' => 'Dota 2'],
            ['name' => 'T1', 'game' => 'League of Legends'],
            ['name' => 'FaZe Clan', 'game' => 'Counter-Strike 2'],
            ['name' => 'Sentinels', 'game' => 'Valorant'],
            ['name' => 'Team Liquid', 'game' => 'Apex Legends'],
        ];

        foreach ($arrayTeam as $i => $data) {
            $team = new Team();
            $team->setName($data['name']);
            $team->setDescription("Équipe officielle de " . $data['name'] . " concourant au plus haut niveau sur " . $data['game'] . ".");
            $team->setIsActive(true);
            $team->setCreatedAt(new \DateTime('-' . rand(30, 100) . ' days'));
            $team->setOwner($this->getReference('user_' . rand(0, 4), User::class));

            // 1. Assigne le Jeu via la référence par nom
            $gameReference = $this->getReference('game_name_' . $data['game'], Game::class);
            $team->setGame($gameReference);

            // 2. Ajoute des membres (Users) aléatoires (ex: 3 à 5 membres par équipe)
            // On pioche dans les utilisateurs créés (user_0 à user_4 selon ton code précédent)
            $usersPool = range(0, 4);
            shuffle($usersPool);
            $memberCount = rand(3, 5);

            for ($j = 0; $j < $memberCount; $j++) {
                if (isset($usersPool[$j])) {
                    $team->addUser($this->getReference('user_' . $usersPool[$j], User::class));
                }
            }

            // 3. Inscription aux tournois (ManyToMany)
            // On cherche les tournois qui concernent LE MÊME JEU que l'équipe
            // Pour l'exemple, on boucle sur les 10 références de tournois créées avant
            for ($t = 0; $t < 10; $t++) {
                /** @var Tournament $tournament */
                $tournament = $this->getReference('tournament_' . $t, Tournament::class);

                // Si le jeu du tournoi est le même que celui de l'équipe, on l'inscrit (50% de chance)
                if ($tournament->getGame() === $gameReference && rand(0, 1)) {
                    $team->addTournament($tournament);
                }
            }

            $manager->persist($team);

            // Stocke une référence si tu as besoin de lier les équipes à d'autres entités plus tard
            $this->addReference('team_' . $i, $team);
        }
    }


}
