#!/usr/bin/env php
<?php

class classMigration {

	protected $vendorName = 'Lemming';

	protected $otherReplacements = [
		"objectManater->create(" => "objectManager->get("
	];

	public function __construct($paths = []) {
		if (getenv('VENDOR') !== false) {
			$this->vendorName = getenv('VENDOR');
		}

		$files = $this->buildFileList($paths);
		$classList = $this->buildTxClassList($files);

		foreach (file('old-classes.txt', FILE_IGNORE_NEW_LINES) as $line) {
			if (strpos($line, '#') !== false) {
				continue;
			}
			$pieces = explode('!', $line);
			$classList[$pieces[0]] = ['fullName' => $pieces[1]];
		};

		foreach (file('other-replacements.txt', FILE_IGNORE_NEW_LINES) as $line) {
			if (strpos($line, '#') !== false) {
				continue;
			}
			$pieces = explode('!', $line);
			$$this->otherReplacements[$pieces[0]] = $pieces[1];
		};

		foreach ($files as $file) {
			/** @var $file SplFileInfo */
			$content = file_get_contents($file->__toString());

			foreach ($classList as $class => $classData) {

				if ($file->__toString() == $classData['file']) {
					// Set namespace
					$replacement = '<?php' . PHP_EOL . "namespace " . $this->vendorName . "\\" . $classList[$class]['namespace'] . ';' . PHP_EOL;
					$content = preg_replace("/<\?php\n/", $replacement, $content);

					// Replace class definition
					$content = preg_replace("/class\s+$class/", "class " . $classList[$class]['className'], $content);
				}

				// Migrate userfunc: file.php:\class->function
				if (isset($classData['file'])) {
					$search = '/=.*' . basename($classData['file']) . ':' . $class . '/';
					$replacement = '= ' . $classData['fullName'] . '::class';
					$content = preg_replace($search, $replacement, $content);
				}

				// Makeinstance, signal slots
				$search = "/'$class'/";
				$replacement = $classData['fullName'] . '::class';
				$content = preg_replace($search, $replacement, $content);

				// Replace other class occurences
				// Use whitespace not to match tslib_fe in $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']
				$search = "/ $class/";
				$replacement = " " . $classData['fullName'];
				$content = preg_replace($search, $replacement, $content);

				// Remove double slashes
				$content = str_replace('\\' . $replacement, $replacement, $content);

				foreach ($this->otherReplacements as $search => $replacement) {
					$content = str_replace($search, $replacement, $content);
				}
			}

			file_put_contents($file, $content);
		}
	}

	protected function buildFileList($paths) {
		$files = [];
		foreach ($paths as $path) {

			if (is_dir($path)) {

				$it = new RecursiveDirectoryIterator($path);
				$allowed = array("php");
				foreach (new RecursiveIteratorIterator($it) as $file) {

					if (in_array(substr($file, strrpos($file, '.') + 1), $allowed)) {

						$files[] = $file;
					}
				}
			} elseif (is_file($path)) {
				$files[] = $path;
			}
		}

		return $files;
	}

	protected function buildTxClassList($files = []) {
		$classList = [];

		foreach ($files as $file) {
			/** @var $file SplFileInfo */
			$content = file_get_contents($file->__toString());
			preg_match_all('/class\s+(?P<class>tx_([\w_]+))/i', $content, $mainClasses);

			if (isset($mainClasses['class'][0])) {
				$mainClass = $mainClasses['class'][0];
				$classList[$mainClass] = [
					'file' => $file->__toString(),
					'className' => self::getClassName($mainClass),
					'namespace' => self::getNamespace($mainClass),
					'fullName' => '\\' . $this->vendorName . '\\' . self::getNamespace($mainClass) . '\\' . self::getClassName($mainClass)
				];
			}
		}

		$classList = array_filter($classList);
		return $classList;
	}

	protected static function getNamespace($class) {
		$pieces = explode('_', $class);
		return implode('\\', array_slice($pieces, 1, -1));
	}

	protected static function getClassName($class) {
		return end(explode('_', $class));
	}
}

error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
array_shift($argv);
$classMigration = new classMigration($argv);