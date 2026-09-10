 import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
import * as XLSX from 'xlsx';
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers:[DatePipe]

})
export class LogComponent implements OnInit {
  isView = false;
  
  plant_id:any;

 
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.plant_id = this.service.getPlantConfigFields('plant_id');

    this.getReceivingLogGeneralMaterials();
   
  }

 
  material_type = 'Stationary';
  results;
  getReceivingLogGeneralMaterials() {
    this.service.get('store/raw.php?type=getReceivingLogGeneralMaterials&material_type='+this.material_type).subscribe(response => {
      this.results = response;
    });
  }
 
  selectedReport: any = {};

  view(data) {
    this.selectedReport = { ...data };
    this.getChkListData(this.selectedReport['receiving_no']);
    this.isView = true;
    this.getUploadChallans();
    this.loadLabelingBatches();
  }

  /** Labeling Details — fetch batches for this receiving record. */
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
    this.service.get('store/receive.php?type=get_save_sampling_batch&' + params.join('&')).subscribe((response: any) => {
      const batches = Array.isArray(response) ? response : [];
      this.selectedReport = { ...this.selectedReport, batches };
    });
  }


  uploadedFileNames;
  getUploadChallans() {
    this.service.get('store/challan.php?type=getUploadedChallans&ch_no=' +this.selectedReport['ch_no'] +'&po_no=' +this.selectedReport['po_no']  +'&vendor_no=' +this.selectedReport['vendor_no']).subscribe((response) => {
        this.uploadedFileNames = response;
    });
  }



  viewFile(url1) {
    let url = this.service.url + '../../upload/challan/' + url1 +'?v=1';
    window.open(url, '_blank');
  }



  viewCoafile(url) {
    url = this.service.url + '../../upload/coa/' + url;
    window.open(url, '_blank');
  }
 

  downloadPDF(sign){
    this.service.open('store/raw.php?type=receivingMaterialPDF&pdfsign='+sign+'&id=' + this.selectedReport['id']);
  }

  downloadLog(){
    this.service.open('store/raw.php?type=receivingMaterialLogPDF&log_source=general&material_type=' + encodeURIComponent(this.material_type));
  }
 
  
  checklist;
  getChkListData(rec_no) {
    this.service.get('master/checklist.php?type=get_rec_ChkListByTranID&rec_no=' + encodeURIComponent(rec_no)).subscribe(response => {
      this.checklist = response;
    });
  }

 
  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.results.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }

















}