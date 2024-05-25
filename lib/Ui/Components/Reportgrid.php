<?php
namespace Mixcode\Ui\Components;

use Closure;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use SLiMS\Plugins;

class Reportgrid extends Datagrid
{
    protected string $filename = '';

    /**
     * Re-constructing datagrid for reporting purpose
     *
     * @param string $name
     * @param string $action
     * @param string $method
     * @param string $target
     */
    public function __construct(string $name = 'reportgrid', string $action = '', string $method = 'POST', string $target = 'submitExec')
    {
        parent::__construct(...func_get_args());

        // Add some options
        $this->filename = $name;
        $this->properties['editable'] = false;
        $this->properties['with_spreadsheet_export'] = true;
    }

    /**
     * Exporting rdata
     *
     * @param Closure $callback
     * @return void
     */
    public function onExport(Closure $callback)
    {
        if (isset($_GET['export'])) {
            foreach ($this->getData(withLimit: false) as $seq => $data) {
                if ($seq === 0) {
                    $this->detail['record'][] = array_keys($data);
                }
                $this->detail['record'][] = $data;
            }

            // Handle custom export
            Plugins::getInstance()->execute('on_reporting_export', [$this->detail['record']]);

            if (is_callable($callback)) {
                $callback($this, $this->detail['record']);
                return;
            }
        }
    }

    public function exportSpreadSheet()
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()
          ->fromArray(
              $this->detail['record'],  // The data to set
              NULL,        // Array values with this value will not be set
              'A1'         // Top left coordinate of the worksheet range where
                           //    we want to set these values (default is A1)
          );

        $writer = new Xlsx($spreadsheet);
        header("Content-Type: application/xlsx");
        header("Content-Disposition: attachment; filename=$this->filename.xlsx");
        header("Pragma: no-cache");
        $writer->save('php://output');
        exit;
    }

    protected function setPrintAction()
    {
        $label = '<strong>'.$this->detail['total'].'</strong> '.__('record(s) found. Currently displaying page').' '.(int)($_GET['page']??1).' ('.$this->limit.' '.__('record each page').') ';
        $buttonPrint = (string)createComponent('a', [
            'class' => 's-btn btn btn-default printReport notAJAX',
            'href' => '#',
            'onclick' => 'window.print()'
        ])->setSlot(__('Print Current Page'));

        $buttonExport = '';
        
        // Handle custom export
        Plugins::getInstance()->execute('before_rendering_reportgrid_print_button', [$this, &$buttonExport]);

        if ($this->properties['with_spreadsheet_export']) {
            $buttonExport .= (string)createComponent('a', [
                'class' => 's-btn btn btn-default printReport',
                'href' => $this->setUrl(['export' => 'spreadsheet']),
            ])->setSlot(__('Export to spreadsheet format'));
        }

        $pagiNation = '';
        if ($this->detail['total'] > $this->properties['limit']) {
            $pagiNation = (string)createComponent('div', ['class' => 'paging-area'])
                            ->setSlot((string)Pagination::create($this->setUrl(), $this->detail['total'], $this->properties['limit']));
        }                            

        return $pagiNation . (string)createComponent('div', [
            'class' => 's-print__page-info printPageInfo'
        ])->setSlot($label . $buttonPrint . $buttonExport);
    }

    public function __toString()
    {
        $report = parent::__toString();
        $printArea = $this->setPrintAction();

        return $printArea . $report;

    }
}