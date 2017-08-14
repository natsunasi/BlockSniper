<?php

namespace BlockHorizons\BlockSniper\listeners;

use BlockHorizons\BlockSniper\brush\PropertyProcessor;
use BlockHorizons\BlockSniper\Loader;
use BlockHorizons\BlockSniper\presets\PresetPropertyProcessor;
use BlockHorizons\BlockSniper\user_interface\WindowHandler;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\Player;

class UserInterfaceListener implements Listener {

	/** @var Loader */
	private $loader = null;

	public function __construct(Loader $loader) {
		$this->loader = $loader;
	}

	/**
	 * @param DataPacketReceiveEvent $event
	 */
	public function onDataPacket(DataPacketReceiveEvent $event) {
		$packet = $event->getPacket();
		if($packet instanceof ModalFormResponsePacket) {
			if(json_decode($packet->formData, true) === null) {
				return;
			}
			$windowHandler = new WindowHandler();
			switch($packet->formId) {
				case 3200: // Main Menu
					$index = (int) $packet->formData + 1;
					switch($index) {
						case 1:
							$json = $windowHandler->getBrushWindowJson($event->getPlayer(), $this->loader);
							break;
						case 3:
							$json = $windowHandler->getConfigurationWindowJson($this->loader);
							break;
						default:
							$json = $windowHandler->getWindowJson($index);
							break;
					}
					if($index !== 4) {
						$packet = new ModalFormRequestPacket();
						$packet->formId = $windowHandler->getWindowIdFor($index);
						$packet->formData = $json;
						$event->getPlayer()->dataPacket($packet);
					}
					return;

				case 3201: // Brush Menu
					$data = json_decode($packet->formData, true);
					$processor = new PropertyProcessor($event->getPlayer(), $this->loader);
					foreach($data as $key => $value) {
						$processor->process($key, $value);
					}
					$this->navigate($windowHandler::WINDOW_MAIN_MENU, $event->getPlayer(), $windowHandler);
					break;

				case 3202: // Preset Menu
					$index = (int) $packet->formData + 4;
					$windowHandler = new WindowHandler();
					switch($index) {
						case 4:
							$json = $windowHandler->getPresetCreationWindowJson($event->getPlayer(), $this->loader);
							break;
						case 5:
							$json = $windowHandler->getPresetDeletionMenuJson($this->loader);
							break;
						case 6:
							$json = $windowHandler->getPresetSelectionMenuJson($this->loader);
							break;
						default:
							$json = "";
							$index = 8;
					}
					if($index !== 8) {
						$packet = new ModalFormRequestPacket();
						$packet->formId = $windowHandler->getWindowIdFor($index);
						$packet->formData = $json;
						$event->getPlayer()->dataPacket($packet);
					} else {
						$this->navigate($windowHandler::WINDOW_MAIN_MENU, $event->getPlayer(), $windowHandler);
					}
					break;

				case 3203: // Configuration Menu
					$data = json_decode($packet->formData, true);
					foreach($data as $key => $value) {
						if($key === 1) {
							$value = Loader::getAvailableLanguages()[$value];
						}
						$this->loader->getSettings()->set($key, $value);
					}
					if($data[9] === true) {
						$this->loader->reload();
					}
					$this->navigate($windowHandler::WINDOW_MAIN_MENU, $event->getPlayer(), $windowHandler);
					break;

				case 3204: // Preset Creation Menu
					$data = json_decode($packet->formData, true);
					$processor = new PresetPropertyProcessor($event->getPlayer(), $this->loader);
					foreach($data as $key => $value) {
						$processor->process($key, $value);
					}
					$this->navigate($windowHandler::WINDOW_PRESET_MENU, $event->getPlayer(), $windowHandler);
					break;

				case 3205: // Preset Deletion Menu
					$index = (int) $packet->formData;
					$presetName = "";
					foreach($this->loader->getPresetManager()->getAllPresets() as $key => $name) {
						if($key === $index) {
							$presetName = $name;
						}
					}
					$this->loader->getPresetManager()->deletePreset($presetName);
					$this->navigate($windowHandler::WINDOW_PRESET_MENU, $event->getPlayer(), $windowHandler);
					break;

				case 3206: // Preset Selection Menu
					$index = (int) $packet->formData;
					$presetName = "";
					foreach($this->loader->getPresetManager()->getAllPresets() as $key => $name) {
						if($key === $index) {
							$presetName = $name;
						}
					}
					$preset = $this->loader->getPresetManager()->getPreset($presetName);
					$preset->apply($event->getPlayer(), $this->loader);
					$this->navigate($windowHandler::WINDOW_PRESET_MENU, $event->getPlayer(), $windowHandler);
					break;
			}
		}
	}

	/**
	 * @param int           $menu
	 * @param Player        $player
	 * @param WindowHandler $windowHandler
	 */
	public function navigate(int $menu, Player $player, WindowHandler $windowHandler) {
		$packet = new ModalFormRequestPacket();
		$packet->formId = $windowHandler->getWindowIdFor($menu);
		$packet->formData = $windowHandler->getWindowJson($menu);
		$player->dataPacket($packet);
	}
}