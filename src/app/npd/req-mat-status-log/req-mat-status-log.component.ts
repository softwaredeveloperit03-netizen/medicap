import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-req-mat-status-log',
  templateUrl: './req-mat-status-log.component.html',
  styleUrls: ['./req-mat-status-log.component.css']
})
export class ReqMatStatusLogComponent implements OnInit {

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getRndReqMaterStatusLog();
  }

 
  results;
  getRndReqMaterStatusLog() {
    this.service.get('npd/npd.php?type=getRndReqMaterStatusLog&requestTo=NPD').subscribe(response => {
      this.results = response;
    });
  }

 
  isView = false;
  materialFrom = 'Indent';

  selectedMaterial = [];
  view(data){
    this.selectedMaterial = data;
    this.isView = true;
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
        if (key === 'entryOn') {
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
