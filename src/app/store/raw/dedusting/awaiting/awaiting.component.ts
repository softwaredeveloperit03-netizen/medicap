import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
declare let alertify;
@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css'],
  providers: [DatePipe]
})
export class AwaitingComponent implements OnInit {

  isView = false;
 
  constructor(private service: DataAccessService , private datePipe: DatePipe) { }

  ngOnInit() {
    this.getPendingDedustingMaterials();
    this.getStoresOperators();
    this.getSelectedEquipments();
  }

  results;
  material_type = 'Raw Material';
  getPendingDedustingMaterials() {
    this.service.get('store/raw.php?type=getPendingDedustingMaterials&material_type='+this.material_type).subscribe(response => {
      this.results = response;
    });
  }

  selectedReport = [];
  view(data) {
    this.selectedReport = data;
    this.isView = true;
  }

  labors;
  getStoresOperators() {
    this.service.get('store/raw.php?type=getStoresOperators').subscribe(response => {
      this.labors = response;
    });
  } 

  equipments;
  getSelectedEquipments() {
    this.service.get('equipments.php?type=getStoreVacuums').subscribe(response => {
      this.equipments = response;
    });
  }




  viewCoafile(url) {
    url = this.service.url + '../../upload/coa/' + url;
    window.open(url, '_blank');
  }




  saveMaterialDedusting(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['id'] = this.selectedReport['id'];
    this.service.post('store/raw.php?type=saveMaterialDedusting', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Dedusting record saved Successfully');
        data.resetForm();
        this.isView = false;
        this.getPendingDedustingMaterials();
      } else {
        alertify.error('An error occured');
      }
    });
  }


  area_cleaned_from = '';
  vfrom_time = '';
  equip_cleaned_from = '';

  area_cleaned_to = '';
  vto_time = '';
  equip_cleaned_to = '';


  setTime(type: string, From: string) {
    const now = new Date();
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const currentTime = `${hours}:${minutes}`;

    if (type === 'start') {
      if(From == 'Area'){ this.area_cleaned_from = currentTime; }
      else if(From == 'Vaccum'){ this.vfrom_time = currentTime; }
      else if(From == 'Clean'){ this.equip_cleaned_from = currentTime; }
    }
    if (type === 'stop') {
      if(From == 'Area'){this.area_cleaned_to = currentTime;}
      else if(From == 'Vaccum'){ this.vto_time = currentTime;}
      else if(From == 'Clean'){ this.equip_cleaned_to = currentTime; }
    }
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
