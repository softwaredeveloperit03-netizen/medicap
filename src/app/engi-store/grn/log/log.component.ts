import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers: [DatePipe],
})
export class LogComponent implements OnInit {
  isView = false;
 
  constructor(private service: DataAccessService ) { }

  grades;
  ngOnInit() {
    this.getGRNLogGeneralMaterial();
  }
 
  results;
  material_type = 'Stationary';
  getGRNLogGeneralMaterial() {
    this.service.get('store/raw.php?type=getGRNLogGeneralMaterial&material_type='+this.material_type).subscribe(response => {
      this.results = response;
    });
  }
 
  selectedResult = [];

  viewResult(data) {
      this.selectedResult = data;
      this.isView = true;
      this.getCheckPointData();
  }

  download() {
    this.service.open('store/raw.php?type=downloadGRN&challan_no=' +encodeURIComponent(this.selectedResult['challan_no']) +'&grn_no=' +encodeURIComponent(this.selectedResult['grn_no']));
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
