import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView: boolean = false;
  tests;
  selectedResult = [];
  

  constructor(private service:DataAccessService, private router: Router) { }
 
 
  ngOnInit(): void {
    this.getTests();
  }

  
 
  getTests() {
    this.service.get('master/test.php?type=getTestsapproval')
    .subscribe(response => {
      this.tests = response;
    });
  }


  viewTest(index){
    this.selectedResult = this.filteredMaterials[index];
    this.isView = true;
  }
  
  approveTest(){
    let temp={};
    this.service.post('master/test.php?type=approveTest&id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success(status);
        this.getTests();
        this.router.navigate(['/master/test']);
      } else {
        alertify.error('An error Occured, Please try again!');
      }
    });
  }


  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.tests; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.tests.filter((material) => {
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
