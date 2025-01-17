<?php

declare(strict_types=1);

/**
 * Hook into the inline icons.
 */

namespace HDNET\Focuspoint\Hooks;

use HDNET\Autoloader\Annotation\Hook;
use HDNET\Focuspoint\Service\WizardService;
use TYPO3\CMS\Backend\Form\Element\InlineElementHookInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Hook into the inline icons.
 *
 * @Hook("TYPO3_CONF_VARS|SC_OPTIONS|t3lib/class.t3lib_tceforms_inline.php|tceformsInlineHook")
 */
class InlineRecord implements InlineElementHookInterface
{
    /**
     * Initializes this hook object.
     *
     * @param object $parentObject
     */
    public function init(&$parentObject): void
    {
    }

    /**
     * Pre-processing to define which control items are enabled or disabled.
     *
     * @param string $parentUid       The uid of the parent (embedding) record (uid or NEW...)
     * @param string $foreignTable    The table (foreign_table) we create control-icons for
     * @param array  $childRecord     The current record of that foreign_table
     * @param array  $childConfig     TCA configuration of the current field of the child record
     * @param bool   $isVirtual       Defines whether the current records is only virtually shown and not physically part of the parent record
     * @param array  $enabledControls (reference) Associative array with the enabled control items
     */
    public function renderForeignRecordHeaderControl_preProcess(
        $parentUid,
        $foreignTable,
        array $childRecord,
        array $childConfig,
        $isVirtual,
        array &$enabledControls
    ): void {
    }

    /**
     * Post-processing to define which control items to show. Possibly own icons can be added here.
     *
     * @param string $parentUid    The uid of the parent (embedding) record (uid or NEW...)
     * @param string $foreignTable The table (foreign_table) we create control-icons for
     * @param array  $childRecord  The current record of that foreign_table
     * @param array  $childConfig  TCA configuration of the current field of the child record
     * @param bool   $isVirtual    Defines whether the current records is only virtually shown and not physically part of the parent record
     * @param array  $controlItems (reference) Associative array with the currently available control items
     */
    public function renderForeignRecordHeaderControl_postProcess(
        $parentUid,
        $foreignTable,
        array $childRecord,
        array $childConfig,
        $isVirtual,
        array &$controlItems
    ): void {
        if ('sys_file_reference' !== $foreignTable) {
            return;
        }

        // Handling for TYPO3 > 8.x
        foreach ($childRecord['uid_local'] as $item) {
            if ('sys_file' !== $item['table']) {
                return;
            }
            if (!MathUtility::canBeInterpretedAsInteger($childRecord['uid'])) {
                return;
            }
        }

        $table = $childRecord['tablenames'];
        $uid = (int) $parentUid;

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        if ($this->isValidRecord($table, $uid)) {
            $arguments = GeneralUtility::_GET();
            // The arguments array is different in case this is called by an AJAX request
            // via an IRRE inside an IRRE...
            if (!isset($arguments['edit'])) {
                $url = parse_url(GeneralUtility::getIndpEnv('HTTP_REFERER'));
                parse_str($url['query'], $arguments);
            }
            $returnUrl = [
                'edit' => $arguments['edit'],
                'returnUrl' => $arguments['returnUrl'],
            ];

            $wizardArguments = [
                'P' => [
                    'referenceUid' => $childRecord['uid'],
                    'returnUrl' => (string) $uriBuilder->buildUriFromRoute('record_edit', $returnUrl),
                ],
            ];
            $wizardUri = (string) $uriBuilder->buildUriFromRoute('focuspoint', $wizardArguments);
        } else {
            $wizardUri = 'javascript:alert(\'Please save the base record first, because open this wizard will not save the changes in the current form!\');';
        }

        /** @var WizardService $wizardService */
        $wizardService = GeneralUtility::makeInstance(WizardService::class);
        $this->arrayUnshiftAssoc($controlItems, 'focuspoint', $wizardService->getWizardButton((string) $wizardUri, true));
    }

    /**
     * Check if the record is valid.
     */
    protected function isValidRecord(string $table, int $uid): bool
    {
        return null !== BackendUtility::getRecord($table, $uid);
    }

    /**
     * Add a element with the given key in front of the array.
     */
    protected function arrayUnshiftAssoc(array &$arr, string $key, string $val): void
    {
        $arr = array_reverse($arr, true);
        $arr[$key] = $val;
        $arr = array_reverse($arr, true);
    }
}
