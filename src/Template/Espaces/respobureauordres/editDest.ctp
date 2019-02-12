<?php
/**
  * @var \App\View\AppView $this
  */
?>
<section class="content-header">
  <h1>
    Destinataire
    <small><?= __('Modifier') ?></small>
  </h1>
  <ol class="breadcrumb">
    <li>
    <?= $this->Html->link('<i class="fa fa-dashboard"></i> '.__('Retour'), ['action' => 'indexDest'], ['escape' => false]) ?>
    </li>
  </ol>
</section>

<!-- Main content -->
<section class="content">
  <div class="row">
    <!-- left column -->
    <div class="col-md-12">
      <!-- general form elements -->
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title"><?= __('Formulaire') ?></h3>
        </div>
        <!-- /.box-header -->
        <!-- form start -->
        <?= $this->Form->create($destinataire, array('role' => 'form')) ?>
          <div class="box-body">

          <?php
            echo $this->Form->input('nomComplet_destinataire');
            echo $this->Form->input('adresse_destinataire');
            echo $this->Form->input('email_destinataire');
            echo $this->Form->input('telephone_destinataire');
            echo $this->Form->input('ville_destinataire');
            echo $this->Form->input('pays_destinataire');
          ?>
          </div>
          <!-- /.box-body -->
          <div class="box-footer">
            <?= $this->Form->button(__('Sauvegarder')) ?>
          </div>
        <?= $this->Form->end() ?>
      </div>
    </div>
  </div>
</section>
