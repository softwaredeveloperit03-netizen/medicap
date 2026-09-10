import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
import * as XLSX from 'xlsx';
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers: [DatePipe],
})
export class LogComponent implements OnInit {
  from_date = '';
  to_date = '';
  isView = false;
  isView1 = false;
  material_type = '';
  results;
  grndetails = [];
  selectedReport = [];
  challan_for = '';
  today = '';
  material_subtype = '';
  checklist: any = [];
  plant_id: any;

  constructor(
    private service: DataAccessService,
    private datePipe: DatePipe,
    private cdr: ChangeDetectorRef
  ) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  async ngOnInit() {
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    await this.getGRNLog();
  }

  getGRNLog() {
    return new Promise((res, rej) => {
      this.service
        .get(
          'store/raw.php?type=getGRNLog&from_date=' +
            this.from_date +
            '&to_date=' +
            this.to_date +
            '&material_subtype=' +
            this.material_subtype
        )
        .subscribe((response) => {
          res(response);
          this.results = response;
        });
    });
  }

  getChkListData(grnNo) {
    this.service
      .get('master/checklist.php?type=getChkListByTranID&tranId=' + grnNo)
      .subscribe((response) => {
        this.checklist = response;
      });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.grndetails = this.selectedReport['grn_details'];
    //this.getChkListData(this.selectedReport['grn_no']);
    this.isView = true;
    this.cdr.detectChanges();
  }

  download() {
    this.service.open(
      'store/raw.php?type=downloadGRN&id=' +
        this.selectedReport['id'] +
        '&challan_no=' +
        this.selectedReport['challan_no']
    );
  }

  downloadLog() {
    this.service.open(
      'store/raw.php?type=GRNLogPDF&from_date=' +
        this.from_date +
        '&to_date=' +
        this.to_date +
        '&challan_for=' +
        this.challan_for
    );
  }

  viewfile(url) {
    url = this.service.url + '../../upload/coa/' + url;
    window.open(url, '_blank');
  }

  viewChallan(url) {
    // url = this.service.url + 'upload/challan/' + url;
    // window.open(url, '_blank');
    // window.open(this.service.url+ this.selectedReport['challan_file']);
    window.open(
      this.service.url + '../../upload/challan' + this.selectedReport['id']
    );
  }

  AllRecord() {
    this.service
      .get('store/raw.php?type=getAllGRNLog')
      .subscribe((response: any) => {
        this.results = response;
      });
    this.material_subtype = '';
  }
  exportToExcel(): void {
    const dataToExport = this.results.map((result, index) => ({
      'Sr.': index + 1,
      'Receiving no': result.grn_no,
      'Date': result.grn_date,
      'Receiving Date': result.receiving_date,
      'Material Type': result.material_subtype,
      'Material Code': result.material_code,
      'Material Name': result.material_name,
      Grade: result.gradeName,
      Qty: `${result.received_qty} ${result.unit}`,
      Status: result.status,
    }));

    const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(dataToExport);
    const workbook: XLSX.WorkBook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Report');

    XLSX.writeFile(workbook, 'report.xlsx');
  }
}
