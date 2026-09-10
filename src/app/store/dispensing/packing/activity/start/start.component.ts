import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-start',
  templateUrl: './start.component.html',
  styleUrls: ['./start.component.css']
})
export class StartComponent implements OnInit {

  
  isView = false;
  results;
  selectedResult=[];
  laf_pressure='';
  laf_equipments;
  rlaf_id='RLAF-02';
  equipments;
  checklist=[];
  checklist_batch = [   
    { observation: 'Are all the Drums/Containers sampled one by one and ls are closed properly after sampling?', check: '' },
    { observation: 'Whether “Sampled” labels with Container No. are affix on polybags/Bottles containing sample?', check: '' },
    { observation: 'Whether “Sample for Analysis” labels with container No. are affix on polybags/Bottles containing sample?', check: '' },
    { observation: 'Whether the used sampling tools are kept in Polybags and closed properly for transferring it to laboratory for cleaning?', check: '' },
    { observation: 'Are all the safety precautions have been taken during Sampling?', check: '' },
    { observation: 'Are all the containers taken for sampling kept back to designated places?', check: '' },
    { observation: 'Whether tanker number mentioned in tanker receipt intimation is matched with tanker to be sampled?', check: '' },
    { observation: 'Are the entire compartment sealed of tanker?', check: '' },
    { observation: 'Is the tanker cleaning record available with tanker?', check: '' },
  ];
  checklist_product = [
    { observation: 'All are the containers properly segregated?', check: '' },
    { observation: 'All are the containers properly Labeled (Supplier/Mfg., Approved label, Quarantine Label?', check: '' },
    { observation: 'Is the information given on Quarantine label as per GRN?', check: '' },
    { observation: 'Are there any damage/leakage/outer seals intact of the Drums/Containers/Bags?', check: '' },
    { observation: 'Is sampling area properly cleaned', check: '' },
    { observation: 'Is there any extraneous material observed on surface of Polybags', check: '' },
    { observation: 'Are all the Drums/Containers sampled one by one and ls are closed properly after sampling?', check: '' },
    
  ];
  constructor(private service:DataAccessService) {
   }

  ngOnInit(): void {
    this.getAcceptedRequests();
    this.getLafEquipments();
    this.getEquipments();
  }

  getLafEquipments() {
    this.service.get('qc/sampling.php?type=getLafEquipments').subscribe(response => {
      this.laf_equipments = response;
    });
  }
  getEquipments() {
    this.service.get('qc/sampling.php?type=getEquipments').subscribe(response => {
      this.equipments = response;
    });
  }
  getAcceptedRequests(){
    this.service.get('store/dispensing.php?type=getDispensingAcceptedRequests&material_type=Packing Material').subscribe(response => {
      this.results = response;
    });
  }
  getChecklist(idx){
    this.checklist=[]
    if(idx ==0){
      this.checklist = this.checklist_batch;
    }else{
      this.checklist = this.checklist_product;
    }
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  startRLAF(data){
    if(!data.valid){
      alertify.error('All feilds are required');
      return;
    }
    let temp= data.value;
    temp['bfr_no']= this.selectedResult['bfr_no'];
    temp['mfr_no']= this.selectedResult['mfr_no'];
    temp['material_no']= this.selectedResult['product_code'];
    temp['batch_no']= this.selectedResult['batch_number'];
    temp['work_order_id'] = this.selectedResult['id'];
    temp['checkpoints'] = this.checklist;
    this.service.post('store/dispensing.php?type=save_rm_line_clearance_activity',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('RLAF start successfuly');
        this.getAcceptedRequests();
        this.isView=false;
      }else{
        alertify.error('some error occured!');
      }
    });
  }
}
