import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-rejected',
  templateUrl: './rejected.component.html',
  styleUrls: ['./rejected.component.css']
})
export class RejectedComponent implements OnInit {

   isView = false;
   results;
   selectedSampling = [];

    constructor(private service: DataAccessService) { }
  
    ngOnInit() {
      this.gerRejectedGrnReceivingLog();
     }
    
    material_type = 'Raw Material';
    gerRejectedGrnReceivingLog() {
      this.service.get('qc/sampling/raw.php?type=gerRejectedGrnReceivingLog&material_type=' + this.material_type).subscribe(response => {
        this.results = response;
      });
    }
  
    viewResult(data) {
      this.selectedSampling = data;
      this.isView = true;
    }
  
  
    isCurrentDate(date: string | Date): boolean {
      const today = new Date();
      const givenDate = new Date(date);
      return today.toDateString() === givenDate.toDateString();
    }
 
  
  
    viewCoafile(url) {
      url = this.service.url + '../../upload/coa/' + url;
      window.open(url, '_blank');
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
  
   
  