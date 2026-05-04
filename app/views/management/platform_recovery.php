<?php
/** @var xmlDom $dom */
/** @var \Dom\HTMLElement $target */
/** @var array $undoableEvents */
/** @var array $redoEvents */
/** @var string|null $msg */

$dom->fabricateChild($target, 'h1', [], 'Platform Recovery & Event Log');
$dom->fabricateChild($target, 'p', [], 'View the immutable event log and perform point-in-time recovery.');

if ($msg === 'replay_queued') {
    $dom->fabricateChild($target, 'div', ['class' => 'alert alert-success'], 'Recovery replay has been queued for processing.');
}

// Point-in-Time Recovery Form
$recoveryTools = $dom->fabricateChild($target, 'div', ['class' => 'recovery-tools card']);
$dom->fabricateChild($recoveryTools, 'h2', [], 'Point-in-Time Recovery');

$form = $dom->fabricateChild($recoveryTools, 'form', ['method' => 'POST', 'action' => '/platform_recovery']);
$dom->fabricateChild($form, 'input', ['type' => 'hidden', 'name' => 'action', 'value' => 'replay']);

$formGroup = $dom->fabricateChild($form, 'div', ['class' => 'form-group']);
$dom->fabricateChild($formGroup, 'label', ['for' => 'target_time'], 'Recover to Timestamp:');
$dom->fabricateChild($formGroup, 'input', ['type' => 'datetime-local', 'id' => 'target_time', 'name' => 'target_time', 'class' => 'form-control', 'required' => 'required']);

$dom->fabricateChild($form, 'button', [
    'type' => 'submit',
    'class' => 'btn btn-danger',
    'onclick' => "return confirm('Are you sure? This will overwrite the current database state.');"
], 'Replay Events');

// Undo Last Action
$undoTools = $dom->fabricateChild($target, 'div', ['class' => 'recovery-tools card']);
$dom->fabricateChild($undoTools, 'h2', [], 'Undo Last Action');
$dom->fabricateChild($undoTools, 'p', [], 'Revert the most recent action you performed.');

$hyperlink = new Hyperlink();
$hyperlink->appendHyperlinkForm($dom, $undoTools, 'Undo Action', '/undo', ['mode' => 'undo'], ['btn', 'btn-warning']);
$hyperlink->appendHyperlinkForm($dom, $undoTools, 'Redo Action', '/undo', ['mode' => 'redo'], ['btn', 'btn-success']);

// Shared Table Config
$columns = [
    ['key' => 'id',           'label' => 'ID',            'isAction' => false],
    ['key' => 'aggregate_id', 'label' => 'Aggregate ID',  'isAction' => false],
    ['key' => 'event_type',   'label' => 'Type',          'isAction' => false, 'cssClass' => 'flex-wide',],
    ['key' => 'is_reversal',  'label' => 'Reversal',      'isAction' => false],
    ['key' => 'status',       'label' => 'Status',        'isAction' => false, 'renderCallback' => function($xmlDom, $cell, $rowData) {
        $status = $rowData['status'];
        $class = 'badge badge-' . ($status === 'processed' ? 'success' : ($status === 'failed' ? 'danger' : 'warning'));
        $xmlDom->fabricateChild($cell, 'span', ['class' => $class], $status);
    }],
    ['key' => 'user_id',   'label' => 'User ID',    'isAction' => false],
    ['key' => 'created_at','label' => 'Created At', 'isAction' => false, 'cssClass' => 'flex-wide',],
];

// 1. Undoable History (Current Path)
$dom->fabricateChild($target, 'h2', [], 'Your Undoable History');
$undoTable = new FlexTableComponent($dom);
$undoTable->setNestedKey('_payload_details');
$undoTable->setColumns($columns);
$undoTable->setData($undoableEvents);
$target->append($undoTable->render());

// 2. Redo Stack (Available Forward Path)
$dom->fabricateChild($target, 'h2', [], 'Your Redo Stack');
$redoTable = new FlexTableComponent($dom);
$redoTable->setNestedKey('_payload_details');
$redoTable->setColumns($columns);
$redoTable->setData($redoEvents);
$target->append($redoTable->render());

// 3. Global Event Log
$dom->fabricateChild($target, 'h2', [], 'Recent Events (All Users)');
$flexTable = new FlexTableComponent($dom);
$flexTable->setNestedKey('_payload_details');
$flexTable->setColumns($columns);
$flexTable->setData($events);
$target->append($flexTable->render());
