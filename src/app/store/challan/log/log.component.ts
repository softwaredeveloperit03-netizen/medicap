import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers: [DatePipe]
})
export class LogComponent implements OnInit {
  isView = false;
  results: any[] = [];

  selectedResult: any = {};
  remark = '';
  constructor(private service: DataAccessService,private datePipe: DatePipe) {}

  ngOnInit(): void {
    this.getChallansLog();

    this.plant_id = localStorage.getItem('plant_id');
  }
 
  plant_id = localStorage.getItem('plant_id');

  getChallansLog() {
    this.service.get('store/challan.php?type=getChallansLog').subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
    });
  }



    isDocChecklist = false;
  isVehicleInspCheck = false;

 

  view(data) {
    this.selectedResult = data;
    this.isView = true;
    this.getUploadChallans();
  }


  
  uploadedFileNames;
  getUploadChallans() {
    this.service.get('store/challan.php?type=getUploadedChallans&ch_no=' +this.selectedResult['ch_no'] +'&po_no=' +this.selectedResult['po_no']  +'&vendor_no=' +this.selectedResult['vendor_no']).subscribe((response) => {
        this.uploadedFileNames = response;
    });
  }

 

  downloadDocumentChecklist() {
    this.service.open('store/raw.php?type=downloadDocumentChecklist&challan_no=' +encodeURIComponent(this.selectedResult['challan_no'])) ;
  }
 



    
  viewFile(url1) {
    let url = this.service.url + '../../upload/challan/' + url1 +'?v=1';
    window.open(url, '_blank');
  }

 
  searchQuery = '';

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
