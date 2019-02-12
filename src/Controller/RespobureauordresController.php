<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use App\Model\Entity\Article;


class respobureauordresController extends AppController {

    public function beforeFilter(Event $event)
    {
        // allow only login, forgotpassword
        $this->Auth->authorize = 'controller';
        $usrole = $this->Auth->user('role');
        if($usrole!='respobureauordre' && $usrole!='admin')
        {

            $this->Flash->error(__('Vous ne pouvez pas acceder a ce lien'));
            return $this->redirect(
                ['controller' => 'Users', 'action' => 'logout']
            );
        }
        $this->Auth->deny();

    }


    public function index() {


        $connection=ConnectionManager::get('default');
        $CourrierArrivees=TableRegistry::get('CourrierArrivees');
        $CourrierDeparts=TableRegistry::get('CourrierDeparts');
        $Expediteurs=TableRegistry::get('Expediteurs');
        $Destinataires=TableRegistry::get('Destinataires');

        $id=$this->Auth->user('id');
        $name = $connection->execute('SELECT nom_fct , prenom_fct ,genre ,somme, specialite, CIN, email,phone FROM fonctionnaires WHERE user_id=?',[$id])->fetchAll('assoc');
        $this->set('name',$name);
        if($name[0]['genre']=='F') {
            $genre='Mme';
        }
        else {
            $genre = 'Mr';
        }
        $this->set('genre',$genre);
        $nom = $name[0]['nom_fct'].' '.$name[0]['prenom_fct'];
        $this->set('nom',$nom);
        $courrierArrivee = $connection->execute('SELECT * FROM courrier_arrivees')->fetchAll('assoc');
        $courrierDepart = $connection->execute('SELECT * FROM courrier_departs')->fetchAll('assoc');
        $expediteur = $connection->execute('SELECT * FROM expediteurs')->fetchAll('assoc');
        $destinataire = $connection->execute('SELECT * FROM destinataires')->fetchAll('assoc');

        $couArr=count($courrierArrivee);
        $couDep=count($courrierDepart);
        $dest=count($destinataire);
        $exp=count($expediteur);



        $usrole=$this->Auth->user('role');
        $this->set('couArr',$couArr);
        $this->set('couDep',$couDep);
        $this->set('dest',$dest);
        $this->set('exp',$exp);

        $this->set('role',$usrole);
        $this->render('/Espaces/respobureauordres/home');
    }

/***** Ibtihal ****/





    /*************************************** Courrier Depart *********************/////////////////////////////////////////////////////////////////////

    public function indexDepart1()
    {
        $this->updateEtatDepart();
        $connection=ConnectionManager::get('default');
        $CourrierDeparts = TableRegistry::get('courrierDeparts');
        $courrierDeparts = $this->paginate($CourrierDeparts);

        $courrierDepart = $connection->execute('SELECT courrier_departs.courrier,courrier_departs.accuse,courrier_departs.id,courrier_departs.etat_courrier, courrier_departs.destinataire_id,destinataires.nomComplet_destinataire, courrier_departs.service, courrier_departs.date_depart,courrier_departs.désignation,courrier_departs.type_courrier,courrier_departs.necessite FROM courrier_departs
            JOIN destinataires ON courrier_departs.destinataire_id= destinataires.id

            ')->fetchAll('assoc');

        $this->set('courrierDepart',$courrierDepart);
        $this->set(compact('courrierDeparts'));
        $this->set('_serialize', ['courrierDeparts']);
        $this->render('/Espaces/respobureauordres/indexDepart1');
    }
    public function initialize(){
        parent::initialize();


        $this->loadComponent('Flash');

        $this->loadModel('CourrierDeparts');


    }

    public function addDepart1()
    {
        $connexion=ConnectionManager::get('default');
        $CourrierDeparts = TableRegistry::get('courrierDeparts');
        $courrierDepart = $CourrierDeparts->newEntity();
        if ($this->request->is('post')) {
            if($this->request->data['necessite']=="Non")
            {
                $this->request->data['etat_courrier']='*';
                $this->request->data['accuse']='*';
            }
            if($this->request->data['necessite']=="Oui")
            {
                $this->request->data['etat_courrier']='en attente';
                $this->request->data['accuse']='-';
            }
            if(!empty($this->request->data['courrier']['name'])){
                $courrierName = $this->request->data['courrier']['name'];

                $uploadPath = WWW_ROOT.DS.'/uploads/files/';
                $uploadcourrier = $uploadPath.$courrierName;


                $courrierDepart = $CourrierDeparts->patchEntity($courrierDepart, $this->request->data);
                if(move_uploaded_file($this->request->data['courrier']['tmp_name'],$uploadcourrier)){
                    $courrierDepart->courrier = $courrierName;
                    if ($CourrierDeparts->save($courrierDepart)) {
                        $this->Flash->success(__('Le courrier Depart a été sauvegardé.'));

                        return $this->redirect(['action' => 'indexDepart1']);
                    }else{
                        $this->Flash->error(__('Le courrier Depart ne peut pas étre sauvegardé. essayer une autre fois'));
                    }
                }else{
                    $this->Flash->error(__('Le fichier n\'a pas ete emporté. essayer une autre fois'));
                }
            }else{
                $this->Flash->error(__('choisissez un fichier a telecharger.'));
            }

        }
        $destinataires = $CourrierDeparts->Destinataires->find('list', ['limit' => 200]);
        $Destinataires = TableRegistry::get('destinataires');
        $dest = $Destinataires->find("all", array(
            "joins" => array(
                array(

                    "table" => "destinataires",
                    "conditions" => array(
                        "courrier_departs.destinataire_id = destinataires.id"
                    )
                )
            ),
            "fields" =>"destinataires.nomComplet_destinataire"
        ));
        $donne_demande =  $connexion->execute('SELECT destinataires.nomComplet_destinataire, destinataires.id FROM destinataires ')->fetchAll('assoc');


        $this->set('donne_demande',$donne_demande);

        $destinataire=$dest->toArray();
        $Destinataires=array();
        for ($i=0;$i<count($destinataire);$i++) {
            $Destinataires[]=$destinataire[$i]['nomComplet_destinataire'];
        }

        $files = $CourrierDeparts->find('all', ['order' => ['CourrierDeparts.created' => 'DESC']]);
        $filesRowNum = $files->count();
        $this->set('files',$files);
        $this->set('filesRowNum',$filesRowNum);
        $this->set(compact('courrierDepart', 'destinataires'));
        $this->set('_serialize', ['courrierDepart']);
        $this->render('/Espaces/respobureauordres/addDepart1');
    }

    public function addDest1()
    {
        $Destinataires=TableRegistry::get('Destinataires');
        $destinataire = $Destinataires->newEntity();
        if ($this->request->is('post')) {
            $destinataire = $Destinataires->patchEntity($destinataire, $this->request->data);
            if ($Destinataires->save($destinataire)) {
                $this->Flash->success(__('le destinataire a été sauvegardé.'));

                return $this->redirect(['action' => 'addDepart1']);
            }
            $this->Flash->error(__('le Destinataire ne peut pas étre sauvegardé. essayer une autre fois'));
        }
        $this->set(compact('destinataire', 'courrierDepart'));
        $this->set('_serialize', ['destinataire']);
        $this->render('/Espaces/respobureauordres/addDest1');

    }

    public function editDepart1($id = null)
    {   $connection = ConnectionManager::get('default');
        $CourrierDeparts =TableRegistry::get('CourrierDeparts');
        $courrierDepart = $CourrierDeparts->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {

            $courrierDepart = $CourrierDeparts->patchEntity($courrierDepart, $this->request->data);
            if ($CourrierDeparts->save($courrierDepart)) {
                $this->Flash->success(__('Le {0} a été sauvegardé.', 'Courrier Depart'));
                return $this->redirect(['action' => 'indexDepart1']);
            } else {
                $this->Flash->error(__('Le courrier Depart ne peut pas étre sauvegardé. essayer une autre fois'));
            }
        }

        $dest=$connection->execute('SELECT destinataires.id, destinataires.nomComplet_destinataire FROM destinataires JOIN courrier_departs ON destinataires.id= courrier_departs.destinataire_id WHERE courrier_departs.id = :id',[':id'=>$id])->fetchAll('assoc');
        $destinataires = $connection->execute('SELECT destinataires.id, destinataires.nomComplet_destinataire FROM destinataires WHERE destinataires.id !='.$dest[0]['id'])->fetchAll('assoc');

        $this->set('dest',$dest);
        $this->set(compact('courrierDepart','destinataires','destinataire'));
        $this->set('_serialize', ['courrierDepart','destinataire']);
        $this->render('/Espaces/respobureauordres/editDepart1');
    }

    public function approuver($id = null)
    {
        $CourrierDeparts =TableRegistry::get('CourrierDeparts');
        $courrierDepart = $CourrierDeparts->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {

            $this->request->data['etat_courrier'] = 'courrier reçu' ;
            $courrierDepart = $CourrierDeparts->patchEntity($courrierDepart, $this->request->data);

            if(!empty($this->request->data['accuse']['name'])){
                $accuseName = $this->request->data['accuse']['name'];

                $uploadPath = WWW_ROOT.DS.'/uploads/files/';
                $uploadaccuse = $uploadPath.$accuseName;


                $courrierDepart = $CourrierDeparts->patchEntity($courrierDepart, $this->request->data);
                if(move_uploaded_file($this->request->data['accuse']['tmp_name'],$uploadaccuse)){
                    $courrierDepart->accuse = $accuseName;
                    if ($CourrierDeparts->save($courrierDepart)) {
                        $this->Flash->success(__('Le {0} a été sauvegardé.', 'Courrier Depart'));

                        return $this->redirect(['action' => 'indexDepart1']);
                    }else{
                        $this->Flash->error(__('Le courrier Depart ne peut pas étre sauvegardé. essayer une autre fois'));
                    }
                }else{
                    $this->Flash->error(__('Le fichier n\'a pas ete emporté. essayer une autre fois'));
                }
            }else{
                $this->Flash->error(__('choisissez un fichier a telecharger.'));
            }
        }


        $this->set(compact('courrierDepart'));
        $this->set('_serialize', ['courrierDepart']);
        $this->render('/Espaces/respobureauordres/approuver');
    }

    public function viewDepart1($id = null)
    {
        $CourrierDeparts =TableRegistry::get('CourrierDeparts');
        $courrierDepart = $CourrierDeparts->get($id, [
            'contain' => ['Destinataires']
        ]);

        $this->set('courrierDepart', $courrierDepart);
        $this->set('_serialize', ['courrierDepart']);;
        $this->render('/Espaces/respobureauordres/viewDepart1');
    }

    public function deleteDepart1($id = null)
    {
        $CourrierDeparts =TableRegistry::get('CourrierDeparts');
        $this->request->allowMethod(['post', 'delete']);
        $courrierDepart = $CourrierDeparts->get($id);
        if ($CourrierDeparts->delete($courrierDepart)) {
            $this->Flash->success(__('Le {0} a été supprimé.', 'Courrier Depart'));
        } else {
            $this->Flash->error(__('Le {0} ne peut pas etre supprimer. essayer une autre fois.', 'Courrier Depart'));
        }
        return $this->redirect(['action' => 'indexDepart1']);
    }


    private function updateEtatDepart()
    {
        $connection=ConnectionManager::get('default');
        $CourrierDeparts =TableRegistry::get('CourrierDeparts');
        $y=date("Y");
        $m=date("m");
        $d=date("d");

        $courrierDepart=$connection->execute('SELECT  ADDDATE(date_depart, INTERVAL 1 DAY), id, etat_courrier FROM courrier_departs WHERE ADDDATE(date_depart, INTERVAL 1 DAY) < "'.$y.'-'.$m.'-'.$d.'"  ')->fetchAll('assoc');

        for($i=0;$i<count($courrierDepart);$i++)
        {
            if($courrierDepart[$i]['etat_courrier'] =="en attente")
            {

                $connection->execute('UPDATE courrier_departs SET etat_courrier = ? WHERE id = ?', ['courrier non reçus',$courrierDepart[$i]['id'] ]);
            }

        }
    }

    public function envoyer()
    {
        $CourrierDeparts =TableRegistry::get('CourrierDeparts');
        $courrierDepart = $CourrierDeparts->newEntity();
        if ($this->request->is('post')) {
            //if(isset($_FILES) && (bool) $_FILES)
            if (file_exists($_FILES['attach1']['tmp_name']) || is_uploaded_file($_FILES['attach1']['tmp_name']))
            {

                $allowedExtensions = array("pdf","docx","doc","gif","jpeg","jpg","png","rtf","txt","rar","zip",);
                $files=array();

                foreach ($_FILES as $name => $file) 
                {

                    $file_name = $file['name'];
                    $temp_name = $file['tmp_name'];

                    $path_parts = pathinfo($file_name);
                    $ext = $path_parts['extension'];
                    if(!in_array($ext, $allowedExtensions))
                    {
                        $this->Flash->error(__('extension not allowed ', 'mail '));
                        return $this->redirect(['action' => 'envoyer']);

                    }

                    $server_file = "../webroot/uploads/$path_parts[basename]";
                    move_uploaded_file($temp_name, $server_file);
                    array_push($files, $server_file);
                }

                $to = $this->request->data['email'];
                $from = "ensakhouribga2007@gmail.com";
                $subject = $this->request->data['subject'];
                $msg = $this->request->data['msg'];
                $headers = "From: $from";


                $semi_rand = md5(time());
                $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";

                $headers .= "\nMIME-Version: 1.0\n";
                $headers .= "Content-Type: multipart/mixed;\n";
                $headers .= " boundary=\"{$mime_boundary}\"";

                $message ="\n\n--{$mime_boundary}\n";
                $message .="Content-Type: text/plain; charset=\"iso-8859-1\"\n";
                $message .="Content-Transfer-Encoding: 7bit\n\n" . $msg . "\n\n";
                $message .= "--{$mime_boundary}\n";

                foreach ($files as $file) 
                {
                    $aFile = fopen($file, "rb");
                    $data = fread($aFile, filesize($file));
                    fclose($aFile);
                    $data = chunk_split(base64_encode($data));
                    $message .="Content-Type: {\"application/octet-stream\"};\n";
                    $message .= "name=\"$file\"\n";
                    $message .= "Content-Disposition: attachment;\n";
                    $message .= " filename=\"$file\"\n";
                    $message .= "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
                    $message .= "--{$mime_boundary}\n";
                }
                $ok = mail($to, $subject, $message ,$headers);
                if ($ok) 
                {
                    $this->Flash->success(__('l\' email est envoyé.'));
                    return $this->redirect(['action' => 'indexDepart1']);
                }
                else
                {
                    $this->Flash->error(__('l\' email ne peut pas etre envoyer! '));
                    return $this->redirect(['action' => 'envoyer']);
                }
            }
            else
            {
                $to = $this->request->data['email'];
                $from = "ensakhouribga2007@gmail.com";
                $subject = $this->request->data['subject'];
                $msg = $this->request->data['msg'];
                $headers = "From: $from";


                $semi_rand = md5(time());
                $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";

                $headers .= "\nMIME-Version: 1.0\n";
                $headers .= "Content-Type: multipart/mixed;\n";
                $headers .= " boundary=\"{$mime_boundary}\"";

                $message ="\n\n--{$mime_boundary}\n";
                $message .="Content-Type: text/plain; charset=\"iso-8859-1\"\n";
                $message .="Content-Transfer-Encoding: 7bit\n\n" . $msg . "\n\n";
                $message .= "--{$mime_boundary}\n";
                $ok = mail($to, $subject, $message ,$headers);
                if ($ok) 
                {
                    $this->Flash->success(__('l\' email est envoyé.'));
                    return $this->redirect(['action' => 'indexDepart1']);
                }
                else
                {
                    $this->Flash->error(__('l\' email ne peut pas etre envoyer! '));
                    return $this->redirect(['action' => 'envoyer']);
                }
            }
        }
        error_reporting(0);
        $this->set('CourrierDeparts',$CourrierDeparts);
        $this->render('/Espaces/respobureauordres/jointe');
    }



    public function trierDepart(){


        $connection=ConnectionManager::get('default');
        $destinataires = $connection->execute('SELECT destinataires.nomComplet_destinataire FROM destinataires ')->fetchAll('assoc');
        $this->set('destinataires',$destinataires);
        $this->render('/Espaces/respobureauordres/trierDepart');
    }


    public function filterDepart($limit=100)
    {
        $connection=ConnectionManager::get('default');
        $CourrierDeparts =TableRegistry::get('CourrierDeparts');
        $courrierDeparts = $this->paginate($CourrierDeparts);

        $this->paginate = ['contain' => ['Destinataires']];

        $con= ConnectionManager::get('default');


        for($i=1;$i<9;$i++)
        {
            if(!empty($_POST['cat'.$i.'']))
            {
                $catt[$i]=$_POST['cat'.$i.''];
            }
            else{
                $catt[$i]=NULL;
            }
        }

        $cat1=$catt[1];
        $cat2=$catt[2];
        $cat3=$catt[3];
        $cat4=$catt[4];
        $cat5=$catt[5];
        $cat6=$catt[6];
        $cat8=$catt[8];

        if(!empty($_POST['cat7']))
        {
            $cat7=$_POST['cat7'];
        }
        else{
            $cat7="";
        }



        $courrierDepart=$con->execute("SELECT courrier_departs.id,courrier_departs.etat_courrier, courrier_departs.destinataire_id,destinataires.nomComplet_destinataire, courrier_departs.service, courrier_departs.date_depart,courrier_departs.désignation,courrier_departs.type_courrier,courrier_departs.necessite,courrier_departs.courrier,courrier_departs.accuse FROM courrier_departs JOIN destinataires ON courrier_departs.destinataire_id = destinataires.id WHERE courrier_departs.id LIKE '%" .$cat1. "%' AND courrier_departs.date_depart LIKE '%" .$cat2. "%' AND courrier_departs.désignation LIKE '%" .$cat3. "%'  AND courrier_departs.destinataire_id IN(SELECT destinataires.id FROM destinataires WHERE destinataires.nomComplet_destinataire LIKE'%" .$cat4. "%') AND courrier_departs.type_courrier LIKE '%" .$cat5. "%' AND courrier_departs.service LIKE '%" .$cat6. "%' AND courrier_departs.etat_courrier LIKE '%" .$cat7. "%' AND courrier_departs.necessite LIKE '%" .$cat8. "%'")->fetchAll('assoc');



        $this->set(compact('courrierDeparts'));
        $this->set('_serialize', ['courrierDeparts']);
        $this->set('courrierDepart',$courrierDepart);
        $this->render('/Espaces/respobureauordres/filterDepart');
    }

    public function supprimerArchiveDepart()
    {   $connection = ConnectionManager::get('default');
        $this->request->allowMethod(['post', 'delete']);

        $CourrierDeparts =TableRegistry::get('CourrierDeparts');
        $courrier=$connection->execute('SELECT id FROM courrier_departs WHERE courrier_departs.etat_courrier="interrompu" OR courrier_departs.etat_courrier="traité"')->fetchAll('assoc');
        for($i=0;$i<count($courrier);$i++)
        {
            $connection->execute('DELETE FROM courrier_departs WHERE id='.$courrier[$i]['id']);
        }
        return $this->redirect(['action' => 'indexDepart1']);
    }
    public function miseAzeroDepart()
    {
        $this->request->allowMethod(['post', 'delete']);
        $connection=ConnectionManager::get('default');
        $connection->execute('TRUNCATE TABLE courrier_departs');
        return $this->redirect(['action' => 'indexDepart1']);
    }

    public function Indexdest()
    {
        $Destinataires = TableRegistry::get('destinataires');
        $Destinataires = $this->paginate($Destinataires);


        $this->set(compact('Destinataires'));
        $this->set('_serialize', ['Destinataires']);
        $usrole=$this->Auth->user('username');
        $this->set('role',$usrole);
        $this->render('/Espaces/respobureauordres/indexDest');
    }



    //////////////////////////////////////////////////////////////////////////////////

    public function addDest()
    {
        $Destinataires=TableRegistry::get('Destinataires');
        $destinataire = $Destinataires->newEntity();
        if ($this->request->is('post')) {
            $destinataire = $Destinataires->patchEntity($destinataire, $this->request->data);
            if ($Destinataires->save($destinataire)) {
                $this->Flash->success(__('Le destinataire a ete sauvegarder.'));
                return $this->redirect(['action' => 'indexDest']);
            } else {
                $this->Flash->error(__('Le Destinataire ne peut pas étre sauvegardé. essayer une autre fois'));
            }
        }
        $this->set(compact('destinataire', 'courrierDepart'));
        $this->set('_serialize', ['destinataire']);
        $this->render('/Espaces/respobureauordres/addDest');

    }





    public function viewDest($id = null)
    {

        $Destinataires = TableRegistry::get('destinataires');
        $destinataire = $Destinataires->get($id, [
            'contain' => ['CourrierDeparts']
        ]);





        $this->set('destinataire', $destinataire);
        $this->set('_serialize', ['destinataire']);
        $this->render('/Espaces/respobureauordres/viewDest');
    }

    public function deleteDest($id = null)
    {
        $Destinataires = TableRegistry::get('destinataires');
        $this->request->allowMethod(['post', 'delete']);
        $destinataire = $Destinataires->get($id);
        if ($Destinataires->delete($destinataire)) {
            $this->Flash->success(__('le destinataire a ete supprimer.'));
        } else {
            $this->Flash->error(__('Le destinataire ne peut pas etre supperimer. essayer une autre fois'));
        }

        return $this->redirect(['action' => 'indexDest']);
    }



    public function editDest($id = null)
    {
        $Destinataires=TableRegistry::get('Destinataires');
        $destinataire = $Destinataires->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $destinataire = $Destinataires->patchEntity($destinataire, $this->request->data);
            if ($Destinataires->save($destinataire)) {
                $this->Flash->success(__('Le destinataire a ete sauvegarder.'));

                return $this->redirect(['action' => 'indexDest']);
            }
            $this->Flash->error(__('Le Destinataire ne peut pas étre sauvegardé. essayer une autre fois'));
        }
        $this->set(compact('destinataire'));
        $this->set('_serialize', ['destinataire']);
        $this->render('/Espaces/respobureauordres/editDest');
    }







    /*************************Fin******************/






    ////////////////////////////////////Expediteur //////////////////////////////////

    public function indexexpediteur() {

        $Expediteurs = TableRegistry::get('expediteurs');
        $Expediteurs = $this->paginate($Expediteurs);

        $this->set(compact('Expediteurs'));
        $this->set('_serialize', ['Expediteurs']);
        $usrole=$this->Auth->user('username');
        $this->set('role',$usrole);
        $this->render('/Espaces/respobureauordres/indexexpediteur');
    }







    public function AzzouziEditexpediteur($id = null)
    {
        $Expediteurs=TableRegistry::get('Expediteurs');
        $expediteur = $Expediteurs->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $expediteur = $Expediteurs->patchEntity($expediteur, $this->request->data);
            if ($Expediteurs->save($expediteur)) {
                $this->Flash->success(__('Bien enregistré .', 'Expediteur'));
                return $this->redirect(['action' => 'indexexpediteur']);
            } else {
                $this->Flash->error(__('Erreur d\'enregistrement, ressayez!!', 'Expediteur'));
            }
        }
        $this->set(compact('expediteur'));
        $this->set('_serialize', ['expediteur']);
        $this->render('/Espaces/respobureauordres/Azzouzi_editexpediteur');
    }

    public function viewExpediteur($id = null)
    {
        $Expediteurs=TableRegistry::get('Expediteurs');
        $expediteur = $Expediteurs->get($id, [
            'contain' => ['CourrierArrivees']
        ]);

        $this->set('expediteur', $expediteur);
        $this->set('_serialize', ['expediteur']);
        $this->render('/Espaces/respobureauordres/viewExpediteur');
    }


    public function AzzouziSupprimerexpediteur($id = null)
    {
        $Expediteurs=TableRegistry::get('Expediteurs');
        $this->request->allowMethod(['post', 'delete']);
        $expediteur = $Expediteurs->get($id);
        if ($Expediteurs->delete($expediteur)) {
            $this->Flash->success(__('Suppression réussite.', 'Expediteur'));
        } else {
            $this->Flash->error(__('Erreur de suppression, ressayez !!.', 'Expediteur'));
        }
        return $this->redirect(['action' => 'indexexpediteur']);
        $this->render('/Espaces/respobureauordres/Azzouzi_supprimerexpediteur');
    }


    //////////////////////////////////////////DEstinataire ////////////////////////













    /*************************************** Courrier Depart *********************/



    public function viewArrivee($id = null)
    {
        $connection=ConnectionManager::get('default');
        $CourrierArrivees=TableRegistry::get('CourrierArrivees');
        $courrierArrivee = $CourrierArrivees->get($id, ['contain' => ['Expediteurs','Services']]);


        $this->set('courrierArrivee', $courrierArrivee);
        $this->set('_serialize', ['courrierArrivee','service']);
        $this->render('/Espaces/respobureauordres/viewArrivee');
    }


    //courrier arriver*******************************************************************************************************
    public function indexArrivee()
    {
        $this->updateEtat2();

        $connection=ConnectionManager::get('default');
        $CourrierArrivees=TableRegistry::get('CourrierArrivees');

        $courrierArrivee = $connection->execute('SELECT courrier_arrivees.courrier,courrier_arrivees.accuse,courrier_arrivees.id,courrier_arrivees.etat_du_courrier, courrier_arrivees.expediteur_id,expediteurs.nomComplet_expediteur, courrier_arrivees.service_id, courrier_arrivees.date_arrivee,courrier_arrivees.Désignation,courrier_arrivees.type_courrier,courrier_arrivees.necessité_du_traitement,courrier_arrivees.Priorité,courrier_arrivees.date_limite_du_traitement,courrier_arrivees.courrier_retourne FROM courrier_arrivees
            JOIN expediteurs ON courrier_arrivees.expediteur_id= expediteurs.id

            ')->fetchAll('assoc');
        $service=$connection->execute('SELECT services.nom_service, services.id FROM services 
            JOIN courrier_arrivees ON services.id = courrier_arrivees.service_id
            ')->fetchAll('assoc');
        $servivces=array();
        for($i=0;$i<count($service);$i++)
        {
            $a=$service[$i]['id'];
            $services[$a]=$service[$i]['nom_service'];
        }



        $this->set('courrierArrivee',$courrierArrivee);
        if (!empty($services)) {
            $this->set('services',$services);
        }


        $path = WWW_ROOT.'courrier';
        $this->set('path',$path);
        $this->render('/Espaces/respobureauordres/indexArrivee');


    }

    ////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////
    private function updateEtat2()
    {
        $connection=ConnectionManager::get('default');
        $CourrierArrivees=TableRegistry::get('CourrierArrivees');
        $y=date("Y");
        $m=date("m");
        $d=date("d");
        $courrierArrivee=$connection->execute('SELECT courrier_arrivees.date_limite_du_traitement, courrier_arrivees.id, courrier_arrivees.etat_du_courrier FROM courrier_arrivees WHERE courrier_arrivees.date_limite_du_traitement < "'.$y.'-'.$m.'-'.$d.'"  ')->fetchAll('assoc');

        for($i=0;$i<count($courrierArrivee);$i++)
        {
            if($courrierArrivee[$i]['etat_du_courrier'] !="traité")
            {

                $connection->execute('UPDATE courrier_arrivees SET etat_du_courrier = ? WHERE id = ?', ['interrompu',$courrierArrivee[$i]['id'] ]);
            }

        }
    }
    ////////////////////////////////////////////////////////////////////////////////////////
    public function updateEtat($id = null)
    {
        $CourrierArrivees=TableRegistry::get('CourrierArrivees');
        $courrierArrivee = $CourrierArrivees->get($id, [
            'contain' => ['Services']
        ]);
        $connection = ConnectionManager::get('default');
        $this->request->allowMethod(['post', 'Prête','Approuver','Délivré','Cmnt']);

        if($this->request->data['editer']=='Approuver')
        {
            $courrierArrivee->etat_du_courrier="en cours de traitement";
        }
        if($this->request->data['editer']=='Valider')
        {
            $courrierArrivee->etat_du_courrier="traité";
        }

        if ($CourrierArrivees->save($courrierArrivee)) {
            $this->Flash->success(__($this->request->data));
            return $this->redirect(['action' => 'indexArrivee']);
        }
        $this->Flash->error(__('Aucn changement n \' est effectué.'));
        return $this->redirect(['action' => 'indexArrivee']);
    }
//////////////////////////////////////////////////////////////////////////////////////////////////
    public function supprimerArchiveArrivee()
    {   $connection = ConnectionManager::get('default');
        $this->request->allowMethod(['post', 'delete']);

        $CourrierArrivees=TableRegistry::get('CourrierArrivees');
        $courrier=$connection->execute('SELECT id FROM courrier_arrivees WHERE courrier_arrivees.etat_du_courrier="interrompu" OR courrier_arrivees.etat_du_courrier="traité"')->fetchAll('assoc');
        for($i=0;$i<count($courrier);$i++)
        {
            $connection->execute('DELETE FROM courrier_arrivees WHERE id='.$courrier[$i]['id']);
        }
        return $this->redirect(['action' => 'indexArrivee']);
    }
    public function miseAzeroArrivee()
    {
        $this->request->allowMethod(['post', 'delete']);
        $connection=ConnectionManager::get('default');
        $connection->execute('TRUNCATE TABLE courrier_arrivees');
        return $this->redirect(['action' => 'indexArrivee']);

    }
    //////////////////////////////////////////////////////////////////////////////////////////////
    public function addArrivee()

    {
        $connexion=ConnectionManager::get('default');
        $CourrierArrivees=TableRegistry::get('CourrierArrivees');

        $courrierArrivee = $CourrierArrivees->newEntity();


        if ($this->request->is('post')) {
            if($this->request->data['necessité_du_traitement']=="Non")
            {
                $this->request->data['etat_du_courrier']='-';
                $this->request->data['date_limite_du_traitement']=NULL;
                $this->request->data['service_id']=NULL;
                $this->request->data['Priorité']='-';

            }
            if($this->request->data['necessité_du_traitement']=="Oui")
            {
                $this->request->data['etat_du_courrier']='en attente';


            }




            $courrierArrivee = $CourrierArrivees->patchEntity($courrierArrivee, $this->request->data);

            $extension1 = substr($this->request->getData('attachement1')["type"],-3);
            $titre=$connexion->execute("SELECT MAX(id)+1 as id FROM courrier_arrivees ")->fetchAll('assoc');
            $attachementPath = WWW_ROOT .DS. 'courrier'.DS. $titre['0']['id'].".".$extension1;
            move_uploaded_file($this->request->getData('attachement1')["tmp_name"], WWW_ROOT .DS. 'courrier'.DS.$titre['0']['id'].".".$extension1);

            //////////////////////////////////////////////////////////////////////////
            if(!empty($titre['0']['id']) && !empty($extension1)){
                $courrier_nom=$titre['0']['id'].".".$extension1;
            }
            else{
                $courrier_nom="";
            }



            $extension2 = substr($this->request->getData('attachement2')["type"],-3);
            $titre2=$connexion->execute("SELECT MAX(id)+1 as id FROM courrier_arrivees ")->fetchAll('assoc');
            $attachementPath2 = WWW_ROOT .DS. 'courrier'.DS. $titre2['0']['id'].'accuse'.".".$extension2;

            move_uploaded_file($this->request->getData('attachement2')["tmp_name"], WWW_ROOT .DS.'courrier'.DS.$titre2['0']['id'].'accuse'.".".$extension2);
            if(!empty($titre2['0']['id']) && !empty($extension2)){
                $accuse_nom=$titre2['0']['id'].'accuse'.".".$extension2;
            }
            else{
                $accuse_nom="";
            }

            $courrierArrivee->courrier = $courrier_nom;
            $courrierArrivee->accuse = $accuse_nom;

            //////////////////////////////////////////////////////////////////////////


            if ($CourrierArrivees->save($courrierArrivee)) {
                $this->Flash->success(__('Le courrier est sauvgardé'));

                return $this->redirect(['action' => 'indexArrivee']);
            }
            $this->Flash->error(__('Le courrier n\'est pas sauvgardé, ressayez !! '));
        }
        $expediteurs = $CourrierArrivees->Expediteurs->find('list', ['limit' => 200]);


        $Expediteurs = TableRegistry::get('expediteurs');
        $Services=TableRegistry::get('services');
        $serv=$Services->find("all", array(
            "joins" => array(
                array(

                    "table" => "courrier_arrivees",
                    "conditions" => array(
                        "courrier_arrivees.service_id = services.id"
                    )
                )
            ), "fields" =>["services.id","services.nom_service"]
        ));


        $exp = $Expediteurs->find("all", array(
            "joins" => array(
                array(

                    "table" => "expediteurs",
                    "conditions" => array(
                        "courrier_arrivees.expediteur_id = expediteurs.id"
                    )
                )
            ),
            "fields" =>"expediteurs.nomComplet_expediteur"
        ));


        $connection = ConnectionManager::get('default');

        $donne_demande =  $connection->execute('SELECT expediteurs.nomComplet_expediteur, expediteurs.id FROM expediteurs ')->fetchAll('assoc');


        $this->set('donne_demande',$donne_demande);

        $expediteur=$exp->toArray();
        $expediteurs=array();
        for ($i=0;$i<count($expediteur);$i++) {
            $expediteurs[]=$expediteur[$i]['nomComplet_expediteur'];
        }

        $service=$serv->toArray();

        $services=array();
        for ($i=0;$i<count($service);$i++) {
            $services[$i]['id']=$service[$i]['id'];
            $services[$i]['nom']=$service[$i]['nom_service'];

        }




        $expediteur = $Expediteurs->newEntity();
        if ($this->request->is('post')) {
            $expediteur = $Expediteurs->patchEntity($expediteur, $this->request->data);
            if ($Expediteurs->save($expediteur)) {
                $this->Flash->success(__('Bien enregistré .', 'Expediteur'));
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('Erreur d\'enregistrement, ressayez!!', 'Expediteur'));
            }
        }

        $this->set('services',$services);
        $this->set(compact('courrierArrivee', 'expediteurs', 'destinataires','services','expediteur'));
        $this->set('_serialize', ['courrierArrivee','expediteur']);
        $this->render('/Espaces/respobureauordres/addArrivee');



    }

    ///////////////////////////////////////////////////////////////////////////////////////////

    public function ajouterexp()
    {

        $Expediteurs = TableRegistry::get('expediteurs');

        $expediteur = $Expediteurs->newEntity();
        if ($this->request->is('post')) {
            $expediteur = $Expediteurs->patchEntity($expediteur, $this->request->data);

            if ($Expediteurs->save($expediteur)) {
                $this->Flash->success(__('Bien enregistré .', 'Expediteur'));
                return $this->redirect(['action' => 'addArrivee']);
            } else {
                $this->Flash->error(__('Erreur d\'enregistrement, ressayez!!', 'Expediteur'));
            }
        }


        $this->set(compact('courrierArrivee', 'expediteurs', 'destinataires','services','expediteur'));
        $this->set('_serialize', ['expediteur']);
        $this->render('/Espaces/respobureauordres/nouveau');

    }

    public function AjouterExpediteur()
    {

        $Expediteurs = TableRegistry::get('expediteurs');

        $expediteur = $Expediteurs->newEntity();
        if ($this->request->is('post')) {
            $expediteur = $Expediteurs->patchEntity($expediteur, $this->request->data);

            if ($Expediteurs->save($expediteur)) {
                $this->Flash->success(__('Bien enregistré .', 'Expediteur'));
                return $this->redirect(['action' => 'indexexpediteur']);
            } else {
                $this->Flash->error(__('Erreur d\'enregistrement, ressayez!!', 'Expediteur'));
            }
        }


        $this->set(compact('courrierArrivee', 'expediteurs', 'destinataires','services','expediteur'));
        $this->set('_serialize', ['expediteur']);
        $this->render('/Espaces/respobureauordres/AjouterExpediteur');

    }


    /////////////////////////////////////////////////////////////::::

    public function editArrivee($id = null)
    {
        $connection = ConnectionManager::get('default');
        $CourrierArrivees=TableRegistry::get('CourrierArrivees');
        $courrierArrivee = $CourrierArrivees->get($id, ['contain' => ['Expediteurs','Services']]);

        $this->set('courrierArrivee', $courrierArrivee);
       $courrierArrivee = $CourrierArrivees->get($id, [
            'contain' => ['Services']
        ]);
                if ($this->request->is(['patch', 'post', 'put'])) {
            $courrierArrivee = $CourrierArrivees->patchEntity($courrierArrivee, $this->request->data);
            if ($CourrierArrivees->save($courrierArrivee)) {
                $this->Flash->success(__('Bien enregistré .', 'Courrier Arrivee'));
                return $this->redirect(['action' => 'indexArrivee']);
            } else {
                $this->Flash->error(__('Erreur d\'enregistrement, ressayez!!', 'Courrier Arrivee'));
            }
        }
        $exp=$connection->execute('SELECT expediteurs.id, expediteurs.nomComplet_expediteur FROM expediteurs JOIN courrier_arrivees ON expediteurs.id= courrier_arrivees.expediteur_id WHERE courrier_arrivees.id = :id',[':id'=>$id])->fetchAll('assoc');
        $expediteurs = $connection->execute('SELECT expediteurs.id, expediteurs.nomComplet_expediteur FROM expediteurs WHERE expediteurs.id !='.$exp[0]['id'])->fetchAll('assoc');
        $serv=$connection->execute('SELECT services.id, services.nom_service FROM services JOIN courrier_arrivees ON services.id= courrier_arrivees.service_id WHERE courrier_arrivees.id = :id',[':id'=>$id])->fetchAll('assoc');
        $services = $connection->execute('SELECT services.id, services.nom_service FROM services')->fetchAll('assoc');

        $this->set('services',$services);
        $this->set('serv',$serv);
        $this->set('exp',$exp);
        $this->set(compact('courrierArrivee', 'expediteurs', 'destinataires','services','expediteur'));
        $this->set('_serialize', ['courrierArrivee','expediteur','service']);
        $this->set(compact('courrierArrivee', 'expediteurs', 'services','service'));
        $this->render('/Espaces/respobureauordres/editArrivee');
    }

    public function deleteArrivee($id = null)
    {
        $CourrierArrivees=TableRegistry::get('CourrierArrivees');
        $this->request->allowMethod(['post', 'delete']);
        $courrierArrivee = $CourrierArrivees->get($id);
        if ($CourrierArrivees->delete($courrierArrivee)) {
            $this->Flash->success(__('Suppression réussite.', 'Courrier Arrivee'));
            return $this->redirect(['action' => 'indexArrivee']);

        } else {
            $this->Flash->error(__('Erreur de suppression, ressayez !!.', 'Courrier Arrivee'));
        }
        return $this->redirect(['action' => 'index']);
        $this->render('/Espaces/respobureauordres/deleteArrivee');
    }


    public function trierArrivee(){
        $connection=ConnectionManager::get('default');
        $expediteurs = $connection->execute('SELECT expediteurs.nomComplet_expediteur FROM expediteurs ')->fetchAll('assoc');
        $this->set('expediteurs',$expediteurs);
        $services = $connection->execute('SELECT services.nom_service FROM services ')->fetchAll('assoc');
        $this->set('services',$services);


        $this->render('/Espaces/respobureauordres/trierArrivee');

    }




    public function filterArriveebis($limit=100)
    {

        $this->updateEtat2();

        $connection=ConnectionManager::get('default');
        $CourrierArrivees=TableRegistry::get('CourrierArrivees');
        $Expediteurs=TableRegistry::get('Expediteurs');
        //$CourrierArrivees=TableRegistry::get('CourrierArrivees');
        $this->paginate = ['contain' => ['Expediteurs','Services']];
        // $courrierArrivees = $this->paginate($CourrierArrivees);
        $courrierArrivee = $connection->execute('SELECT courrier_arrivees.courrier,courrier_arrivees.accuse,courrier_arrivees.id,courrier_arrivees.etat_du_courrier, courrier_arrivees.expediteur_id,expediteurs.nomComplet_expediteur, courrier_arrivees.service_id, courrier_arrivees.date_arrivee,courrier_arrivees.Désignation,courrier_arrivees.type_courrier,courrier_arrivees.necessité_du_traitement,courrier_arrivees.Priorité,courrier_arrivees.date_limite_du_traitement,courrier_arrivees.courrier_retourne FROM courrier_arrivees
            JOIN expediteurs ON courrier_arrivees.expediteur_id= expediteurs.id

            ')->fetchAll('assoc');
        $courrierArrivees=$courrierArrivee;
        $service=$connection->execute('SELECT services.nom_service, services.id FROM services 
            JOIN courrier_arrivees ON services.id = courrier_arrivees.service_id
            ')->fetchAll('assoc');
        $servivces=array();
        for($i=0;$i<count($service);$i++)
        {
            $a=$service[$i]['id'];
            $services[$a]=$service[$i]['nom_service'];
        }





        $this->set('courrierArrivee',$courrierArrivee);
        $this->set('courrierArrivees',$courrierArrivees);

        $this->set('services',$services);

        $CourrierArrivees =TableRegistry::get('CourrierArrivees');
        $con= ConnectionManager::get('default');

        for($i=1;$i<=11;$i++)
        {
            if(!empty($_POST['cat'.$i.'']))
            {
                $catt[$i]=$_POST['cat'.$i.''];
            }
            else{
                $catt[$i]=NULL;
            }
        }



        $cat1=$catt[1];
        $cat2=$catt[2];
        $cat3=$catt[3];
        $cat4=$catt[4];
        $cat5=$catt[5];
        $cat6=$catt[6];
        $cat9=$catt[9];
        


        $cat10=$catt[10];
        $cat11 = $catt[11];


        

        if(!empty($_POST['cat7']))
        {
            $cat7=$_POST['cat7'];
        }
        else{
            $cat7="";
        }
        if(!empty($_POST['cat8']))
        {
            $cat8=$_POST['cat8'];
        }
        else{
            $cat8="";
        }



        $servi=$con->execute("SELECT id FROM services WHERE services.nom_service LIKE'%" .$cat6. "%'")->fetchAll('assoc');
        $servR=array();
        for($i=0;$i<count($servi);$i++)
        {
            $servR[$i]=$servi[$i]['id'];
        }
        if($cat4=="Oui")
        {

            $courrierArrivees=$con->execute("SELECT courrier_arrivees.id,courrier_arrivees.etat_du_courrier, courrier_arrivees.expediteur_id,expediteurs.nomComplet_expediteur, courrier_arrivees.service_id, courrier_arrivees.date_arrivee,courrier_arrivees.Désignation,courrier_arrivees.type_courrier,courrier_arrivees.necessité_du_traitement,courrier_arrivees.Priorité,courrier_arrivees.date_limite_du_traitement,courrier_arrivees.courrier_retourne FROM courrier_arrivees JOIN expediteurs ON courrier_arrivees.expediteur_id= expediteurs.id WHERE courrier_arrivees.id LIKE '%" .$cat1. "%' AND courrier_arrivees.date_arrivee LIKE '%" .$cat2. "%' AND courrier_arrivees.Désignation LIKE '%" .$cat3. "%' AND courrier_arrivees.necessité_du_traitement LIKE '%" .$cat4. "%' AND courrier_arrivees.type_courrier LIKE '%" .$cat5. "%' AND courrier_arrivees.service_id IN(SELECT services.id FROM services WHERE services.nom_service LIKE'%" .$cat6. "%') AND courrier_arrivees.Priorité LIKE '%" .$cat7. "%' AND courrier_arrivees.etat_du_courrier LIKE '%" .$cat8. "%' AND courrier_arrivees.expediteur_id IN(SELECT expediteurs.id FROM expediteurs WHERE expediteurs.nomComplet_expediteur LIKE'%" .$cat9. "%') AND courrier_arrivees.date_limite_du_traitement LIKE '%" .$cat10. "%' AND courrier_arrivees.courrier_retourne LIKE '%" .$cat11. "%'")->fetchAll('assoc');
        }
        elseif($cat4=="Non")
        {

            $courrierArrivees=$con->execute("SELECT courrier_arrivees.id,courrier_arrivees.etat_du_courrier, courrier_arrivees.expediteur_id,expediteurs.nomComplet_expediteur, courrier_arrivees.service_id, courrier_arrivees.date_arrivee,courrier_arrivees.Désignation,courrier_arrivees.type_courrier,courrier_arrivees.necessité_du_traitement,courrier_arrivees.Priorité,courrier_arrivees.date_limite_du_traitement,courrier_arrivees.retourne FROM courrier_arrivees JOIN expediteurs ON courrier_arrivees.expediteur_id= expediteurs.id WHERE courrier_arrivees.id LIKE '%" .$cat1. "%' AND courrier_arrivees.date_arrivee LIKE '%" .$cat2. "%' AND courrier_arrivees.Désignation LIKE '%" .$cat3. "%' AND courrier_arrivees.necessité_du_traitement LIKE '%" .$cat4. "%' AND courrier_arrivees.type_courrier LIKE '%" .$cat5. "%'  AND courrier_arrivees.expediteur_id IN(SELECT expediteurs.id FROM expediteurs WHERE expediteurs.nomComplet_expediteur LIKE'%" .$cat9. "%') AND courrier_arrivees.courrier_retourne LIKE '%" .$cat11. "%' ")->fetchAll('assoc');
        }
        else{
            if($cat6!=NULL)
            {
                if($cat10 == NULL)
                {
                    $courrierArrivees=$con->execute("SELECT courrier_arrivees.id,courrier_arrivees.etat_du_courrier, courrier_arrivees.expediteur_id,expediteurs.nomComplet_expediteur, courrier_arrivees.service_id, courrier_arrivees.date_arrivee,courrier_arrivees.Désignation,courrier_arrivees.type_courrier,courrier_arrivees.necessité_du_traitement,courrier_arrivees.Priorité,courrier_arrivees.date_limite_du_traitement,courrier_arrivees.courrier_retourne FROM courrier_arrivees JOIN expediteurs ON courrier_arrivees.expediteur_id= expediteurs.id WHERE courrier_arrivees.id LIKE '%" .$cat1. "%' AND courrier_arrivees.date_arrivee LIKE '%" .$cat2. "%' AND courrier_arrivees.Désignation LIKE '%" .$cat3. "%' AND courrier_arrivees.necessité_du_traitement LIKE '%" .$cat4. "%' AND courrier_arrivees.type_courrier LIKE '%" .$cat5. "%'  AND courrier_arrivees.expediteur_id IN(SELECT expediteurs.id FROM expediteurs WHERE expediteurs.nomComplet_expediteur LIKE'%" .$cat9. "%') AND courrier_arrivees.service_id IN(SELECT services.id FROM services WHERE services.nom_service LIKE'%" .$cat6. "%') AND courrier_arrivees.courrier_retourne LIKE '%" .$cat11. "%'")->fetchAll('assoc');
                }
                else
                {
                    $courrierArrivees=$con->execute("SELECT courrier_arrivees.id,courrier_arrivees.etat_du_courrier, courrier_arrivees.expediteur_id,expediteurs.nomComplet_expediteur, courrier_arrivees.service_id, courrier_arrivees.date_arrivee,courrier_arrivees.Désignation,courrier_arrivees.type_courrier,courrier_arrivees.necessité_du_traitement,courrier_arrivees.Priorité,courrier_arrivees.date_limite_du_traitement,courrier_arrivees.courrier_retourne FROM courrier_arrivees JOIN expediteurs ON courrier_arrivees.expediteur_id= expediteurs.id WHERE courrier_arrivees.id LIKE '%" .$cat1. "%' AND courrier_arrivees.date_arrivee LIKE '%" .$cat2. "%' AND courrier_arrivees.Désignation LIKE '%" .$cat3. "%' AND courrier_arrivees.necessité_du_traitement LIKE '%" .$cat4. "%' AND courrier_arrivees.type_courrier LIKE '%" .$cat5. "%'  AND courrier_arrivees.expediteur_id IN(SELECT expediteurs.id FROM expediteurs WHERE expediteurs.nomComplet_expediteur LIKE'%" .$cat9. "%') AND courrier_arrivees.service_id IN(SELECT services.id FROM services WHERE services.nom_service LIKE'%" .$cat6. "%') AND courrier_arrivees.date_limite_du_traitement LIKE '%" .$cat10. "%' AND courrier_arrivees.courrier_retourne LIKE '%" .$cat11. "%'")->fetchAll('assoc');
                }
            }
            else
            {
                if($cat10== NULL)
                {
                    $courrierArrivees=$con->execute("SELECT courrier_arrivees.id,courrier_arrivees.etat_du_courrier, courrier_arrivees.expediteur_id,expediteurs.nomComplet_expediteur, courrier_arrivees.service_id, courrier_arrivees.date_arrivee,courrier_arrivees.Désignation,courrier_arrivees.type_courrier,courrier_arrivees.necessité_du_traitement,courrier_arrivees.Priorité,courrier_arrivees.date_limite_du_traitement,courrier_arrivees.courrier_retourne FROM courrier_arrivees JOIN expediteurs ON courrier_arrivees.expediteur_id= expediteurs.id WHERE courrier_arrivees.id LIKE '%" .$cat1. "%' AND courrier_arrivees.date_arrivee LIKE '%" .$cat2. "%' AND courrier_arrivees.Désignation LIKE '%" .$cat3. "%' AND courrier_arrivees.necessité_du_traitement LIKE '%" .$cat4. "%' AND courrier_arrivees.type_courrier LIKE '%" .$cat5. "%'  AND courrier_arrivees.expediteur_id IN(SELECT expediteurs.id FROM expediteurs WHERE expediteurs.nomComplet_expediteur LIKE'%" .$cat9. "%') AND courrier_arrivees.Priorité LIKE '%" .$cat7. "%' AND courrier_arrivees.etat_du_courrier LIKE '%" .$cat8. "%' AND courrier_arrivees.courrier_retourne LIKE '%" .$cat11. "%'")->fetchAll('assoc');
                }
                else
                {
                    $courrierArrivees=$con->execute("SELECT courrier_arrivees.id,courrier_arrivees.etat_du_courrier, courrier_arrivees.expediteur_id,expediteurs.nomComplet_expediteur, courrier_arrivees.service_id, courrier_arrivees.date_arrivee,courrier_arrivees.Désignation,courrier_arrivees.type_courrier,courrier_arrivees.necessité_du_traitement,courrier_arrivees.Priorité,courrier_arrivees.date_limite_du_traitement,courrier_arrivees.courrier_retourne FROM courrier_arrivees JOIN expediteurs ON courrier_arrivees.expediteur_id= expediteurs.id WHERE courrier_arrivees.id LIKE '%" .$cat1. "%' AND courrier_arrivees.date_arrivee LIKE '%" .$cat2. "%' AND courrier_arrivees.Désignation LIKE '%" .$cat3. "%' AND courrier_arrivees.necessité_du_traitement LIKE '%" .$cat4. "%' AND courrier_arrivees.type_courrier LIKE '%" .$cat5. "%'  AND courrier_arrivees.expediteur_id IN(SELECT expediteurs.id FROM expediteurs WHERE expediteurs.nomComplet_expediteur LIKE'%" .$cat9. "%') AND courrier_arrivees.Priorité LIKE '%" .$cat7. "%' AND courrier_arrivees.etat_du_courrier LIKE '%" .$cat8. "%' AND courrier_arrivees.date_limite_du_traitement LIKE '%" .$cat10. "%' AND courrier_arrivees.courrier_retourne LIKE '%" .$cat11. "%'")->fetchAll('assoc');
                }
            }
        }


        $this->set('courrierArrivees',$courrierArrivees);
        $this->render('/Espaces/respobureauordres/filterArrivee');
    }

    public function filterArrivee($limit=100)
    {

        $this->updateEtat2();

        $connection=ConnectionManager::get('default');
        $CourrierArrivees=TableRegistry::get('CourrierArrivees');
        $Expediteurs=TableRegistry::get('Expediteurs');
        //$CourrierArrivees=TableRegistry::get('CourrierArrivees');
        $this->paginate = ['contain' => ['Expediteurs','Services']];
        // $courrierArrivees = $this->paginate($CourrierArrivees);
        $courrierArrivee = $connection->execute('SELECT courrier_arrivees.courrier,courrier_arrivees.accuse,courrier_arrivees.id,courrier_arrivees.etat_du_courrier, courrier_arrivees.expediteur_id,expediteurs.nomComplet_expediteur, courrier_arrivees.service_id, courrier_arrivees.date_arrivee,courrier_arrivees.Désignation,courrier_arrivees.type_courrier,courrier_arrivees.necessité_du_traitement,courrier_arrivees.Priorité,courrier_arrivees.date_limite_du_traitement,courrier_arrivees.courrier_retourne FROM courrier_arrivees
            JOIN expediteurs ON courrier_arrivees.expediteur_id= expediteurs.id

            ')->fetchAll('assoc');
        $courrierArrivees=$courrierArrivee;
        $service=$connection->execute('SELECT services.nom_service, services.id FROM services 
            JOIN courrier_arrivees ON services.id = courrier_arrivees.service_id
            ')->fetchAll('assoc');
        $servivces=array();
        for($i=0;$i<count($service);$i++)
        {
            $a=$service[$i]['id'];
            $services[$a]=$service[$i]['nom_service'];
        }





        $this->set('courrierArrivee',$courrierArrivee);
        $this->set('courrierArrivees',$courrierArrivees);

        $this->set('services',$services);

        $CourrierArrivees =TableRegistry::get('CourrierArrivees');
        $con= ConnectionManager::get('default');

        for($i=1;$i<12;$i++)
        {
            if(!empty($_POST['cat'.$i.'']))
            {
                $catt[$i]=$_POST['cat'.$i.''];
            }
            else{
                $catt[$i]=NULL;
            }
        }



        $cat1=$catt[1];
        $cat2=$catt[2];
        $cat3=$catt[3];
        $cat4=$catt[4];
        $cat5=$catt[5];
        $cat6=$catt[6];
        $cat9=$catt[9];

        $cat10=$catt[10];
        $cat11=$catt[11];
        if(!empty($_POST['cat7']))
        {
            $cat7=$_POST['cat7'];
        }
        else{
            $cat7="";
        }
        if(!empty($_POST['cat8']))
        {
            $cat8=$_POST['cat8'];
        }
        else{
            $cat8="";
        }



        $servi=$con->execute("SELECT id FROM services WHERE services.nom_service LIKE'%" .$cat6. "%'")->fetchAll('assoc');
        $servR=array();
        for($i=0;$i<count($servi);$i++)
        {
            $servR[$i]=$servi[$i]['id'];
        }
        if($cat4=="Oui")
        {

            $courrierArrivees=$con->execute("SELECT courrier_arrivees.id,courrier_arrivees.etat_du_courrier, courrier_arrivees.expediteur_id,expediteurs.nomComplet_expediteur, courrier_arrivees.service_id, courrier_arrivees.date_arrivee,courrier_arrivees.Désignation,courrier_arrivees.type_courrier,courrier_arrivees.necessité_du_traitement,courrier_arrivees.Priorité,courrier_arrivees.date_limite_du_traitement,courrier_arrivees.courrier_retourne FROM courrier_arrivees JOIN expediteurs ON courrier_arrivees.expediteur_id= expediteurs.id WHERE courrier_arrivees.id LIKE '%" .$cat1. "%' AND courrier_arrivees.date_arrivee LIKE '%" .$cat2. "%' AND courrier_arrivees.Désignation LIKE '%" .$cat3. "%' AND courrier_arrivees.necessité_du_traitement LIKE '%" .$cat4. "%' AND courrier_arrivees.type_courrier LIKE '%" .$cat5. "%' AND courrier_arrivees.service_id IN(SELECT services.id FROM services WHERE services.nom_service LIKE'%" .$cat6. "%') AND courrier_arrivees.Priorité LIKE '%" .$cat7. "%' AND courrier_arrivees.etat_du_courrier LIKE '%" .$cat8. "%' AND courrier_arrivees.expediteur_id IN(SELECT expediteurs.id FROM expediteurs WHERE expediteurs.nomComplet_expediteur LIKE'%" .$cat9. "%') AND courrier_arrivees.date_limite_du_traitement LIKE '%" .$cat10. "%' AND courrier_arrivees.courrier_retourne LIKE '%" .$cat11. "%'")->fetchAll('assoc');
        }
        elseif($cat4=="Non")
        {

            $courrierArrivees=$con->execute("SELECT courrier_arrivees.id,courrier_arrivees.etat_du_courrier, courrier_arrivees.expediteur_id,expediteurs.nomComplet_expediteur, courrier_arrivees.service_id, courrier_arrivees.date_arrivee,courrier_arrivees.Désignation,courrier_arrivees.type_courrier,courrier_arrivees.necessité_du_traitement,courrier_arrivees.Priorité,courrier_arrivees.date_limite_du_traitement,courrier_arrivees.courrier_retourne FROM courrier_arrivees JOIN expediteurs ON courrier_arrivees.expediteur_id= expediteurs.id WHERE courrier_arrivees.id LIKE '%" .$cat1. "%' AND courrier_arrivees.date_arrivee LIKE '%" .$cat2. "%' AND courrier_arrivees.Désignation LIKE '%" .$cat3. "%' AND courrier_arrivees.necessité_du_traitement LIKE '%" .$cat4. "%' AND courrier_arrivees.type_courrier LIKE '%" .$cat5. "%'  AND courrier_arrivees.expediteur_id IN(SELECT expediteurs.id FROM expediteurs WHERE expediteurs.nomComplet_expediteur LIKE'%" .$cat9. "%') AND courrier_arrivees.courrier_retourne LIKE '%" .$cat11. "%'")->fetchAll('assoc');
        }
        else{
            if($cat6!=NULL)
            {
                if($cat10 == NULL)
                {
                    $courrierArrivees=$con->execute("SELECT courrier_arrivees.id,courrier_arrivees.etat_du_courrier, courrier_arrivees.expediteur_id,expediteurs.nomComplet_expediteur, courrier_arrivees.service_id, courrier_arrivees.date_arrivee,courrier_arrivees.Désignation,courrier_arrivees.type_courrier,courrier_arrivees.necessité_du_traitement,courrier_arrivees.Priorité,courrier_arrivees.date_limite_du_traitement,courrier_arrivees.courrier_retourne FROM courrier_arrivees JOIN expediteurs ON courrier_arrivees.expediteur_id= expediteurs.id WHERE courrier_arrivees.id LIKE '%" .$cat1. "%' AND courrier_arrivees.date_arrivee LIKE '%" .$cat2. "%' AND courrier_arrivees.Désignation LIKE '%" .$cat3. "%' AND courrier_arrivees.necessité_du_traitement LIKE '%" .$cat4. "%' AND courrier_arrivees.type_courrier LIKE '%" .$cat5. "%'  AND courrier_arrivees.expediteur_id IN(SELECT expediteurs.id FROM expediteurs WHERE expediteurs.nomComplet_expediteur LIKE'%" .$cat9. "%') AND courrier_arrivees.service_id IN(SELECT services.id FROM services WHERE services.nom_service LIKE'%" .$cat6. "%') AND courrier_arrivees.courrier_retourne LIKE '%" .$cat11. "%'")->fetchAll('assoc');
                }
                else
                {
                    $courrierArrivees=$con->execute("SELECT courrier_arrivees.id,courrier_arrivees.etat_du_courrier, courrier_arrivees.expediteur_id,expediteurs.nomComplet_expediteur, courrier_arrivees.service_id, courrier_arrivees.date_arrivee,courrier_arrivees.Désignation,courrier_arrivees.type_courrier,courrier_arrivees.necessité_du_traitement,courrier_arrivees.Priorité,courrier_arrivees.date_limite_du_traitement,courrier_arrivees.courrier_retourne FROM courrier_arrivees JOIN expediteurs ON courrier_arrivees.expediteur_id= expediteurs.id WHERE courrier_arrivees.id LIKE '%" .$cat1. "%' AND courrier_arrivees.date_arrivee LIKE '%" .$cat2. "%' AND courrier_arrivees.Désignation LIKE '%" .$cat3. "%' AND courrier_arrivees.necessité_du_traitement LIKE '%" .$cat4. "%' AND courrier_arrivees.type_courrier LIKE '%" .$cat5. "%'  AND courrier_arrivees.expediteur_id IN(SELECT expediteurs.id FROM expediteurs WHERE expediteurs.nomComplet_expediteur LIKE'%" .$cat9. "%') AND courrier_arrivees.service_id IN(SELECT services.id FROM services WHERE services.nom_service LIKE'%" .$cat6. "%') AND courrier_arrivees.date_limite_du_traitement LIKE '%" .$cat10. "%' AND courrier_arrivees.courrier_retourne LIKE '%" .$cat11. "%'")->fetchAll('assoc');
                }
            }
            else
            {
                if($cat10== NULL)
                {
                    $courrierArrivees=$con->execute("SELECT courrier_arrivees.id,courrier_arrivees.etat_du_courrier, courrier_arrivees.expediteur_id,expediteurs.nomComplet_expediteur, courrier_arrivees.service_id, courrier_arrivees.date_arrivee,courrier_arrivees.Désignation,courrier_arrivees.type_courrier,courrier_arrivees.necessité_du_traitement,courrier_arrivees.Priorité,courrier_arrivees.date_limite_du_traitement,courrier_arrivees.courrier_retourne FROM courrier_arrivees JOIN expediteurs ON courrier_arrivees.expediteur_id= expediteurs.id WHERE courrier_arrivees.id LIKE '%" .$cat1. "%' AND courrier_arrivees.date_arrivee LIKE '%" .$cat2. "%' AND courrier_arrivees.Désignation LIKE '%" .$cat3. "%' AND courrier_arrivees.necessité_du_traitement LIKE '%" .$cat4. "%' AND courrier_arrivees.type_courrier LIKE '%" .$cat5. "%'  AND courrier_arrivees.expediteur_id IN(SELECT expediteurs.id FROM expediteurs WHERE expediteurs.nomComplet_expediteur LIKE'%" .$cat9. "%') AND courrier_arrivees.Priorité LIKE '%" .$cat7. "%' AND courrier_arrivees.etat_du_courrier LIKE '%" .$cat8. "%' AND courrier_arrivees.courrier_retourne LIKE '%" .$cat11. "%'")->fetchAll('assoc');
                }
                else
                {
                    $courrierArrivees=$con->execute("SELECT courrier_arrivees.id,courrier_arrivees.etat_du_courrier, courrier_arrivees.expediteur_id,expediteurs.nomComplet_expediteur, courrier_arrivees.service_id, courrier_arrivees.date_arrivee,courrier_arrivees.Désignation,courrier_arrivees.type_courrier,courrier_arrivees.necessité_du_traitement,courrier_arrivees.Priorité,courrier_arrivees.date_limite_du_traitement,courrier_arrivees.courrier_retourne FROM courrier_arrivees JOIN expediteurs ON courrier_arrivees.expediteur_id= expediteurs.id WHERE courrier_arrivees.id LIKE '%" .$cat1. "%' AND courrier_arrivees.date_arrivee LIKE '%" .$cat2. "%' AND courrier_arrivees.Désignation LIKE '%" .$cat3. "%' AND courrier_arrivees.necessité_du_traitement LIKE '%" .$cat4. "%' AND courrier_arrivees.type_courrier LIKE '%" .$cat5. "%'  AND courrier_arrivees.expediteur_id IN(SELECT expediteurs.id FROM expediteurs WHERE expediteurs.nomComplet_expediteur LIKE'%" .$cat9. "%') AND courrier_arrivees.Priorité LIKE '%" .$cat7. "%' AND courrier_arrivees.etat_du_courrier LIKE '%" .$cat8. "%' AND courrier_arrivees.date_limite_du_traitement LIKE '%" .$cat10. "%' AND courrier_arrivees.courrier_retourne LIKE '%" .$cat11. "%' ")->fetchAll('assoc');
                }
            }

        }
        $this->set('courrierArrivees',$courrierArrivees);
        $this->render('/Espaces/respobureauordres/filterArrivee');
    }

public function downloadAcc($id = null){

        $connexion=ConnectionManager::get('default');
        $CourrierArrivees=TableRegistry::get('CourrierArrivees');
        $courrierArrivee = $CourrierArrivees->get($id);
        $titre2=$connexion->execute("SELECT id FROM courrier_arrivees where id=$id ")->fetchAll('assoc');
        $titre22=$titre2['0']['id'];
        $attachementPath2 =WWW_ROOT .'courrier'.DS.$titre22.'accuse.pdf';
        $this->response->file($attachementPath2, array(
            'download' => true,
            'name' => $titre22.'accuse.pdf',
        ));
        return $this->response;

    }

    public function download($id = null){



        $connexion=ConnectionManager::get('default');
        $CourrierArrivees=TableRegistry::get('CourrierArrivees');
        $courrierArrivee = $CourrierArrivees->get($id);
        $titre=$connexion->execute("SELECT id FROM courrier_arrivees where id=$id ")->fetchAll('assoc');
        $titre11=$titre['0']['id'];
        $attachementPath = WWW_ROOT . 'courrier'.DS. $titre11.'.pdf';

        $this->response->file($attachementPath, array(
            'download' => true,
            'name' => $titre11.'.pdf',
        ));
        return $this->response;
    }

    public function downloadAcc($id = null){

        $connexion=ConnectionManager::get('default');
        $CourrierArrivees=TableRegistry::get('CourrierArrivees');
        $courrierArrivee = $CourrierArrivees->get($id);
        $titre2=$connexion->execute("SELECT id FROM courrier_arrivees where id=$id ")->fetchAll('assoc');
        $titre22=$titre2['0']['id'];
        $attachementPath2 =WWW_ROOT .'courrier'.DS.$titre22.'accuse.pdf';
        $this->response->file($attachementPath2, array(
            'download' => true,
            'name' => $titre22.'accuse.pdf',
        ));
        return $this->response;

    }


/****** Bouhsise *****/

//DEBUT IBTISSAM +kawtar
    public function demanderabsences()
    {
        $_SESSION['auto'] = "none";
        $user_id = $this->Auth->user('id');
        $con=ConnectionManager::get('default');

        $id = $con->execute("SELECT id FROM fonctionnaires WHERE user_id = $user_id")->fetchAll('assoc');
        //debug($id); die;
        $fonct_id = $id[0]['id'];

        $nbr = $con->execute("SELECT COUNT(*) as n FROM absences WHERE fonctionnaire_id = $fonct_id")->fetchAll('assoc');
        $duree = $con->execute("SELECT duree_ab FROM absences WHERE fonctionnaire_id = $fonct_id")->fetchAll('assoc');
        $d =0;
        for ($i=0; $i < $nbr[0]['n']; $i++)
        {
            $d += $duree[$i]['duree_ab'];
        }

        if(isset($_POST['submit']))
        {

            $duree_ab = $_POST['duree_ab'];
            $cause = $_POST['cause'];
            $date_ab = $_POST['date'];

            if (empty($_POST['time']))
            {
                $time_ab = 0;

            }
            else
            {
                $time_ab = $_POST['time'];
            }

            if($d>'13')
            {
                $_SESSION['auto'] ="no";
            }
            else
            {
                $_SESSION['auto'] ="yes";
                $con->execute("INSERT INTO absences (fonctionnaire_id,duree_ab,cause,date_ab,time_ab) VALUES ($fonct_id,$duree_ab,'$cause','$date_ab','$time_ab')");

            } }

        $this->render('/Espaces/respobureauordres/demanderabsences');
    }
    public function demanderDocFct()
    {
        $ProfpermanentsDocuments=TableRegistry::get('FonctionnairesDocuments');
        $documentsProfesseur = $ProfpermanentsDocuments->newEntity();
        $documentbis=TableRegistry::get('Documents');
        $documentbis=$documentbis->find('all');
        $profbis=TableRegistry::get('Fonctionnaires');
        $profbis=$profbis->find('all');
        $idUser=$this->Auth->user('id');
        $profpermanents=TableRegistry::get('Fonctionnaires');
        $query=$profpermanents->find('all')->select('id')->where(['user_id'=>$idUser]);

        foreach($query as $ligne)
        {
            $usrid=$ligne->id;
        }

        if ($this->request->is('post')){

            $documentsProfesseur->fonctionnaire_id =$usrid;
            $documentsProfesseur->document_id =$this->request->data('nomDoc');
            //requete pour une demande déja effectué
            $requete = $ProfpermanentsDocuments->find('all',array('conditions' => array('FonctionnairesDocuments.fonctionnaire_id' => $usrid
            ,   'FonctionnairesDocuments.document_id' => $this->request->data('nomDoc'))));
            $nombre=0;
            foreach($requete as $resultat)
            {
                if($resultat->etatdemande=='Demande envoyé' or $resultat->etatdemande=='Prete' or $resultat->etatdemande=='En cours de traitement')
                {
                    $nombre++;
                }
            }

            $Profpermanents=TableRegistry::get('Fonctionnaires');
            $identifiantDoc=$this->request->data('nomDoc');

            switch($identifiantDoc)
            {
                case 1:
                {
                    $nbtentativebis=$Profpermanents->find('all')->select('etat_attestation')->where(['id'=>$usrid]);
                    foreach ($nbtentativebis as $value) {
                        $nombrebis=$value->etat_attestation;

                    }

                    // if($nombrebis>3)
                    // {
                    //     $this->Flash->error(__('Vous avez dépassé le nombre maximum des attestations , pour plus d\'infos veuillez nous conatcter au service'));
                    //     break;
                    // }

                    // else
                    if($nombre>=1)
                    {
                        $this->Flash->error(__('Echéc d\'envoi ... Déja vous avez '.$nombre.' demande(s) dans le service, veuillez attender Svp'));
                        break;
                    }
                    elseif($ProfpermanentsDocuments->save($documentsProfesseur)) {
                        $nombrebis++;
                        $query=$profpermanents->find('all')->update()->set(['etat_attestation' => $nombrebis])->where(['id' => $usrid]);
                        $query->execute();

                        $this->Flash->success(__('Demande envoyée.'));

                        return $this->redirect(['controller'=>'Respobureauordres','action' => 'index']);
                    }
                    else{
                        $this->Flash->error(__('Demande échouée'));

                    }



                    break;
                }
                case 2:
                {
                    // debug($usrid);
                    $nbtentativebis=$profpermanents->find('all')->select('etat_fiche')->where(['id'=>$usrid]);
                    foreach ($nbtentativebis as $value) {
                        $nombrebis=$value->etat_attestation;

                    }
                    $nombrebis=count($nbtentativebis);
                    // if($nombrebis>3)
                    // {
                    //     $this->Flash->error(__('Vous avez dépassé le nombre maximum des fiches de salaire, pour plus d\'infos veuillez nous conatcter au service'));
                    //     break;
                    // }
                    // else
                    if($nombre>=1)
                    {
                        $this->Flash->error(__('Echec d\'envoi ... Déja vous avez '.$nombre.'  demande(s) dans le service , veuillez attender Svp'));
                    }
                    elseif ($ProfpermanentsDocuments->save($documentsProfesseur)) {
                        $nombrebis++;
                        $query=$profpermanents->find('all')->update()->set(['etat_fiche' => $nombrebis])->where(['id' => $usrid]);
                        $query->execute();
                        $this->Flash->success(__('Demande envoyée.'));

                        return $this->redirect(['controller'=>'Respobureauordres','action' => 'index']);
                    }
                    else{
                        $this->Flash->error(__('Demande echouée'));

                    }

                }
            }


        }

        $profpermanents = $ProfpermanentsDocuments->fonctionnaires->find('list', ['limit' => 200]);
        $documents = $ProfpermanentsDocuments->Documents->find('list', ['limit' => 200]);
        $this->set('doc',$documentbis);
        $this->set('prof',$profbis);
        $this->set(compact('documentsProfesseur', 'profpermanents', 'documents'));
        $this->set('_serialize', ['documentsProfesseur']);
        $this->render('/Espaces/respobureauordres/demanderDocFct');

    }
    public function etatDemandeFct()
    {
        $idUser = $this->Auth->user('id');
        $Foncts = TableRegistry::get('Fonctionnaires');
        $query = $Foncts->find('all')->select('id')->where(['user_id' => $idUser]);
        foreach ($query as $ligne) {
            $ide = $ligne->id;
            break;
        }
        $this->paginate = [
            'contain' => ['Fonctionnaires', 'Documents']
        ];
        $FonctionnairesDocuments = TableRegistry::get('FonctionnairesDocuments');
        $FonctionnairesDocuments = $this->paginate($FonctionnairesDocuments->find("all", array(
                "joins" => array(
                    array(
                        "table" => "Fonctionnaires",
                        "conditions" => array(
                            "FonctionnairesDocuments.fonctionnaire_id = Fonctionnaires.id"
                        )
                    ),
                    array(
                        "table" => "Documents",
                        "conditions" => array(
                            "FonctionnairesDocuments.document_id = Documents.id"
                        )
                    )
                ),
                'conditions' => array(
                    'FonctionnairesDocuments.fonctionnaire_id' => $ide)
            )
        ));
        $this->set(compact('FonctionnairesDocuments'));
        $this->set('_serialize', ['FonctionnairesDocuments']);
        $this->render('/Espaces/respobureauordres/etatDemandeFct');

    }
    // FIN KAWTAR + IBTISSAM
    //Validation de donnees

    public function viewmouna($id = null)
    {
        $this->loadModel('Fonctionnaires');
        $usrole=$this->Auth->user('id');
        $role=$this->Auth->user('role');

        $modif = ConnectionManager::get('default');
        $id = $modif->execute("SELECT id FROM fonctionnaires  WHERE user_id=".$usrole."")->fetchAll('assoc');
        //debug($id);

        $profpermanent = $this->Fonctionnaires->get($id[0]['id'], [
            'contain' => []
        ]);

        $this->set('id',$usrole);
        $this->set('role',$role);
        $this->set('profpermanent', $profpermanent);
        $this->render('/Espaces/fonctionnaires/viewmouna');
    }

public function editmouna()
    {
        $this->loadModel('Fonctionnaires');
        $usrole=$this->Auth->user('id');

        $modif = ConnectionManager::get('default');
        $id = $modif->execute("SELECT id FROM fonctionnaires  WHERE user_id=".$usrole."")->fetchAll('assoc');
        $id=$id[0]['id'];
        $Profpermanent = TableRegistry::get('fonctionnairesbis');
        $profpermanentOriginal = $this->Fonctionnaires->get($id);
        $profpermanent = $this->Fonctionnaires->get($id);
        //debug($profpermanent);

        if ($this->request->is(['patch', 'post', 'put'])) {

            //debug($profpermanentOriginal);
            $profpermanent = $Profpermanent->newEntity();
            //$profpermanent= $Profpermanent->patchEntity($profpermanent, $this->request->data);

            $profpermanent->somme=$this->request->data('somme');
            $profpermanent->user_id=$profpermanentOriginal->user_id;
            $profpermanent->salaire=$profpermanentOriginal->salaire;
            $profpermanent->etat=$this->request->data('etat');
            //debug($this->request->data('date_Recrut'));
            if($profpermanentOriginal->date_Recrut)
               $profpermanent->date_Recrut=$profpermanentOriginal->date_Recrut;
            $profpermanent->nom_fct=$this->request->data('nom_fct');
            $profpermanent->prenom_fct=$this->request->data('prenom_fct');
            $profpermanent->age=$this->request->data('age');
            $profpermanent->specialite=$this->request->data('specialite');
            $profpermanent->situation_Familiale=$this->request->data('situation_Familiale');
            if($profpermanentOriginal->dateNaissance)
             $profpermanent->dateNaissance=$profpermanentOriginal->dateNaissance;
             $profpermanent->etat_attestation=$profpermanentOriginal->etat_attestation;
             $profpermanent->photo=$profpermanentOriginal->photo;
             $profpermanent->etat_fiche=$profpermanentOriginal->etat_fiche;
            $profpermanent->lieuNaissance=$this->request->data('lieuNaissance');
            $profpermanent->CIN=$this->request->data('CIN');
            $profpermanent->email=$this->request->data('email');
            $profpermanent->phone=$this->request->data('phone');
            $profpermanent->genre=$this->request->data('genre');
            $profpermanent->nbr_enfants=$this->request->data('nbr_enfants');
            $profpermanent->isPassExam=$this->request->data('isPassExam');
            //debug($profpermanent);

            //dump($profpermanent);exit;

            if ($Profpermanent->save($profpermanent)) {
                $this->Flash->success(__('Votre demande de modification de données a été envoyée au responsable , veuillez attendre son traitement .
                '));

                //return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The {0} could not be saved. Please, try again.', 'Fonctionnaire'));
            }
        }
        $this->set(compact('profpermanent'));
        $this->render('/Espaces/fonctionnaires/editmouna');

    }
}
?>