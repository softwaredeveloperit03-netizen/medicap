import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css'],
})
export class CheckingComponent implements OnInit {
  isView = false;
 
  constructor(private service: DataAccessService ) { }

  grades;
  ngOnInit() {
    this.getPendingCheckingGRN();
  }
 
  results;
  material_type = 'Raw Material';
  getPendingCheckingGRN() {
    this.service.get('store/raw.php?type=getPendingCheckingGRN&material_type='+this.material_type).subscribe(response => {
      this.results = response;
    });
  }
 
  selectedResult = [];

  viewResult(data) {
      this.selectedResult = data;
      this.isView = true;
      this.getCheckPointData();
  }

  viewCoafile(url) {
    url = this.service.url + '../../upload/coa/' + url;
    window.open(url, '_blank');
  }


  checkPointData;
 
   getCheckPointData() {
       this.service.get('master/checklist.php?type=getCheckPointByForm&module=Grn&form=GRN Checking').subscribe((response) => {
           this.checkPointData = response;
       });
   }

 
  selectedBatch = [];
  isProceed = false;
  proceed(data) {
    this.selectedBatch = data
    this.isProceed = true;
  }

   

  updateGrn(status) {
 
     let temp = {};
     temp['batches'] = this.selectedResult['batches'];
     temp['inward_no'] = this.selectedResult['inward_no'];
     temp['status'] = status;

     this.service.post('store/raw.php?type=updateGRN', JSON.stringify(temp)).subscribe(response => {
       if (response['status'] == 'success') {
         alertify.success('GRN Prepared successfully');
         this.isView = false;
         this.getPendingCheckingGRN();
       } else {
         alertify.error('Failed: An error occured, please try again!');
       }
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
