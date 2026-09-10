

import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {
  buildChecklistReadonlyRows,
  hasChecklistFooterRow,
  resolveReceivingChecklistRows,
} from 'src/app/master/checklist/checklist-shared';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {
  isView = false;
  loading = false;

  plant_id: any;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.getInprocessReceivings();
  }

  material_type = 'Raw Material';
  results: any[] = [];

  getInprocessReceivings() {
    this.loading = true;
    const mt = encodeURIComponent(this.material_type || 'Raw Material');
    this.service.get('store/raw.php?type=getInprocessReceivings&material_type=' + mt).subscribe(
      (response: any) => {
        this.results = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      () => {
        this.results = [];
        this.loading = false;
      }
    );
  }

  selectedReport: any = {};

  view(data: any) {
    this.selectedReport = { ...data };
    this.parseReceivingDetails(this.selectedReport);
    this.loadChecklistForReport();
    this.isView = true;
    this.getUploadChallans();
  }

  private parseReceivingDetails(row: any): void {
    if (!row || row.receiving_details == null) {
      return;
    }
    if (typeof row.receiving_details === 'string') {
      try {
        row.receiving_details = JSON.parse(row.receiving_details);
      } catch {
        row.receiving_details = {};
      }
    }
  }

  uploadedFileNames: any[] = [];

  getUploadChallans() {
    this.service.get(
      'store/challan.php?type=getUploadedChallans&ch_no=' + this.selectedReport['ch_no'] +
      '&po_no=' + this.selectedReport['po_no'] +
      '&vendor_no=' + this.selectedReport['vendor_no']
    ).subscribe((response: any) => {
      this.uploadedFileNames = Array.isArray(response) ? response : [];
    });
  }

  viewFile(url1: string) {
    const url = this.service.url + '../../upload/challan/' + url1 + '?v=1';
    window.open(url, '_blank');
  }

  viewCoafile(url: string) {
    const full = this.service.url + '../../upload/coa/' + url;
    window.open(full, '_blank');
  }

  update(status: string) {
    this.service.get(
      'store/raw.php?type=checkReceivedMaterial&status=' + status +
      '&id=' + this.selectedReport['id'] +
      '&challan_id=' + this.selectedReport['challan_id']
    ).subscribe((response: any) => {
      if (response['status'] == 'success') {
        let msg = 'Material updated successfully';
        if (response['grn_no']) {
          msg += '. GRN: ' + response['grn_no'];
        }
        if (response['ar_nos'] && response['ar_nos'].length) {
          msg += ', AR: ' + response['ar_nos'].join(', ');
        }
        alertify.success(msg);
        this.isView = false;
        this.getInprocessReceivings();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  checklist: any[] = [];
  checkPointRows: any[] = [];

  private applyChecklistRows(rows: any[]): void {
    this.checklist = rows;
    this.checkPointRows = buildChecklistReadonlyRows(rows);
  }

  loadChecklistForReport() {
    const recNo = this.selectedReport?.receiving_no || '';
    const cmId = this.selectedReport?.id || '';

    this.applyChecklistRows(
      resolveReceivingChecklistRows([], this.selectedReport?.receiving_details)
    );

    this.service.get(
      'store/raw.php?type=getReceivingChecklist&rec_no=' + encodeURIComponent(recNo) +
      '&challan_material_id=' + encodeURIComponent(cmId)
    ).subscribe(
      (response: any) => {
        this.applyChecklistRows(
          resolveReceivingChecklistRows(response, this.selectedReport?.receiving_details)
        );
      },
      () => {
        this.applyChecklistRows(
          resolveReceivingChecklistRows([], this.selectedReport?.receiving_details)
        );
      }
    );
  }

  trackCheckPointRow(index: number, row: any): string {
    if (row?.rowKey) {
      return row.rowKey;
    }
    if (row?.type === 'header' || row?.type === 'footer') {
      return `${row.type}_${row.title || index}`;
    }
    return String(index);
  }

  hasChecklistFooterInRows(): boolean {
    return hasChecklistFooterRow(this.checkPointRows);
  }

  searchQuery = '';

  get filteredMaterials(): any[] {
    if (!Array.isArray(this.results)) {
      return [];
    }
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results;
    }

    const query = this.searchQuery.toLowerCase().trim();

    return this.results.filter((material) => {
      return Object.entries(material).some(([key, value]) => {
        if (value === null || value === undefined) {
          return false;
        }
        if (key === 'entry_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          return (
            dateValue instanceof Date &&
            !isNaN(dateValue.getTime()) &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        }
        return value.toString().toLowerCase().includes(query);
      });
    });
  }
}
