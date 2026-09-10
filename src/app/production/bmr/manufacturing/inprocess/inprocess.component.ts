import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-inprocess',
  templateUrl: './inprocess.component.html',
  styleUrls: ['./inprocess.component.css']
})
export class InprocessComponent implements OnInit {

  results;
  inprocess=[];
  isView = false;
  selectedResult=[];
  selectedIndex = -1;
  selectedStageIndex = -1; 
  isInprocess = false;
  equipments: any =[]; 
  selectedStage = [];
  selectedFile;

  isLoading = false;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getStartedBatches();
    this.getEquipments();
  }

  addStage(val){
    console.log(val.value);
    this.inprocess.push(val.value);
  }

  deleteStage(val){
    this.inprocess.splice(val, 1);
  }

  onFileChanged(event) {
    this.selectedFile = event.target.files[0];
    console.log(this.selectedFile);
  }

  getStartedBatches(){
    this.service.get('production/manufacturing2.php?type=getStartedBatches').subscribe(response=>{
      this.results = response;
      if (this.selectedIndex !== -1) {
        this.selectedResult = this.results[this.selectedIndex];
        this.isView = true;
      } else {
        this.isView = false;
      }
    });
  }

  getEquipments() {
    let plant_name='plant-9'
    this.service.get('equipments.php?type=getEquipmentbyPlant&plant_name='+plant_name).subscribe(response => {
      this.equipments = response;
    });
  }

  selectedEquipment = [];
  selectEquipment(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedEquipment = this.equipments[index];
    } else {
      this.selectedEquipment = [];
    }
  }

  view(index){
    this.selectedIndex = index;
    this.selectedResult=this.results[index];
    this.isView = true;
  }

  start(index) {
    let stages = this.selectedResult['stages'];
    this.selectedStage = stages[index];
    this.isInprocess = true;
  }

  sendsample(data){
    if(!data.valid){
      alertify.error('all Feilds are required');
      return;
    }
    if (this.isLoading == false) {
      this.isLoading = true;
    }
    let temp = data.value;
    temp['product_code'] = this.selectedResult['product_code'];
    temp['batch_no'] = this.selectedResult['batch_no'];
    temp['stage'] = this.selectedStage['stage'];

    this.service.post('production/technical.php?type=saveTISheet',JSON.stringify(data.value)).subscribe(response=>{
      this.isLoading = false;  
      if(response['status']=='success') {
        this.getStartedBatches();
        this.isInprocess = false;
        alertify.success('data save sucessfuly');
        data.resetForm();
      } else{
        alertify.error('some error Occured!');
      }
    });
  }

  sendSRP(material_code, received_qty){
    this.selectedResult['material_code'] = material_code;
    this.selectedResult['received_qty'] = received_qty;
    this.service.post('production/plant9/recovery.php?type=saveMLGenerated',JSON.stringify(this.selectedResult)).subscribe(response=>{
      if(response['status']=='success') {
        this.getStartedBatches();
        this.isInprocess = false;
        alertify.success('data save sucessfuly');
      }else{
       alertify.error('some error Occured!');
      }
    });
  }

  completeBatch() {
    let flag = 0;
    let stages = this.selectedResult['stages'];
    for (let i = 0; i < stages.length; i++) {
      let stage = stages[i];
      if (stage['ti_remark'] !== 'DONE' && (stage['stage'] !== 'Pure Chlorhexidine Base (Dry Powder)')) {
        flag = 1;
        break;
      }
    }
    if (flag == 0) {
      // this.service.post('production/plant9/manufacturing.php?type=completeBatch&id=' + this.selectedResult['id'], JSON.stringify(this.selectedResult)).subscribe(response => {
        this.service.post('production/plant9/recovery.php?type=completeBatch&id=' + this.selectedResult['id'],JSON.stringify(this.selectedResult)).subscribe(response=>{
  
      if (response['status'] == 'success') {
          alertify.success(response['msg']); 
          this.getStartedBatches();
          this.isView = false;
        } else {
          alertify.error(response['msg']);
        }
      });
    } else {
      alertify.error('All TR Sheet is not Completed');
    }
  }

}
