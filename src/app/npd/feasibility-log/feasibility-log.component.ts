import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-feasibility-log',
  templateUrl: './feasibility-log.component.html',
  styleUrls: ['./feasibility-log.component.css']
})
export class FeasibilityLogComponent implements OnInit {
  leadResults;
  selectedResult=[]  

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getFeasabilityFormLog();
  }
 
  getFeasabilityFormLog() {
    this.service.get('npd/npd.php?type=getFeasabilityFormLog').subscribe(response => {
      this.leadResults = response;
    });
  }
 
  
  isView = false;

  selectedEnquiry = [];
  view(data){
    this.selectedEnquiry = data;
    this.isView = true;
  }
 


  sentClientForLegal(data) {

 
    const temp = confirm('Do You Want To Send Client For Legal..');
    if (temp) {
      const temp = {};
      temp['id'] = data['id'];
      temp['client_code'] = data['client_code'];
      temp['enquiry_no'] = data['enquiry_no'];

      this.service.post('npd/npd.php?type=sentClientForLegal', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] = 'success') {
          alertify.success('Client Sent For Legal Successfully....');
          this.getFeasabilityFormLog();
        }else {
            alertify.error('Please try Again');
        }
      });

    }

  }


   searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.leadResults; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.leadResults.filter((material) => {
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


