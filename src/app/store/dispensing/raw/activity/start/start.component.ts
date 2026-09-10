import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-start',
  templateUrl: './start.component.html',
  styleUrls: ['./start.component.css'],
  providers: [DatePipe]
})
export class StartComponent implements OnInit {
  checkPointData: any[] = [];
  isView = false;
  loading = false;
  results: any[] = [];
  selectedResult: any = {};
  laf_pressure='';
  laf_equipments;
  rlaf_id='RLAF-02';
  equipments: any;
  checklist=[];
  balance_eqip_code;
  laf_eqip_code;
  line_clearance_type = '';
  line_clearance_remarks = '';
  po_status = '';
  checklist_batch = [   

    // { observation: 'Are all the Drums/Containers sampled one by one and ls are closed properly after sampling?', check: '' },
    // { observation: 'Whether “Sampled” labels with Container No. are affix on polybags/Bottles containing sample?', check: '' },
    // { observation: 'Whether “Sample for Analysis” labels with container No. are affix on polybags/Bottles containing sample?', check: '' },
    // { observation: 'Whether the used sampling tools are kept in Polybags and closed properly for transferring it to laboratory for cleaning?', check: '' },
    // { observation: 'Are all the safety precautions have been taken during Sampling?', check: '' },
    // { observation: 'Are all the containers taken for sampling kept back to designated places?', check: '' },
    // { observation: 'Whether tanker number mentioned in tanker receipt intimation is matched with tanker to be sampled?', check: '' },
    // { observation: 'Are the entire compartment sealed of tanker?', check: '' },
    // { observation: 'Is the tanker cleaning record available with tanker?', check: '' },
  ];
  checklist_product = [
    // { observation: 'All are the containers properly segregated?', check: '' },
    // { observation: 'All are the containers properly Labeled (Supplier/Mfg., Approved label, Quarantine Label?', check: '' },
    // { observation: 'Is the information given on Quarantine label as per GRN?', check: '' },
    // { observation: 'Are there any damage/leakage/outer seals intact of the Drums/Containers/Bags?', check: '' },
    // { observation: 'Is sampling area properly cleaned', check: '' },
    // { observation: 'Is there any extraneous material observed on surface of Polybags', check: '' },
    // { observation: 'Are all the Drums/Containers sampled one by one and ls are closed properly after sampling?', check: '' },
    
  ];
  constructor(private service:DataAccessService, private datePipe: DatePipe) {
   }
   plant_id;
  ngOnInit(): void {
    this.getAcceptedRequests();
    this.getLafEquipments();
    this.getEquipments();
    this.getCheckPointData();
    this.getCheckPointData();
    this.plant_id = this.service.getPlantConfigFields("plant_id");
  }


 
getCheckPointData(){
  this.service.get('store/raw.php?type=getCheckPointByForm&module=Dispensing&form=Start Activity').subscribe(response => {
    this.checkPointData = Array.isArray(response) ? response : [];
  });
}

 


// prevdate;
prevdate: any = {
  product_name: '',
  product_code: '',
  batch_number: '',
  date_of_cleaning: '',
  cleaned_by: '',
  checked_by: '',
  request_date: '',
  pleasure_diff_reading: '',
  laf_eqip_code: '',
  balance_eqip_code: ''
};
pleasure_diff_reading: ''
sp_prev_data(){
    this.service.get('store/dispensing.php?type=sp_prev_data').subscribe(response =>{
      if (response && Object.keys(response).length > 0) {
        // Format date if it exists
        let formattedDate = '';
        if (response['date_of_cleaning']) {
          try {
            formattedDate = this.datePipe.transform(response['date_of_cleaning'], 'dd-MM-yyyy') || response['date_of_cleaning'];
          } catch (e) {
            formattedDate = response['date_of_cleaning'];
          }
        }
        
        this.prevdate = {
          product_name: response['product_name'] || '',
          product_code: response['product_code'] || '',
          batch_number: response['batch_number'] || '',
          date_of_cleaning: formattedDate,
          cleaned_by: response['cleaned_by'] || '',
          checked_by: response['checked_by'] || '',
          request_date: response['request_date'] || '',
          pleasure_diff_reading: response['pleasure_diff_reading'] || '',
          laf_eqip_code: response['laf_eqip_code'] || '',
          balance_eqip_code: response['balance_eqip_code'] || ''
        };
        
        // Auto-populate form fields if data exists
        if (response['pleasure_diff_reading']) {
          this.pleasure_diff_reading = response['pleasure_diff_reading'];
        }
        if (response['laf_eqip_code']) {
          this.laf_eqip_code = response['laf_eqip_code'];
        }
        if (response['balance_eqip_code']) {
          this.balance_eqip_code = response['balance_eqip_code'];
        }
      } else {
        // Reset to defaults if no data
        this.prevdate = {
          product_name: '',
          product_code: '',
          batch_number: '',
          date_of_cleaning: '',
          cleaned_by: '',
          checked_by: '',
          request_date: '',
          pleasure_diff_reading: '',
          laf_eqip_code: '',
          balance_eqip_code: ''
        };
      }
    });
}
//   getCheckPointData(){
   
//     this.service.get('master/checklist.php?type=getCheckPointByForm&module=Grn&form=GRN Checking').subscribe(response => {
//      this.checkPointData = response;
    
//    });

//  }

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
    this.loading = true;
    this.service.get('store/dispensing.php?type=getDispensingAcceptedRequests&material_type=Raw Material').subscribe({
      next: (response) => {
        this.results = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      error: () => {
        this.results = [];
        this.loading = false;
      },
    });
  }

  lcStatusClass(status: string): string {
    const s = String(status || '').toLowerCase();
    if (!s) return 'disp-start__badge--neutral';
    if (s.includes('complete') || s.includes('done') || s.includes('clear') || s === 'ok' || s.includes('approved')) {
      return 'disp-start__badge--ok';
    }
    if (s.includes('reject') || s.includes('fail') || s.includes('hold')) {
      return 'disp-start__badge--warn';
    }
    if (s.includes('pending') || s.includes('await') || s.includes('open') || s.includes('start')) {
      return 'disp-start__badge--pending';
    }
    return 'disp-start__badge--neutral';
  }
  getChecklist(idx){
    this.checklist=[]
    if(idx ==0){
      this.checklist = this.checklist_batch;
    }else{
      this.checklist = this.checklist_product;
    }
  }

  view(row: any){
    this.selectedResult = row || {};
    this.isView = true;
    // Reset previous data first
    this.prevdate = {
      product_name: '',
      product_code: '',
      batch_number: '',
      date_of_cleaning: '',
      cleaned_by: '',
      checked_by: '',
      request_date: '',
      pleasure_diff_reading: '',
      laf_eqip_code: '',
      balance_eqip_code: ''
    };
    // Then fetch previous data
    this.sp_prev_data();
  }

  startRLAF(data){
    if(!data.valid){
      alertify.error('All feilds are required');
      return;
    }
    let temp2={}
    temp2['product_code']= this.prevdate['product_code'];
    temp2['pleasure_diff_reading']= this.prevdate['pleasure_diff_reading'];
    temp2['laf_eqip_code']= this.prevdate['laf_eqip_code'];
    temp2['balance_eqip_code']= this.prevdate['balance_eqip_code'];
    temp2['request_date']= this.prevdate['request_date'];
    temp2['batch_number']= this.prevdate['batch_number'];
    let temp= data.value;
    temp['bfr_no']= this.selectedResult['bfr_no'];
    temp['mfr_no']= this.selectedResult['mfr_no'];
    temp['material_no']= this.selectedResult['product_code'];
    temp['batch_no']= this.selectedResult['batch_number'];
    temp['work_order_id'] = this.selectedResult['id'];
    temp['checklist'] = this.checkPointData;
    temp['checkpoints'] = this.checklist;
    temp['prevdate'] = temp2;
    this.service.post('store/dispensing.php?type=save_rm_line_clearance_activity&product_code='+this.selectedResult['product_code'],JSON.stringify(temp)).subscribe(response=>{
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
