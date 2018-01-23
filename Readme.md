# TYPO3 class migrations

* Replaces old class names, see `old-classes.txt`
* Rebuild Tx_ classes to use namespaces
* Replace others, see `other-replacements.txt`
* Replace `'AnyKnownClass'` with `AnyKnownClass::class`
* Remove `?>` at end of file
* Make sure there is a newline at end of file

## Example diffs
```
 <?php
+namespace Lemming\YourProducts\Domain\Model;

-class Tx_YourProducts_Domain_Model_Product extends Tx_Extbase_DomainObject_AbstractEntity {
+class Product extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {
```

```
-$instance = t3lib_div::makeInstance('AnyKnownClass')
+$instance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(AnyKnownClass::class);
```

```
-$another = $this->objectManater->create('AnyKnownClass')
+$another = $this->objectManater->get(AnyKnownClass::class)
```

## Usage

```
export VENDOR=Lemming
./migrate path/to/your/extension path/to/another/extension
```

Make sure the the paths contain all your custom extensions when there are cross references and you want to rebuild Tx_ classes