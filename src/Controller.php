<?php

require_once __DIR__ . '/Command.php';
require_once __DIR__ . '/User.php';

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\Message;
use Longman\TelegramBot\Entities\Keyboard;

class Controller{
    private $connection;

    private $message; //json contenente i dati della chat, dell'utente e del messaggio
    private $chat_id; 
    private $text;

    private $command; //oggetto per gestire i comandi

    private $keyboard;
    private $keyboard_user;

    private $request;
    private $user;
    
    public function __construct($database, $message_received) {
        date_default_timezone_set('Europe/Rome');
        
        $this->connection = $database;
        $this->message = new Message($message_received['message']);
    }
    
    public function setParameters(){
        $this->request = new Request();
        $this->chat_id = $this->message->getChat()->getId();
        $this->text = $this->message->getText();
        
        // messaggio di test
        // $this->request::sendMessage([
        //     'chat_id' => $this->chat_id,
        //     'text'=> 'messaggio di test',
        //     'parse_mode' => 'HTML'
        // ]);
        
        $this->user = new User($this->chat_id, $this->message->getChat()->getUsername(), $this->connection);
    }

    public function start(){
        $this->command = new Command($this->connection, $this->text, $this->user, $this->entities); //creazione del comando

        //$this->request::sendMessage($this->command->httpAnswer(password_hash($this->chat_id, PASSWORD_BCRYPT)));
        $this->command->setResponse($this->request);
        
        if($this->user->isNew()){ //gestione del nuovo utente: utente creato = new record e set tastiera
            $this->request::sendMessage(
                $this->command->makeAction()
            ); 
        } else {        
            if ($this->user->getAction()==NULL){ //se non sta effettuando alcuna operazione (come scrivi, modifica o programma)    
                if ($this->command->getCommand() == NULL) // se il comando su db non viene trovato, imposta come comando inserito dall'utente command_not_found
                    $this->command->setCommand("command_not_found");
                
                $action_message = $this->command->makeAction();
                if($action_message != NULL)
                    $this->request::sendMessage(
                        $action_message
                );
                
            }
        }
    }
}