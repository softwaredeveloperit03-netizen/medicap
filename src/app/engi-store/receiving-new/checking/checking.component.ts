


import { Component, OnInit } from '@angular/core';
import { ClrLoadingState } from '@clr/angular';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {
  isView = false;
  
  plant_id:any;

 
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.plant_id = this.service.getPlantConfigFields('plant_id');

    this.getInprocessReceivingsGeneralMaterials();
   
  }

 
  material_type = 'Stationary';
  results;
  getInprocessReceivingsGeneralMaterials() {
    this.service.get('store/raw.php?type=getInprocessReceivingsGeneralMaterials&material_type='+this.material_type).subscribe(response => {
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
 

  update(status) {
    this.service.get('store/raw.php?type=checkReceivedMaterial&status=' + status + '&id=' + this.selectedReport['id']+ '&challan_id=' + this.selectedReport['challan_id']).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Material updated successfully');
        this.isView = false;
        this.getInprocessReceivingsGeneralMaterials();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
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