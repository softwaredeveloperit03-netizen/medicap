import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-manufacturing',
  templateUrl: './manufacturing.component.html',
  styleUrls: ['./manufacturing.component.css']
})
export class ManufacturingComponent implements OnInit {

  results;
  isView = false;
  selectedResult=[];
  selectedIndex = -1;
  isShow=false;
  selectedStage=[];
  equipmentCode;
  equipment_type='';
  selectequip=[];
  units;
  selectedTechnicalInfo=[];
  isStageDone=false;
  test:any=0;

  selectedStageIndex = -1;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getStartedBatches();
    this.getUnits();
  }

  getStartedBatches(){
    this.service.get('production/lot/manufacturing.php?type=getStartedBatches').subscribe(response=>{
      this.results = response;
      if (this.selectedIndex !== -1) {
        this.selectedResult = this.results[this.selectedIndex];
        this.isView = true;
      } else {
        this.isView = false;
      }
    });
  }

  view(index){
    this.selectedIndex = index;
    this.selectedResult=this.results[index];
    this.isView = true;
  }

  viewStages(index){
    let stages=this.selectedResult['stages'];
    this.selectedStage=stages[index];
    this.isShow=true;
  }

  showStages(index){
    this.selectedStageIndex = index;
    let stages=this.selectedResult['stages'];
    this.selectedStage=stages[index];
    this.selectedTechnicalInfo=this.selectedStage['technical_info'];
    this.isStageDone=true;
  }

  getEquipmentTypes(){
    this.service.get('equipments.php?type=getProductionEquipments&equipment_type='+this.equipment_type).subscribe(response=>{
      this.equipmentCode=response;
    });
  }

  getcode(index){
    index=index-1;
    if(index != -1){
      this.selectequip=this.equipmentCode[index];
    }
  }

  start(stage, index) {
    this.service.get('production/lot/manufacturing.php?type=startStage&id=' + this.selectedResult['id'] + '&stage=' + stage + '&index=' + index).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Stage Started Succcessfully!');
        this.getStartedBatches();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  getUnits(){
    this.service.get('common.php?type=getUnits').subscribe(response=>{
      this.units=response;
    });
  }

  sendsample(data){
    if(!data.valid){
      alertify.error('all Feilds are required');
      return;
    }

    let temp=data.value;
    temp['company_unit']=this.selectedResult['company_unit'];
    temp['product_code']=this.selectedResult['product_code'];
    temp['stage']=this.selectedStage['stage'];
    temp['batch_no']=this.selectedResult['batch_no'];
    temp['product_name']=this.selectedResult['product_name'];

    this.service.post('production/technical.php?type=saveTISheet',JSON.stringify(temp)).subscribe(
     response=>{
       if(response['status']=='success'){
         alertify.success('data save sucessfuly');
         data.resetForm();
         this.isShow=false;
         this.getStartedBatches();
       }else{
        alertify.error('some error Occured!');
       }
     }
    );
  }

  // complete(stage, index) {
  //   index = index + 1;
  //   if (+index == +this.selectedResult['stages'].length) {
  //     this.selectedIndex = -1;
  //     index = 'last';
  //   }
  //   this.service.get('production/lot/manufacturing.php?type=completeStage&id=' + this.selectedResult['id'] + '&stage=' + stage + '&index=' + index).subscribe(response => {
  //     if (response['status'] == 'success') {
  //       alertify.success('Stage Completed Succcessfully!');
  //       this.getStartedBatches();
  //     } else {
  //       alertify.success('Failed: An error occured, please try again!');
  //     }
  //   });
  // }


  complete(stage) { 
    this.test=this.test+1;

    let index = +this.selectedStageIndex + 1;
    let index1 = "";
    if (+index == +this.selectedResult['stages'].length) {
      this.selectedIndex = -1;
      index1 = 'last';
    }

    this.service.get('production/lot/manufacturing.php?type=completeStage&id=' + this.selectedResult['id'] + '&stage=' + stage+ '&index=' + index1).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Stage Completed Succcessfully!');
        this.getStartedBatches();
        this.isStageDone=false;
        this.isView = false;
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
