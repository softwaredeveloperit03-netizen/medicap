


import { Component, OnInit } from '@angular/core';
import { ClrLoadingState } from '@clr/angular';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-rejected',
  templateUrl: './rejected.component.html',
  styleUrls: ['./rejected.component.css']
})
export class RejectedComponent implements OnInit {

  isView = false;
  
  plant_id:any;

 
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.plant_id = this.service.getPlantConfigFields('plant_id');

    this.getReceivingLogRejected();
   
  }

 
  material_type = 'Raw Material';
  results;
  getReceivingLogRejected() {
    this.service.get('store/raw.php?type=getReceivingLogRejected&material_type='+this.material_type).subscribe(response => {
      this.results = response;
    });
  }
 
  selectedReport = [];

  view(data) {
    this.selectedReport = data;
    this.getChkListData(this.selectedReport['receiving_no']);
    this.isView = true;
    this.getUploadChallans();
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
    const mt = encodeURIComponent(this.material_type || 'Raw Material');
    this.service.open('store/raw.php?type=receivingMaterialLogPDF&material_type=' + mt);
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