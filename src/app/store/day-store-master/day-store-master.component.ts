import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-day-store-master',
  templateUrl: './day-store-master.component.html',
  styleUrls: ['./day-store-master.component.css']
})
export class DayStoreMasterComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getApprovedDayStores();
  }
 
  results;
  getApprovedDayStores() {
      this.service.get('production/master.php?type=getApprovedDayStores').subscribe(response => {
        this.results = response;
      });
  }

  saveDayStore(data) {

    if (!data.valid) {
      alertify.error('All fields are required!!!!!!!!');
      return;
    }
  
    let temp = data.value;
 
    this.service.post('production/master.php?type=saveDayStore', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Day/Plant Store Saved successfully!');
        this.getApprovedDayStores();
        this.isNew = false;
        data.reset();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
 

 
  isNew = false;
 
 
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
