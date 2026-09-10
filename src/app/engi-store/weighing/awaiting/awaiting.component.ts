import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {
  isView = false;
 
  constructor(private service: DataAccessService ) { }

  ngOnInit() {
    this.getPendingWeighingMaterials();
    this.getCheckPointData();
    this.getDeptBalances();
  }


    checkPointData;
    getCheckPointData(){
      this.service.get('master/checklist.php?type=getCheckPointByForm&module=Weighing&form=Weighing').subscribe(response => {
       this.checkPointData = response;
      }); 
    }

    balances
    getDeptBalances() {
      this.service.get('equipments.php?type=getStoreBalance').subscribe(response => {
        this.balances = response;
      });
    }


  results;
  material_type = 'Stationary';
  getPendingWeighingMaterials() {
    this.service.get('store/raw.php?type=getPendingWeighingMaterials&material_type='+this.material_type).subscribe(response => {
      this.results = response;
    });
  }
 
  GetSamplingBatches() {
    this.service.get('store/raw.php?type=GetSamplingBatches&challan_no='+this.selectedResult['challan_no']+'&material_code='+this.selectedResult['material_code']).subscribe(response => {
      this.selectedResult['batches'] = response;
    });
  }

  selectedResult = [];
  weighing_critiera = '100%'

  viewResult(data) {
      this.selectedResult = data;
      this.isView = true;
      this.material_typeIS = data['material_type'];
  }
  
    total_containers = 0;
    selectedBatch = [];
    numbers = [];
    isProceed = false;
    root_container = 0;
    material_typeIS = '';
    proceed(data) {

      console.log(this.material_typeIS);
 
      this.numbers = [];

      this.total_containers = 0;
      this.root_container = 0;
      this.selectedBatch = data
      this.total_containers = Number(data['total_containers']);
      let containerToBeWeigh = 0;

      if(this.weighing_critiera == '10%'){
          containerToBeWeigh = this.total_containers * 0.10;    // 10%
      }else if(this.weighing_critiera == '100%'){
          containerToBeWeigh = this.total_containers;  // 100%
      }else if(this.weighing_critiera == '√n +1'){
          containerToBeWeigh = Math.sqrt(this.total_containers) + 1; // √n + 1
      }

      this.root_container = Math.floor(containerToBeWeigh);

      for (let i = 0; i < this.root_container; i++) {
          let temp = {};
          temp['container_no'] = i+1;
          temp['lbgross_weight'] = 0;
          temp['lbtare_weight'] = 0;
          temp['lbnet_weight'] = 0; 
          temp['acgross_weight'] = 0;
          temp['actare_weight'] = 0;
          temp['acnet_weight'] = 0;
          this.numbers.push(temp);
      }
  
      this.isProceed = true;
    }

  saveWeighingsList(data) {
  
      if (!data.valid) {
        alertify.error('All Fields Are Mandatory');
        return;
      }
  
      let temp = {};
      temp['id'] = this.selectedBatch['id'];
      temp['root_container'] = this.root_container;
      temp['weight'] = this.numbers;
   
   
      this.service.post('store/raw.php?type=saveWeighingsList', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
          alertify.success(this.service.t('common.savedSuccess'));
          this.isProceed = false;
          this.GetSamplingBatches();
          this.selectedBatch = [];
          this.numbers = [];
        } else {
          alertify.error('Failed: An error occured, Please try again!');
        }
      });
    }

    saveWeighings(data) {

      if (!data.valid) {
        alertify.error('All Fields Are Mandatory');
        return;
      }


      let temp1 = {};
      temp1['weighing_procedure']= this.selectedResult['weighing_procedure'];
      temp1['balance']= this.selectedResult['balance'];
      temp1['weighing_critiera'] = this.weighing_critiera;

      let temp = {};
      temp['id'] = this.selectedResult['id'];
      temp['challan_id']= this.selectedResult['challan_id'];
      temp['temp1']= temp1;
      temp['balance']= this.selectedResult['balance'];
      temp['weighing_procedure'] = this.selectedResult['weighing_procedure'];
      temp['checklist'] = this.checkPointData;
 
      this.service.post('store/raw.php?type=saveWeighingGeneralMaterial', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
          alertify.success(this.service.t('common.savedSuccess'));
          this.isView = false;
          this.getPendingWeighingMaterials();
        } else {
          alertify.error('Failed: An error occured, Please try again!');
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
