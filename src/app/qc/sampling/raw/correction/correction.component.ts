import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-correction',
  templateUrl: './correction.component.html',
  styleUrls: ['./correction.component.css']
})
export class CorrectionComponent implements OnInit {
  isView = false;
 
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getRejectedSamplings();
  }
  

  results;
  material_type = 'Raw Material';
  getRejectedSamplings() {
    this.service.get('qc/sampling.php?type=getRejectedSamplings&material_type='+this.material_type).subscribe(response => {
      this.results = response;
    });
  }

  downloadRejectedSamplings() {
    this.service.open('qc/sampling.php?type=downloadRejectedSamplings&material_type=' + this.material_type);
  }

 

  selectedSampling = {};
  view(data) {
    this.selectedSampling = data;
    this.isView = true;
  }

  updateSampling(data,status) {

    if(!data.valid){
      alertify.error("Please Add Remark....");
      return;
    }

    let temp = data.value;
    temp['id'] = this.selectedSampling['id'];
    temp['status'] = status;
 
    this.service.post('qc/sampling.php?type=updateRejectedSampling', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.updatedSuccess'));
        this.isView = false;
        this.getRejectedSamplings();
      }else{
        alertify.error("some error Ocuured");
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
