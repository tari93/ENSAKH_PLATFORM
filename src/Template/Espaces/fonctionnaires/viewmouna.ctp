
<section class="content-header">
  <h1>
    <?php
    use Cake\I18n\Time;
     echo __('Vos Données');
     ?>
  </h1>
  <ol class="breadcrumb">
    <li>
    <?= $this->Html->link('<i class="fa fa-dashboard"></i> ' . __('Back'), ['action' => 'index'], ['escape' => false])?>
    </li>
  </ol>
</section>
<!--mouna jellouli-->
<!-- Main content -->
<section class="content">
<div class="row">
    <div class="col-md-12">
        <div class="box box-solid">
            <div class="box-header with-border">
                <i class="fa fa-info"></i>
                <h3 class="box-title"><?php echo __('Information'); ?></h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <dl class="dl-horizontal">
                                 <dt><?= __('Nom :') ?></dt>
                                        <dd>
                                            <?= h($profpermanent->nom_fct) ?>
                                        </dd>
                                      <dt><?= __('Prénom :') ?></dt>
                                        <dd>
                                            <?= h($profpermanent->prenom_fct) ?>
                                        </dd>
                                           <dt><?= __(' Numéro de somme  :') ?></dt>
                                        <dd>
                                            <?= h($profpermanent->somme) ?>
                                        </dd>
                                          
                                          <dt><?= __('Numéro de téléphone :') ?></dt>
                                <dd>
                                    <?= h($profpermanent->phone) ?>
                                </dd>
                                  <dt><?= __('Email :') ?></dt>
                                <dd>
                                    <?= h($profpermanent->email) ?>
                                </dd>
                                     
                                          
                                             <dt><?= __('Specialité :') ?></dt>
                                        <dd>
                                            <?= h($profpermanent->specialite) ?>
                                        </dd>
                                              <dt><?= __('Situation Familiale :') ?></dt>
                                        <dd>
                                            <?= h($profpermanent->situation_Familiale) ?>
                                        </dd>
                                            <dt><?= __('Lieu de naissance :') ?></dt>
                                        <dd>
                                            <?= h($profpermanent->lieuNaissance) ?>
                                        </dd>
                        
   
                              <dt><?= __('Age :') ?></dt>
                                <dd>
                                    <?= $this->Number->format($profpermanent->age) ?>
                                </dd>
   
                               
                                                <dt><?= __('CIN :') ?></dt>
                                <dd>
                                    <?= h($profpermanent->CIN) ?>
                                </dd>
                                                                                                
                                        <dt><?= __('Date  de recrutement :') ?></dt>
                                <dd>
                                    <?= Time::parse($profpermanent->date_Recrut)->nice('Europe/Paris', 'fr-FR') ?>
                                </dd>
                                        <dt><?= __('Date de naissance :') ?></dt>
                                <dd>
                                    <?= Time::parse($profpermanent->dateNaissance)->nice('Europe/Paris', 'fr-FR') ?> ?>
                                </dd>
                                        <dt><?= __('Nombre d\'enfants :') ?></dt>
                                <dd>
                                    <?= $this->Number->format($profpermanent->nbr_enfants) ?>
                                </dd>
                                        <dt><?= __('Genre :') ?></dt>
                                <dd>
                                    <?= h($profpermanent->genre) ?>
                                </dd>
                                                                                                    
                                            
                                    
                                    
                                                            </dl>
            </div>
            <!-- /.box-body -->
        </div>
        <div class="col-md-3 pull-middle">
        <form action="<?= $this->Url->build(); ?>" method="post">
            <?php   // user is logged in, show logout..user menu etc
            
            echo $this->Html->link('Modifier vos données', array('controller' => $role.'s', 'action' => 'editmouna'),['class' => 'btn btn-success btn-block']);
            ?>
     <!-- <span>    <input      name="modifier" type="submit" value="modifier vos données"   class="pull-right" class="btn btn-info btn-flat"></span>-->
        </form>
         </div>
        <!-- /.box -->
    </div>
    <!-- ./col -->
</div>
<!-- div -->

</section>
