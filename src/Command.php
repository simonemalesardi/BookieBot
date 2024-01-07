<?php

use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;

class Command{
    private $command; 
    private $answer;
    private $text_menu;
    public $action;
    private $connection; 
    private $user_menu;
    private $buttons;
    private $keyboard_object;
    private $text;
    private $user;
    private $privileges;
    private $attachment;
    private $entities;

    public function __construct(&$connection, $text, $user, $entities) {
        $this->connection = $connection;
        $this->text = $text;  
        $this->user = $user;
        $this->setCommand($text);
        $this->entities = $entities;
    }

    public function getCommand(){
        return $this->command;
    }

    public function setCommand($text){
        $command_row = $this->searchCommand($text);
        if($command_row != NULL){
            $this->command = $command_row['command'];
            $this->answer = $command_row['answer'];
            $this->text_menu = $command_row['text_menu'];
            $this->action = $command_row['action'];    
            $this->attachment = $command_row['attachment'];    
        } else 
            $this->command = NULL;
    }

    public function setResponse($response){
        $this->response=$response;
    }

    public function getAnswer(){
        return $this->answer;
    }

    // cerca su nella tabella comandi il comando lanciato dall'utente 
    private function searchCommand($text){ 
        //qui devo fare la join
        $sql = "SELECT * FROM commands WHERE (command = :command or text_menu = :command) and privileges <= :privileges";
        $query = $this->connection->prepare($sql);
        $query->execute(['command' => $text, 'privileges' => $this->user->getPrivileges()]);
        $command = $query->fetchAll();
        
        if (sizeof($command) == 1) {
            return $command[0];
        }
        return NULL;
    }

    // indirizza le operazioni in base all'azione intrapresa dall'utente
    public function makeAction(){
        switch($this->action){
            case "init":
                return $this->init();
                break;
            case "getMessage":
                // case corrispondente a quando l'utente chiede /info, /start /regole etc.
                return $this->user->getMenu() > 0 ? $this->getMessage(true) : $this->getMessage();
                break;

            // case "booking": 
            //     //chiedere in questo caso l'orario per la prenotazione
            //     break;

            //inserire tutti i casi delle azioni che può intraprendere l'utente ("booking")
        }   
    }

    // è la funzione eseguita ogni volta che l'utente digita /start o preme il bottone Start (il pulsante Start appare anche ogni volta che l'utente resetta la conversazione)
    private function init(){ 
        $answer =  ($this->user->isNew()) ? $this->answer : "Ci siamo già presentati, ma nel caso in cui non ti ricordassi mi ripresento!\n\n".$this->answer; 

        return $this->httpAnswer($answer, $this->setKeyboard(0)); 
    }

    // viene composto il json che permette di ritorna la risposta che verrà inviata all'utente
    public function httpAnswer($text, $keyboard = NULL, $chat_id = NULL){
        if($chat_id == NULL) $chat_id = $this->user->getChatID();
        $http = [
            'chat_id' => $chat_id,
            'text'=> $text,
            'parse_mode' => 'HTML'
        ];
        if($keyboard != NULL)
            $http = [
                'chat_id' => $chat_id,
                'text'=> $text,
                'reply_markup' => $keyboard,
                'parse_mode' => 'HTML'
            ];

        return $http;
    }

    public function setOperation($user_action){
        $this->user_action = $user_action;

        if ($this->command == '/annulla'){
            $this->response::sendMessage($this->getMessage(true));
        } else {
            // tutte le altre operazioni
        }   
    }

    private function activateMenu(){
        $this->response::sendMessage($this->httpAnswer("Prima di inviare un messaggio attiva il menu apposito, tramite il comando /scrivi"));
    }

    private function getMessage($changeMenu=false){
        
        $keyboard = NULL;
        if ($changeMenu){ // torna alla keyboard precedente
            $keyboard = $this->changeUserMenu($this->user->getMenu()-1, NULL, true);
        }

        return $this->httpAnswer($this->answer, $keyboard);
    }

    
    private function getDiamonds(){
    }

    private function deleteReservation(){
    }

    private function bookASeat(){
    }

    // ritorna la keyboard composta da tutti i bottoni settati a db
    public function setKeyboard($menu){
        $keyboard = new Keyboard([]);

        $sql = "SELECT * FROM keyboards WHERE id = :id and admin <= :admin";
        $query = $this->connection->prepare($sql);
        $query->execute(['id' => $menu, 'admin' => $this->user->getPrivileges()]);
        $buttons = $query->fetchAll();
        
        usort($buttons, function($a, $b) {
                return $a['position'] - $b['position'];
            });

        $array_buttons = [];
        
        foreach ($buttons as $button){
            $size = sizeof($array_buttons);

            if ($size == 0) {
                if ($button['style']=='half')
                    array_push($array_buttons, [$button['command']]);
                if ($button['style']=='full'){
                    array_push($array_buttons, [$button['command']]);
                    array_push($array_buttons, []);  
                }
            }
            else{
                if (sizeof($array_buttons[$size-1])==0){
                    if($button['style']=='half')
                        array_push($array_buttons[$size-1], $button['command']); 
                    else{
                        array_push($array_buttons[$size-1], $button['command']); 
                        array_push($array_buttons, []);  
                    }
                } else if (sizeof($array_buttons[$size-1])==1 && $button['style']=='half') {
                    array_push($array_buttons[$size-1], $button['command']);  
                } else if (sizeof($array_buttons[$size-1])==2 && $button['style']=='half'){
                    array_push($array_buttons, [$button['command']]);  
                } else {
                    array_push($array_buttons, [$button['command']]);  
                    array_push($array_buttons, []);  
                }
            }
        }

        foreach ($array_buttons as $button){
            $keyboard->addRow(...$button); 
        }
        
        return $keyboard;
    }

}

?>