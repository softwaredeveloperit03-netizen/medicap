import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-packing-container-master',
  templateUrl: './packing-container-master.component.html',
  styleUrls: ['./packing-container-master.component.css']
})
export class PackingCOntainerMasterComponent implements OnInit {

  constructor(public service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getCOntainerTypes();
    this.getCOntainerSubtypes();
    this.getMadeUpOfs();
  }


  containerSubTypes;
  getCOntainerSubtypes() {
    this.service.get('master/master.php?type=getCOntainerSubtypes').subscribe((response) => {
      this.containerSubTypes = response;
    });
  }

 

  containerTypes;
  getCOntainerTypes() {
    this.service.get('master/master.php?type=getCOntainerTypes').subscribe((response) => {
      this.containerTypes = response;
    });
  }

  madeUpOfs;
  getMadeUpOfs() {
    this.service.get('master/master.php?type=getMadeUpOfs').subscribe((response) => {
      this.madeUpOfs = response;
    });
  }
 
 
  container_type = '';
  isAddNewContainerType = false;
  addNewContainerSubtType = false;

  checkValue(){
    if(this.container_type == 'ADD NEW'){
      this.isAddNewContainerType = true;
      this.container_type = '';
    }
  }
 
 
  saveContainerType(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.service.post('master/master.php?type=saveContainerType', JSON.stringify(temp)).subscribe((response) => {
      if (response['status'] == 'success') {
        data.resetForm();
        this.getCOntainerTypes();
        this.isAddNewContainerType=false;
        alertify.success('Container Type Saved Successfully.....');
      } else {
        alertify.error(response['status']);
      }
    });
  }
 
  saveCOntainerSUbType(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.service.post('master/master.php?type=saveCOntainerSUbType', JSON.stringify(temp)).subscribe((response) => {
      if (response['status'] == 'success') {
        data.resetForm();
        this.getCOntainerSubtypes();
        this.addNewContainerSubtType=false;
        alertify.success('Container Subtype Saved Successfully.....');
      } else {
        alertify.error(response['status']);
      }
    });
  }

 

  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.containerSubTypes; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.containerSubTypes.filter((material) => {
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
