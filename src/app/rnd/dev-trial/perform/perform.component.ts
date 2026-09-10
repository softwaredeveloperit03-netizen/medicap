import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-perform',
  templateUrl: './perform.component.html',
  styleUrls: ['./perform.component.css']
})
export class PerformComponent implements OnInit {

  results;
  selectedResult=[];
  isView=false;
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingDevTrials();
  }


  getPendingDevTrials(){
    this.service.get('npd/npd.php?type=getPendingDevTrials').subscribe(response=>{
     this.results=response;
    });
  }

  view(data){
    this.selectedResult = data;
    this.isView = true;
  }


  updateTrial(status){

    let temp = {};
    temp['status'] = status;
    temp['id'] = this.selectedResult['id'];

    this.service.post('npd/npd.php?type=approvedDevTrial', JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success("Trial "+status+" Successfully.....");
        this.isView=false;
        this.getPendingDevTrials();
      }else{
        alertify.error('Failed:an error occured')
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
