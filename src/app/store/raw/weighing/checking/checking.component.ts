import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {
  isView = false;
 
  constructor(private service: DataAccessService ) { }

  ngOnInit() {
    this.getCheckingWeighingMaterials();
  }


  results;
  material_type = 'Raw Material';
  getCheckingWeighingMaterials() {
    this.service.get('store/raw.php?type=getCheckingWeighingMaterials&material_type='+this.material_type).subscribe(response => {
      this.results = response;
    });
  }
 
  selectedResult = [];

  viewResult(data) {
      this.selectedResult = data;
      this.isView = true;
  }
  
  selectedBatch = [];
  isProceed = false;
  proceed(data) {
    this.selectedBatch = data
    this.isProceed = true;
  }



  update(status) {

    let temp = {};
    temp['id'] = this.selectedResult['id'];
    temp['challan_id'] = this.selectedResult['challan_id'];
    temp['status'] = status;


    this.service.post('store/raw.php?type=updateWeighing', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Weighing Record Updated Successfully');
        this.isView = false;
        this.getCheckingWeighingMaterials();
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
