<?php

namespace Mvc\Controller;
use Config\Controller;
use Mvc\Model\TournamentModel;
use Mvc\Model\UserModel;
use Twig\Environment;


class TournamentController extends Controller
{
    private TournamentModel $tournamentModel;
    private UserModel $userModel;

    public function __construct() {

        parent::__construct();
        $this->tournamentModel = new TournamentModel();
        $this->userModel = new UserModel();
    }

    public function createTournament() {

        if (isset($_POST) && isset($_POST['name']) && isset($_POST['admissionPrice']) && $_SESSION["user"]['token'] >= 100) {

            $_SESSION["user"]["token"] =  $_SESSION["user"]["token"] - 100; 
            $this->tournamentModel->createTournament($_POST['name'], $_POST['admissionPrice'], $_SESSION['user']['pseudo']);
            $this->userModel->spendToken($_SESSION["user"]["token"], $_SESSION["user"]["firstnames"]);
            header('location: /listTournament');
            exit();
        }

        echo $this->twig->render('tournament/createTournament.html.twig');
    }


    public function listTournament() {
        $tournaments = $this->tournamentModel->findAllTournaments();

        echo $this->twig->render('tournament/listTournament.html.twig', [
            'tournaments' => $tournaments,
        ]);
    }

    public function showDetails(int $id) {

        $tournament = $this->tournamentModel->findOneTournament($id);

        $matches = $this->tournamentModel->getMaches($id);

        $tournamentParticipants = [];

        foreach ($tournament as $detail => $values) {
            foreach ($values as $key => $value) {
                if ($key == 'pseudo') {
                    $tournamentParticipants[] = $value;
                }
            }
        };

        if (count($tournamentParticipants) > 0) {

            $tournamentDetails = ['name' => $tournament[0]['name'],
                'host' => $tournament[0]['host'],
                'admissionPrice' => $tournament[0]['admissionPrice'],
                'tournamentParticipants' => $tournamentParticipants
            ];
        } else {

            $tournamentDetails = ['name' => $tournament[0]['name'],
                'host' => $tournament[0]['host'],
                'admissionPrice' => $tournament[0]['admissionPrice']
            ];
        }

        echo $this->twig->render('tournament/showDetails.html.twig', [
            'tournamentDetails' => $tournamentDetails,
        ]);
    }


    public function deleteTournament() {

        if (isset($_POST)) {

            $this->tournamentModel->deleteTournament(key($_POST));     
        }

        header('location: /listTournament');
        exit();
    }

    public function joinTournament() {

        if (isset($_POST)) {

            $participants = $this->tournamentModel->getParticipants(key($_POST));

            $isPlayerInTournament = false;

            $tournamentPrice = ($this->tournamentModel->findOneTournament(key($_POST)))[0]['admissionPrice'];

            $hasEnoughToken = false;

            if (($_SESSION['user']['token'] - $tournamentPrice) >= 0) {

                $hasEnoughToken = true;
            }

            foreach ($participants as $participant) {
                if ($participant['user_id'] === $_SESSION['user']['id']) {
                    $isPlayerInTournament = true;
                }
            }

            if (count($participants) < 8 && $isPlayerInTournament === false && $hasEnoughToken === true) {

                $this->tournamentModel->addUserInTournament(key($_POST));

                $_SESSION['user']['token'] -= $tournamentPrice;

                header('location: /listTournament');
                exit();

            } else {

                echo("<p style='color: red;'>Maximum players already joined this tournament or you already joined the tournament or you don't have enough tokens.</p>");

            }
            
        }
    }

    public function addPlayerTournament() {

        $users = $this->tournamentModel->findAllUsers();

        if (isset($_POST)) {

            foreach ($users as $key => $user) {

                if ($_POST['playerPseudo'] === $user['pseudo']) {

                    $this->tournamentModel->addPlayerTournament(key($_POST), $user['id']); 
                }
            }    
        }

        header('location: /listTournament');
        exit();
    }

    public function createMatch(int $tournamentId) {

        if (isset($_POST['buttonCreateMatch'])) {

            $this->tournamentModel->createMatch($_POST['player1'], $_POST['goal_player1'], $_POST['player2'], $_POST['goal_player2'], $tournamentId, $_POST['isFinished']);
            
            header('location: /listTournament/' .$tournamentId);
            exit();
        }

        echo $this->twig->render('tournament/createMatch.html.twig', [
            'tournament' => $tournamentId,
        ]);
    }

    public function updateMatch(int $id) {

        if (isset($_POST)) {

            $this->tournamentModel->updateMatch($id);
            
            header('location: /updateMatch/' . $id);
            exit();
        }

        $matchDetail = $this->tournamentModel->getMatchInfo($id);

        echo $this->twig->render('tournament/updateMatch.html.twig', [
            'matchDetail' => $matchDetail,
        ]);
    }
}