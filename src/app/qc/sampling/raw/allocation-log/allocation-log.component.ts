import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;

@Component({
  selector: 'app-allocation-log',
  templateUrl: './allocation-log.component.html',
  styleUrls: ['./allocation-log.component.css']
})
export class AllocationLogComponent implements OnInit {


   constructor(private service: DataAccessService) { }
 
   ngOnInit() {
     this.getSamplingAllocationLog();
    }
   
    results;
   material_type = 'Raw Material';
   getSamplingAllocationLog() {
     this.service.get('qc/sampling/raw.php?type=getSamplingAllocationLog&material_type=' + this.material_type).subscribe(response => {
       this.results = response;
     });
   }

   downloadSamplingAllocationLog() {
     this.service.open('qc/sampling/raw.php?type=downloadSamplingAllocationLog&material_type=' + this.material_type);
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
 
  
 