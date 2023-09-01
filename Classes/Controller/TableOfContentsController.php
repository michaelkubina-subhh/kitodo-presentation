<?php
/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Kitodo\Dlf\Controller;

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\MetsDocument;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Controller class for plugin 'Table Of Contents'.
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class TableOfContentsController extends AbstractController
{
    /**
     * This holds the active entries according to the currently selected page
     *
     * @var array
     * @access protected
     */
    protected $activeEntries = [];

    /**
     * The main method of the plugin
     *
     * @access public
     *
     * @return void
     */
    public function mainAction()
    {
        // Load current document.
        $this->loadDocument();
        if ($this->isDocMissing()) {
            // Quit without doing anything if required variables are not set.
            return;
        } else {
            $this->setPage();

            $this->view->assign('toc', $this->makeMenuArray());
        }
    }

    /**
     * This builds a menu array for HMENU
     *
     * @access private
     *
     * @return array HMENU array
     */
    private function makeMenuArray()
    {
        $this->requestData['double'] = MathUtility::forceIntegerInRange($this->requestData['double'], 0, 1, 0);
        $menuArray = [];
        // Does the document have physical elements or is it an external file?
        if (
            !empty($this->document->getDoc()->physicalStructure)
            || !MathUtility::canBeInterpretedAsInteger($this->requestData['id'])
        ) {
            $this->getAllLogicalUnits();
            // Go through table of contents and create all menu entries.
            foreach ($this->document->getDoc()->tableOfContents as $entry) {
                $menuArray[] = $this->getMenuEntry($entry, true);
            }
        } else {
            // Go through table of contents and create top-level menu entries.
            foreach ($this->document->getDoc()->tableOfContents as $entry) {
                $menuArray[] = $this->getMenuEntry($entry, false);
            }
            // Build table of contents from database.
            $result = $this->documentRepository->getTableOfContentsFromDb($this->document->getUid(), $this->document->getPid(), $this->settings);

            $allResults = $result->fetchAllAssociative();

            if (count($allResults) > 0) {
                $menuArray[0]['ITEM_STATE'] = 'CURIFSUB';
                $menuArray[0]['_SUB_MENU'] = [];
                foreach ($allResults as $resArray) {
                    $entry = [
                        'label' => !empty($resArray['mets_label']) ? $resArray['mets_label'] : $resArray['title'],
                        'type' => $resArray['type'],
                        'volume' => $resArray['volume'],
                        'year' => $resArray['year'],
                        'orderlabel' => $resArray['mets_orderlabel'],
                        'pagination' => '',
                        'targetUid' => $resArray['uid']
                    ];
                    $menuArray[0]['_SUB_MENU'][] = $this->getMenuEntry($entry, false);
                }
            }
        }
        $this->sortMenu($menuArray);
        return $menuArray;
    }

    /**
     * This builds an array for one menu entry
     *
     * @access private
     *
     * @param array $entry : The entry's array from \Kitodo\Dlf\Common\Doc->getLogicalStructure
     * @param bool $recursive : Whether to include the child entries
     *
     * @return array HMENU array for menu entry
     */
    private function getMenuEntry(array $entry, $recursive = false)
    {
        $entry = $this->resolveMenuEntry($entry);

        $entryArray = [];
        // Set "title", "volume", "type" and "pagination" from $entry array.
        $entryArray['title'] = $this->setTitle($entry);
        $entryArray['volume'] = $entry['volume'];
        $entryArray['year'] = $entry['year'];
        $entryArray['orderlabel'] = $entry['orderlabel'];
        $entryArray['type'] = $this->getTranslatedType($entry['type']);
        $entryArray['pagination'] = htmlspecialchars($entry['pagination']);
        $entryArray['_OVERRIDE_HREF'] = '';
        $entryArray['doNotLinkIt'] = 1;
        $entryArray['ITEM_STATE'] = 'NO';

        // Build menu links based on the $entry['points'] array.
        if (
            !empty($entry['points'])
            && MathUtility::canBeInterpretedAsInteger($entry['points'])
        ) {
            $entryArray['page'] = $entry['points'];

            $entryArray['doNotLinkIt'] = 0;
            if ($this->settings['basketButton']) {
                $entryArray['basketButton'] = [
                    'logId' => $entry['id'],
                    'startpage' => $entry['points']
                ];
            }
        } elseif (
            !empty($entry['points'])
            && is_string($entry['points'])
        ) {
            $entryArray['id'] = $entry['points'];
            $entryArray['page'] = 1;
            $entryArray['doNotLinkIt'] = 0;
            if ($this->settings['basketButton']) {
                $entryArray['basketButton'] = [
                    'logId' => $entry['id'],
                    'startpage' => $entry['points']
                ];
            }
        } elseif (!empty($entry['targetUid'])) {
            $entryArray['id'] = $entry['targetUid'];
            $entryArray['page'] = 1;
            $entryArray['doNotLinkIt'] = 0;
            if ($this->settings['basketButton']) {
                $entryArray['basketButton'] = [
                    'logId' => $entry['id'],
                    'startpage' => $entry['targetUid']
                ];
            }
        }
        // Set "ITEM_STATE" to "CUR" if this entry points to current page.
        if (in_array($entry['id'], $this->activeEntries)) {
            $entryArray['ITEM_STATE'] = 'CUR';
        }
        // Build sub-menu if available and called recursively.
        if (
            $recursive === true
            && !empty($entry['children'])
        ) {
            // Build sub-menu only if one of the following conditions apply:
            // 1. Current menu node is in rootline
            // 2. Current menu node points to another file
            // 3. Current menu node has no corresponding images
            if (
                $entryArray['ITEM_STATE'] == 'CUR'
                || is_string($entry['points'])
                || empty($this->document->getDoc()->smLinks['l2p'][$entry['id']])
            ) {
                $entryArray['_SUB_MENU'] = [];
                foreach ($entry['children'] as $child) {
                    // Set "ITEM_STATE" to "ACT" if this entry points to current page and has sub-entries pointing to the same page.
                    if (in_array($child['id'], $this->activeEntries)) {
                        $entryArray['ITEM_STATE'] = 'ACT';
                    }
                    $entryArray['_SUB_MENU'][] = $this->getMenuEntry($child, true);
                }
            }
            // Append "IFSUB" to "ITEM_STATE" if this entry has sub-entries.
            $entryArray['ITEM_STATE'] = ($entryArray['ITEM_STATE'] == 'NO' ? 'IFSUB' : $entryArray['ITEM_STATE'] . 'IFSUB');
        }
        return $entryArray;
    }

    /**
     * If $entry references an external METS file (as mptr),
     * try to resolve its database UID and return an updated $entry.
     *
     * This is so that when linking from a child document back to its parent,
     * that link is via UID, so that subsequently the parent's TOC is built from database.
     *
     * @access private
     *
     * @param array $entry
     * @return array
     */
    private function resolveMenuEntry($entry)
    {
        // If the menu entry points to the parent document,
        // resolve to the parent UID set on indexation.
        $doc = $this->document->getDoc();
        if (
            $doc instanceof MetsDocument
            && ($entry['points'] === $doc->parentHref || $this->isMultiElement($entry['type']))
            && !empty($this->document->getPartof())
        ) {
            unset($entry['points']);
            $entry['targetUid'] = $this->document->getPartof();
        }

        return $entry;
    }

    /**
     * Get all logical units the current page or track is a part of.
     *
     * @access private
     *
     * @return void
     */
    private function getAllLogicalUnits() {
        if (
            !empty($this->requestData['page'])
            && !empty($this->document->getDoc()->physicalStructure)
        ) {
            $this->activeEntries = array_merge((array) $this->document->getDoc()->smLinks['p2l'][$this->document->getDoc()->physicalStructure[0]],
                (array) $this->document->getDoc()->smLinks['p2l'][$this->document->getDoc()->physicalStructure[$this->requestData['page']]]);
            if (
                !empty($this->requestData['double'])
                && $this->requestData['page'] < $this->document->getDoc()->numPages
            ) {
                $this->activeEntries = array_merge($this->activeEntries,
                    (array) $this->document->getDoc()->smLinks['p2l'][$this->document->getDoc()->physicalStructure[$this->requestData['page'] + 1]]);
            }
        }
    }

    /**
     * Get translated type of entry.
     *
     * @access private
     *
     * @param string $type
     *
     * @return string
     */
    private function getTranslatedType($type) {
        return Helper::translate($type, 'tx_dlf_structures', $this->settings['storagePid']);
    }

    /**
     * Check if element has type 'multivolume_work' or 'multipart_manuscript'.
     * For Kitodo.Production prior to version 3.x, hierarchical child documents
     * always come with their own METS file for their parent document, even
     * if multiple documents in fact have the same parent. To make sure that all
     * of them point to the same parent document in Kitodo.Presentation, we
     * need some workaround here.
     *
     * @todo Should be removed when Kitodo.Production 2.x is no longer supported.
     *
     * @access private
     *
     * @param string $type
     *
     * @return bool
     */
    private function isMultiElement($type) {
        return $type === 'multivolume_work' || $type === 'multipart_manuscript';
    }
    /**
     * Set title from entry.
     *
     * @access private
     *
     * @param array $entry
     *
     * @return string
     */
    private function setTitle($entry) {
        if (empty($entry['label']) && empty($entry['orderlabel'])) {
            foreach ($this->settings['titleReplacements'] as $titleReplacement) {
                if ($entry['type'] == $titleReplacement['type']) {
                    $fields = explode(",", $titleReplacement['fields']);
                    $title = '';
                    foreach ($fields as $field) {
                        if ($field == 'type') {
                            $title .= $this->getTranslatedType($entry['type']) . ' ';
                        } else {
                            $title .= $entry[$field] . ' ';
                        }
                    }
        
                    return trim($title);
                }
            }
        }
        return $entry['label'] ?: $entry['orderlabel'];
    }

    /**
     * Sort menu by orderlabel.
     *
     * @access private
     *
     * @param array &$menu
     *
     * @return void
     */
    private function sortMenu(&$menu) {
        if ($menu[0]['type'] == $this->getTranslatedType("newspaper")) {
            $this->sortSubMenu($menu);
        }
        if ($menu[0]['type'] == $this->getTranslatedType("year")) {
            $this->sortSubMenu($menu);
        }
    }

    /**
     * Sort sub menu e.g years of the newspaper by orderlabel.
     *
     * @access private
     *
     * @param array &$menu
     *
     * @return void
     */
    private function sortSubMenu(&$menu) {
        usort($menu[0]['_SUB_MENU'], function ($firstElement, $secondElement) {
            if (!empty($firstElement['orderlabel'])) {
                return $firstElement['orderlabel'] <=> $secondElement['orderlabel'];
            }
            return $firstElement['year'] <=> $secondElement['year'];
        });
    }
}
