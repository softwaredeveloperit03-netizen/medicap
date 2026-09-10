import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {Router} from '@angular/router';

declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  lanes;
  results;
 
  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit() {
    this.getSections();
    this.getPalette();
  }

  sections ;
  getSections() {
    this.service.get('store/location.php?type=getSections').subscribe(response => {
      this.sections = response;
    });
  }

 
  getPalette() {
    this.service.get('store/location.php?type=getPalette').subscribe(response => {
      this.results = response;
    });
  }
 
  savePalette(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    this.service.post('store/location.php?type=savePalette',JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        this.getPalette();
        alertify.success('Data saved successfully');
      } else {
        alertify.error('An error occred, please try again');
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
