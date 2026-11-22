<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

require_once __DIR__  . '/../../../../core/php/core.inc.php';

class devialet extends eqLogic {
  /*     * *************************Attributs****************************** */

  public static $_widgetPossibility = array('custom' => true, 'custom::layout' => false);

  /*     * ***********************Methode static*************************** */

  public static function cron() {
    foreach (self::byType('devialet', true) as $eqLogic) {
      try {
        $autorefresh = $eqLogic->getConfiguration('autorefresh', '');
        if ($autorefresh == '') {
          continue;
        }
        if (!self::isCronDue($autorefresh)) {
          continue;
        }
        $eqLogic->refreshInfo();
      } catch (Exception $e) {
        log::add('devialet', 'error', __('Erreur cron pour', __FILE__) . ' ' . $eqLogic->getHumanName() . ' : ' . $e->getMessage());
      }
    }
  }

  public static function isCronDue($cronExpression) {
    try {
      $c = new Cron\CronExpression(checkAndFixCron($cronExpression), new Cron\FieldFactory);
      return $c->isDue();
    } catch (Exception $e) {
      return false;
    }
  }

  /*     * *********************Méthodes d'instance************************* */

  public function preInsert() {
    if ($this->getCategory('multimedia', 1) == 0) {
      $this->setCategory('multimedia', 1);
    }
  }

  public function preSave() {
    $ip = trim($this->getConfiguration('ip', ''));
    if ($ip == '') {
      throw new Exception(__('L\'adresse IP de l\'enceinte Devialet est obligatoire', __FILE__));
    }
    $namespace = $this->getConfiguration('namespace', 'systems');
    if (!in_array($namespace, array('systems', 'devices', 'groups'))) {
      $this->setConfiguration('namespace', 'systems');
    }
    if ($this->getConfiguration('autorefresh', '') === '') {
      $default = config::byKey('defaultAutorefresh', 'devialet', '*/5 * * * *');
      $this->setConfiguration('autorefresh', $default);
    }
  }

  public function postSave() {
    $this->createOrUpdateCmd('refresh', 'action', 'other', __('Rafraîchir', __FILE__), 1, '1', '0', array(), 'fas fa-sync');
    $this->createOrUpdateCmd('online', 'info', 'binary', __('En ligne', __FILE__), 0, '1', '1', array('generic_type' => 'GENERIC_INFO'));
    $this->createOrUpdateCmd('volume', 'info', 'numeric', __('Volume', __FILE__), 0, '1', '1', array('unit' => '%', 'minValue' => 0, 'maxValue' => 100, 'generic_type' => 'VOLUME'));
    $this->createOrUpdateCmd('volume_set', 'action', 'slider', __('Régler volume', __FILE__), 1, '1', '0', array('value' => '#volume_id#', 'minValue' => 0, 'maxValue' => 100, 'generic_type' => 'SET_VOLUME'));
    $this->createOrUpdateCmd('volume_up', 'action', 'other', __('Volume +', __FILE__), 1, '1', '0', array(), 'fas fa-volume-up');
    $this->createOrUpdateCmd('volume_down', 'action', 'other', __('Volume -', __FILE__), 1, '1', '0', array(), 'fas fa-volume-down');
    $this->createOrUpdateCmd('mute_state', 'info', 'binary', __('Sourdine', __FILE__), 0, '1', '1', array('generic_type' => 'MEDIA_MUTE_STATE'));
    $this->createOrUpdateCmd('mute', 'action', 'other', __('Muet', __FILE__), 1, '1', '0', array('value' => '#mute_state_id#'), 'fas fa-volume-mute');
    $this->createOrUpdateCmd('unmute', 'action', 'other', __('Son', __FILE__), 1, '1', '0', array('value' => '#mute_state_id#'), 'fas fa-volume-off');
    $this->createOrUpdateCmd('playing_state', 'info', 'string', __('Etat lecture', __FILE__), 0, '1', '1', array());
    $this->createOrUpdateCmd('play', 'action', 'other', __('Lecture', __FILE__), 1, '1', '0', array(), 'fas fa-play');
    $this->createOrUpdateCmd('pause', 'action', 'other', __('Pause', __FILE__), 1, '1', '0', array(), 'fas fa-pause');
    $this->createOrUpdateCmd('next', 'action', 'other', __('Suivant', __FILE__), 1, '1', '0', array(), 'fas fa-step-forward');
    $this->createOrUpdateCmd('previous', 'action', 'other', __('Précédent', __FILE__), 1, '1', '0', array(), 'fas fa-step-backward');
    $this->createOrUpdateCmd('source', 'info', 'string', __('Source', __FILE__), 0, '1', '1', array());
    $this->createOrUpdateCmd('select_source', 'action', 'select', __('Sélectionner source', __FILE__), 1, '1', '0', array('value' => '#source_id#', 'listValue' => ''));
    $this->createOrUpdateCmd('night_mode', 'info', 'binary', __('Mode nuit', __FILE__), 0, '0', '1', array());
    $this->createOrUpdateCmd('night_mode_on', 'action', 'other', __('Mode nuit ON', __FILE__), 0, '1', '0', array(), 'fas fa-moon');
    $this->createOrUpdateCmd('night_mode_off', 'action', 'other', __('Mode nuit OFF', __FILE__), 0, '1', '0', array(), 'fas fa-sun');
    $this->createOrUpdateCmd('power_off', 'action', 'other', __('Eteindre', __FILE__), 0, '1', '0', array(), 'fas fa-power-off');

    $this->linkCmds();
    $refresh = $this->getCmd(null, 'refresh');
    if (is_object($refresh)) {
      try {
        $refresh->execCmd();
      } catch (Exception $e) {
        log::add('devialet', 'debug', 'Refresh initial impossible: ' . $e->getMessage());
      }
    }
  }

  private function linkCmds() {
    $volumeId = '';
    $volumeCmd = $this->getCmd(null, 'volume');
    if (is_object($volumeCmd)) {
      $volumeId = '#' . $volumeCmd->getId() . '#';
    }
    $muteId = '';
    $muteCmd = $this->getCmd(null, 'mute_state');
    if (is_object($muteCmd)) {
      $muteId = '#' . $muteCmd->getId() . '#';
    }
    $sourceId = '';
    $sourceCmd = $this->getCmd(null, 'source');
    if (is_object($sourceCmd)) {
      $sourceId = '#' . $sourceCmd->getId() . '#';
    }
    $setVolume = $this->getCmd(null, 'volume_set');
    if (is_object($setVolume) && $volumeId != '') {
      $setVolume->setValue($volumeCmd->getId());
      $setVolume->save();
    }
    foreach (array('mute', 'unmute') as $logicalId) {
      $cmd = $this->getCmd(null, $logicalId);
      if (is_object($cmd) && is_object($muteCmd)) {
        $cmd->setValue($muteCmd->getId());
        $cmd->save();
      }
    }
    $selectSource = $this->getCmd(null, 'select_source');
    if (is_object($selectSource) && is_object($sourceCmd)) {
      $selectSource->setValue($sourceCmd->getId());
      $selectSource->save();
    }
  }

  private function createOrUpdateCmd($logicalId, $type, $subType, $name, $isVisible, $isEnabledOnSave, $isHistorized, $configuration = array(), $icon = '') {
    $cmd = $this->getCmd(null, $logicalId);
    if (!is_object($cmd)) {
      $cmd = new devialetCmd();
      $cmd->setLogicalId($logicalId);
      $cmd->setEqLogic_id($this->getId());
      $cmd->setName($name);
      $cmd->setIsVisible($isVisible);
      $cmd->setIsHistorized($isHistorized);
    }
    $cmd->setType($type);
    $cmd->setSubType($subType);
    if ($icon != '') {
      $cmd->setDisplay('icon', '<i class="' . $icon . '"></i>');
    }
    foreach ($configuration as $key => $value) {
      if ($key === 'value' && strpos($value, '#') !== false) {
        continue;
      }
      $cmd->setConfiguration($key, $value);
    }
    if (isset($configuration['unit'])) {
      $cmd->setUnite($configuration['unit']);
    }
    $cmd->save();
    return $cmd;
  }

  public function getApiBaseUrl() {
    $ip = trim($this->getConfiguration('ip', ''));
    $namespace = $this->getConfiguration('namespace', 'systems');
    if (!in_array($namespace, array('systems', 'devices', 'groups'))) {
      $namespace = 'systems';
    }
    return 'http://' . $ip . '/ipcontrol/v1/' . $namespace . '/current';
  }

  public function callDevialetApi($path, $method = 'GET', $data = null, $timeout = 5) {
    $ip = trim($this->getConfiguration('ip', ''));
    if ($ip == '') {
      throw new Exception(__('Adresse IP non configurée', __FILE__));
    }
    $url = $this->getApiBaseUrl() . $path;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    $headers = array('Accept: application/json');
    if ($data !== null) {
      $payload = json_encode($data);
      $headers[] = 'Content-Type: application/json';
      curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);
    if ($raw === false) {
      throw new Exception(__('Erreur cURL', __FILE__) . ' : ' . $curlErr);
    }
    $decoded = json_decode($raw, true);
    if ($httpCode >= 400) {
      $msg = $raw;
      if (is_array($decoded)) {
        $msg = json_encode($decoded);
      }
      throw new Exception(__('Erreur API Devialet HTTP', __FILE__) . ' ' . $httpCode . ' : ' . $msg);
    }
    if (is_array($decoded)) {
      return $decoded;
    }
    $trimmed = trim($raw);
    if ($trimmed === '') {
      return array();
    }
    if (is_numeric($trimmed)) {
      return $trimmed + 0;
    }
    return $raw;
  }

  public function testConnection() {
    try {
      $result = $this->callDevialetApi('/sources/current/soundControl/volume', 'GET', null, 5);
      $this->checkAndUpdateCmd('online', 1);
      return array('success' => true, 'message' => __('Connexion OK', __FILE__), 'result' => $result);
    } catch (Exception $e) {
      $this->checkAndUpdateCmd('online', 0);
      return array('success' => false, 'message' => $e->getMessage());
    }
  }

  public function refreshInfo() {
    $online = 0;
    try {
      $volume = $this->callDevialetApi('/sources/current/soundControl/volume');
      $online = 1;
      $volumeValue = $this->extractValue($volume, array('volume', 'value'));
      if ($volumeValue !== null && is_numeric($volumeValue)) {
        $this->checkAndUpdateCmd('volume', intval($volumeValue));
      }
    } catch (Exception $e) {
      log::add('devialet', 'debug', $this->getHumanName() . ' volume: ' . $e->getMessage());
    }

    try {
      $playingState = $this->callDevialetApi('/sources/current/playback/playingState');
      $online = 1;
      $state = $this->extractValue($playingState, array('playingState', 'value', 'state'));
      if ($state !== null) {
        $this->checkAndUpdateCmd('playing_state', strval($state));
      }
    } catch (Exception $e) {
      log::add('devialet', 'debug', $this->getHumanName() . ' playingState: ' . $e->getMessage());
    }

    try {
      $current = $this->callDevialetApi('/sources/current');
      $online = 1;
      if (is_array($current)) {
        if (isset($current['sourceId'])) {
          $this->checkAndUpdateCmd('source', strval($current['sourceId']));
        } elseif (isset($current['source']['sourceId'])) {
          $this->checkAndUpdateCmd('source', strval($current['source']['sourceId']));
        }
        if (isset($current['isMuted'])) {
          $this->checkAndUpdateCmd('mute_state', $current['isMuted'] ? 1 : 0);
        }
        if (isset($current['playbackState'])) {
          $this->checkAndUpdateCmd('playing_state', strval($current['playbackState']));
        }
        if (isset($current['playingState'])) {
          $this->checkAndUpdateCmd('playing_state', strval($current['playingState']));
        }
      }
    } catch (Exception $e) {
      log::add('devialet', 'debug', $this->getHumanName() . ' current source: ' . $e->getMessage());
    }

    try {
      $muteState = $this->callDevialetApi('/sources/current/playback/muteState');
      $online = 1;
      $mute = $this->extractValue($muteState, array('muteState', 'isMuted', 'muted', 'value'));
      if ($mute !== null) {
        $this->checkAndUpdateCmd('mute_state', $this->toBool($mute) ? 1 : 0);
      }
    } catch (Exception $e) {
      log::add('devialet', 'debug', $this->getHumanName() . ' muteState: ' . $e->getMessage());
    }

    try {
      $sources = $this->callDevialetApi('/sources');
      $online = 1;
      $this->updateSourcesList($sources);
    } catch (Exception $e) {
      log::add('devialet', 'debug', $this->getHumanName() . ' sources: ' . $e->getMessage());
    }

    $nightMode = $this->fetchNightMode();
    if ($nightMode !== null) {
      $online = 1;
      $this->checkAndUpdateCmd('night_mode', $nightMode ? 1 : 0);
    }

    $this->checkAndUpdateCmd('online', $online);
  }

  private function fetchNightMode() {
    $paths = array('/settings/audio/nightMode', '/settings/audio/nightModeOverlay');
    foreach ($paths as $path) {
      try {
        $result = $this->callDevialetApi($path);
        $value = $this->extractValue($result, array('nightMode', 'nightModeOverlay', 'enabled', 'value'));
        if ($value !== null) {
          return $this->toBool($value);
        }
      } catch (Exception $e) {
        log::add('devialet', 'debug', $this->getHumanName() . ' nightMode ' . $path . ': ' . $e->getMessage());
      }
    }
    return null;
  }

  private function updateSourcesList($sources) {
    $list = array();
    $items = array();
    if (is_array($sources)) {
      if (isset($sources['sources']) && is_array($sources['sources'])) {
        $items = $sources['sources'];
      } else {
        $items = $sources;
      }
    }
    foreach ($items as $key => $item) {
      $sourceId = '';
      $label = '';
      if (is_array($item)) {
        $sourceId = isset($item['sourceId']) ? strval($item['sourceId']) : (isset($item['id']) ? strval($item['id']) : strval($key));
        $label = isset($item['sourceName']) ? strval($item['sourceName']) : (isset($item['name']) ? strval($item['name']) : $sourceId);
        $type = isset($item['sourceType']) ? strval($item['sourceType']) : '';
        if ($label == '' && $type != '') {
          $label = $type;
        }
      } else {
        $sourceId = strval($item);
        $label = strval($item);
      }
      if ($sourceId == '') {
        continue;
      }
      $label = trim($label);
      $sourceId = trim($sourceId);
      if ($label == '') {
        $label = $sourceId;
      }
      $list[] = $sourceId . '|' . $label;
    }
    if (count($list) == 0) {
      return;
    }
    $cmd = $this->getCmd(null, 'select_source');
    if (is_object($cmd)) {
      $cmd->setConfiguration('listValue', implode(';', $list));
      $cmd->save();
    }
  }

  private function extractValue($data, $keys) {
    if (is_array($data)) {
      foreach ($keys as $key) {
        if (array_key_exists($key, $data)) {
          return $data[$key];
        }
      }
      if (count($data) == 1) {
        $values = array_values($data);
        return $values[0];
      }
      return null;
    }
    if (is_scalar($data)) {
      return $data;
    }
    return null;
  }

  private function toBool($value) {
    if (is_bool($value)) {
      return $value;
    }
    if (is_numeric($value)) {
      return intval($value) !== 0;
    }
    $value = strtolower(trim(strval($value)));
    return in_array($value, array('1', 'true', 'yes', 'on'), true);
  }
}

class devialetCmd extends cmd {
  /*     * *********************Methode d'instance************************* */

  public function execute($_options = array()) {
    $eqLogic = $this->getEqLogic();
    if (!is_object($eqLogic)) {
      throw new Exception(__('Equipement introuvable', __FILE__));
    }
    $logicalId = $this->getLogicalId();
    if ($this->getType() == 'info') {
      return $this->getConfiguration('value', $this->execCmd());
    }

    switch ($logicalId) {
      case 'refresh':
        $eqLogic->refreshInfo();
        return true;
      case 'volume_set':
        $volume = isset($_options['slider']) ? intval($_options['slider']) : null;
        if ($volume === null) {
          throw new Exception(__('Volume manquant', __FILE__));
        }
        $volume = max(0, min(100, $volume));
        $eqLogic->callDevialetApi('/sources/current/soundControl/volume', 'POST', array('volume' => $volume));
        $eqLogic->checkAndUpdateCmd('volume', $volume);
        return true;
      case 'volume_up':
        $eqLogic->callDevialetApi('/sources/current/soundControl/volumeUp', 'POST', array());
        $eqLogic->refreshInfo();
        return true;
      case 'volume_down':
        $eqLogic->callDevialetApi('/sources/current/soundControl/volumeDown', 'POST', array());
        $eqLogic->refreshInfo();
        return true;
      case 'mute':
        $eqLogic->callDevialetApi('/sources/current/playback/mute', 'POST', array());
        $eqLogic->checkAndUpdateCmd('mute_state', 1);
        return true;
      case 'unmute':
        $eqLogic->callDevialetApi('/sources/current/playback/unmute', 'POST', array());
        $eqLogic->checkAndUpdateCmd('mute_state', 0);
        return true;
      case 'play':
        $eqLogic->callDevialetApi('/sources/current/playback/play', 'POST', array());
        $eqLogic->checkAndUpdateCmd('playing_state', 'playing');
        return true;
      case 'pause':
        $eqLogic->callDevialetApi('/sources/current/playback/pause', 'POST', array());
        $eqLogic->checkAndUpdateCmd('playing_state', 'paused');
        return true;
      case 'next':
        $eqLogic->callDevialetApi('/sources/current/playback/next', 'POST', array());
        $eqLogic->refreshInfo();
        return true;
      case 'previous':
        $eqLogic->callDevialetApi('/sources/current/playback/previous', 'POST', array());
        $eqLogic->refreshInfo();
        return true;
      case 'select_source':
        $source = isset($_options['select']) ? strval($_options['select']) : '';
        if ($source == '') {
          throw new Exception(__('Source manquante', __FILE__));
        }
        $eqLogic->callDevialetApi('/sources/' . rawurlencode($source) . '/playback/play', 'POST', array());
        $eqLogic->checkAndUpdateCmd('source', $source);
        $eqLogic->refreshInfo();
        return true;
      case 'night_mode_on':
        $this->setNightMode(true);
        return true;
      case 'night_mode_off':
        $this->setNightMode(false);
        return true;
      case 'power_off':
        $eqLogic->callDevialetApi('/system/powerOff', 'POST', array());
        $eqLogic->checkAndUpdateCmd('online', 0);
        return true;
    }

    throw new Exception(__('Commande non implémentée', __FILE__) . ' : ' . $logicalId);
  }

  public function setNightMode($enabled) {
    $eqLogic = $this->getEqLogic();
    $paths = array('/settings/audio/nightMode', '/settings/audio/nightModeOverlay');
    $lastError = null;
    foreach ($paths as $path) {
      try {
        $payload = array();
        if (strpos($path, 'nightModeOverlay') !== false) {
          $payload = array('nightModeOverlay' => $enabled ? 'night' : 'off');
        } else {
          $payload = array('nightMode' => $enabled ? 'on' : 'off');
        }
        $eqLogic->callDevialetApi($path, 'POST', $payload);
        $eqLogic->checkAndUpdateCmd('night_mode', $enabled ? 1 : 0);
        return true;
      } catch (Exception $e) {
        $lastError = $e;
        log::add('devialet', 'debug', $eqLogic->getHumanName() . ' setNightMode ' . $path . ': ' . $e->getMessage());
      }
    }
    if ($lastError !== null) {
      throw $lastError;
    }
    throw new Exception(__('Impossible de configurer le mode nuit', __FILE__));
  }
}
