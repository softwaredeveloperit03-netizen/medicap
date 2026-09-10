import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-reagents-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
})
export class LogComponent implements OnInit {
  isView = false;
  loading = false;
  results: any[] = [];
  selectedReport: any = {};
  checklist: any[] = [];
  uploadedFileNames: any[] = [];
  searchQuery = '';

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getReagentsReceivingLog();
  }

  getReagentsReceivingLog() {
    this.loading = true;
    this.service
      .get(
        'store/raw.php?type=getReceivingLogGeneralMaterials&material_type=QC Materials&material_subtype=Reagents'
      )
      .subscribe({
        next: (response: any) => {
          this.results = Array.isArray(response) ? response : [];
          this.loading = false;
        },
        error: () => {
          this.results = [];
          this.loading = false;
        },
      });
  }

  view(data: any) {
    this.selectedReport = { ...data };
    this.getChkListData(this.selectedReport['receiving_no']);
    this.isView = true;
    this.getUploadChallans();
    this.loadLabelingBatches();
  }

  loadLabelingBatches() {
    const report: any = this.selectedReport;
    if (!report) {
      return;
    }
    const params: string[] = [];
    if (report.receiving_no) {
      params.push('receiving_no=' + encodeURIComponent(report.receiving_no));
    }
    if (report.material_code) {
      params.push('material_code=' + encodeURIComponent(report.material_code));
    }
    if (report.challan_no) {
      params.push('challan_no=' + encodeURIComponent(report.challan_no));
    }
    if (report.ch_no) {
      params.push('ch_no=' + encodeURIComponent(report.ch_no));
    }
    if (params.length === 0) {
      return;
    }
    this.service
      .get('store/receive.php?type=get_save_sampling_batch&' + params.join('&'))
      .subscribe((response: any) => {
        const batches = Array.isArray(response) ? response : [];
        this.selectedReport = { ...this.selectedReport, batches };
      });
  }

  getUploadChallans() {
    this.service
      .get(
        'store/challan.php?type=getUploadedChallans&ch_no=' +
          this.selectedReport['ch_no'] +
          '&po_no=' +
          this.selectedReport['po_no'] +
          '&vendor_no=' +
          this.selectedReport['vendor_no']
      )
      .subscribe((response: any) => {
        this.uploadedFileNames = Array.isArray(response) ? response : [];
      });
  }

  viewFile(url1: string) {
    const url = this.service.url + '../../upload/challan/' + url1 + '?v=1';
    window.open(url, '_blank');
  }

  viewCoafile(url: string) {
    window.open(this.service.url + '../../upload/coa/' + url, '_blank');
  }

  downloadLog() {
    this.service.open(
      'store/raw.php?type=receivingMaterialLogPDF&log_source=general&material_type=' +
        encodeURIComponent('QC Materials') +
        '&material_subtype=' +
        encodeURIComponent('Reagents')
    );
  }

  getChkListData(rec_no: string) {
    this.service
      .get(
        'master/checklist.php?type=get_rec_ChkListByTranID&rec_no=' +
          encodeURIComponent(rec_no)
      )
      .subscribe((response: any) => {
        this.checklist = Array.isArray(response) ? response : [];
      });
  }

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results;
    }
    const query = this.searchQuery.toLowerCase().trim();
    return this.results.filter((material) =>
      Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        }
        return value && value.toString().toLowerCase().includes(query);
      })
    );
  }
}
