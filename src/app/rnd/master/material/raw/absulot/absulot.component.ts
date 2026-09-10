import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-absulot',
  templateUrl: './absulot.component.html',
  styleUrls: ['./absulot.component.css']
})
export class AbsulotComponent implements OnInit {

 
 

  constructor(private service: DataAccessService) {  }

  ngOnInit(): void {
    this.getMaterialsLog();
  }


  results;

  
  material_type = 'Raw Material';

  getMaterialsLog() {
    this.service.get('master/rnd_material.php?type=getMaterialsByStatus&material_type='+this.material_type+'&status=Absolute').subscribe((response) => {
        this.results = response;
      });
  }

  selectedResult = [];
  isView = false;
 
   view(id) {
    const materialIndex = this.results.findIndex(
      (material) => material.id === id
    );
    if (materialIndex !== -1) {
      this.selectedResult = this.results[materialIndex];
    } else {
      console.error('Material not found for the given result id.');
    }
 
    this.isView = true;
  }

 
 
  viewMsds(url) {
    url = this.service.url + '../../upload/product/' + url;
    window.open(url, '_blank');
  }


  ApproveMaterial(){

    let temp ={};
 
    this.service.post('master/rnd_material.php?type=approveMaterial&id=' + this.selectedResult['id'] , JSON.stringify(temp)).subscribe((response) => {
      if (response['status'] == 'success') {
        alertify.success('Material Approved Successfully');
        this.isView = false;
        this.getMaterialsLog();
      } else {
        alertify.error(response['status']);
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
