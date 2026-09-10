import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-spinprocess',
  templateUrl: './spinprocess.component.html',
  styleUrls: ['./spinprocess.component.css'],
})
export class SpinprocessComponent implements OnInit {
  isView = false;
  qa_checking = 'Yes';
  prod_checking = 'Yes';
  dispensing_completed = 'No';
  isDispensingCompleted = false;
  results;
  selectedResult = [];
  selectedIndex = -1;
  isStart = false;
  isShow = false;
  isViewDispensing = false;
  today = '';
  selectedMaterial = [];
  available_ars = [];
  containers = [];
  ars = [];
  balance_qty = 0;
  employees;
  gross_wt = 0;
  tare_wt = 0;
  lafs;
  gross_total = 0;
  tare_total = 0;
  net_total = 0;
  dispensing_room;
  employee;
  emp_id = '';
  balances;
  qcperson;
  officer;
  qaperson;
  ar_data = [];
  available_ars_data = [];
  selectedBalance = [];
  selectedMat = [];
  selectedLAF = [];
  start_time = '';
  end_time = '';
  start_date: Date;
  end_date: Date;
  pressure_reading = '';
  start_rlaf_time = '';
  rlaf_start;
  end_laf_time = '';
  isViewshow = false;
  selectedview = [];
  selectedContainer = [];
  operator;
  cleaning_from = '';
  cleaning_to = '';
  fifo_method = '';
  software_type: any;
  plant_type: any;
  desp_by: any;
  pm_hdr_id;
  constructor(private service: DataAccessService) {
    this.software_type = this.service.getPlantConfigFields('software_type');
    this.plant_type = this.service.getPlantConfigFields('plant_type');
  }

  ngOnInit(): void {
    this.getAcceptedRequests();
  }

  getAcceptedRequests() {
    this.service.get('store/dispensing.php?type=get_Dispensing_Requests_For_Inprocess_Activity_Formulation_pk&material_type=Packing Material&rpt_type=Request&material_code=' +this.selectedMaterial['m_code']).subscribe((response) => {
        this.results = response;
        if (this.results.length > 0) {
          if (this.selectedIndex !== -1) {
            this.selectedResult = this.results[this.selectedIndex];
            this.isView = true;
          } else {
            this.isStart = false;
            this.isView = false;
          }
        } else {
          this.isStart = false;
          this.isView = false;
        }
      });
  }






  view(index) {
     this.isShow = true;
    this.selectedIndex = index;
    this.selectedResult = this.results[index];
    // this.selectedMat = this.selectedResult['materials'];
    //  if(this.selectedResult['pm_disp_completed_by'] != " "){
    //   this.dispensing_completed = "YES";
    //   console.log("hii");
    //  }
    // let material = this.selectedResult['materials'];
            
    // let dispensingCompleted = 'Yes';
    // for (let i = 0; i < material.length; i++) {
    //   if (material[i]['disp_id'] != '0') {
    //     dispensingCompleted = 'Yes';
    //   }
    // }
    // this.dispensing_completed = dispensingCompleted;
   this.get_int_sift();
 
  }
  int_sifters
  get_int_sift() {
    this.service.get('production/product.php?type=get_savebmr_sift_pk_request_inprocess_activity&b_id='+this.selectedResult['b_id']+'&batch_plan_id='+this.selectedResult['batch_plan_id']+'&work_id='+this.selectedResult['id']).subscribe(response => {
      this.int_sifters = response;
    });
  }

  selected_sifter=[];
  add(index){
    this.selected_sifter=this.int_sifters[index]
    this.selectedMat = this.selected_sifter['materials'];
    if(this.selected_sifter['pm_disp_completed_by'] != " "){
     this.dispensing_completed = "YES";
     console.log("hii");
    }
   let material = this.selected_sifter['materials'];
           
   let dispensingCompleted = 'Yes';
   for (let i = 0; i < material.length; i++) {
     if (material[i]['disp_id'] != '0') {
       dispensingCompleted = 'Yes';
     }
   }
   this.dispensing_completed = dispensingCompleted;
  

    this.isView = true;
    this.isShow = false;  
  }






















  



  show(index) {
     
    this.balance_qty = 0;
    this.available_ars = [];
    this.available_ars_data = [];
    let last_container_id = 0;
    this.fifo_method = this.selected_sifter['fifo_method'];
    let material = this.selected_sifter['materials'];

    this.selectedMaterial = material[index];
    this.available_ars_data = this.selectedMaterial['available_ars'];
    let available  = this.selectedMaterial['available_ars'];
     
  //  this.balance_qty = +this.selectedMaterial['avbl_qty'];



    for (let i = 0; i < available.length; i++) {
      this.balance_qty = this.balance_qty + +available[i]['balance_qty'];
      console.log(available[i]['balance_qty']);
    }




let desp_qty = this.selectedMaterial['actual_qtyy'];
this.net_total = this.selectedMaterial['actual_qtyy'];

 

for (let i = 0; i < this.available_ars_data.length && desp_qty > 0; i++) {
   
  const currentArno = { ...this.available_ars_data[i] };

    if (currentArno.balance_qty === 0) continue;

    console.log(desp_qty +"<- desp   balance -> "+ currentArno.balance_qty);
    if (Number(desp_qty) >= Number(currentArno.balance_qty) ) {
      console.log(desp_qty +"<- desp   balance -> "+ currentArno.balance_qty);
      console.log("BYE");
      if(Number(currentArno.balance_qty) > 0){
        this.available_ars.push(currentArno);
        desp_qty -= currentArno.balance_qty;
        currentArno.despensedQty =  currentArno.qty;
        currentArno.qty = 0;

      }else{
        continue;
      }
    
    } else {
      if(Number(currentArno.balance_qty) > 0){
      this.available_ars.push(currentArno);
        currentArno.qty -= desp_qty;
        currentArno.despensedQty =   desp_qty;
        desp_qty = 0;
        console.log("HI");

      }else{
        continue;
      }
    }

    
}
     console.log(this.available_ars);
          
    this.getStoreEmployees();
    this.isStart = true;
    this.isView = false;
    this.isViewDispensing = false;
  }

  

  getStoreEmployees() {
    this.service.get('store/dispensing.php?type=getStoreEmployees').subscribe((response) => {
        this.employees = response;
      });
  }
   
  

  viewshow(index) {
    this.available_ars =[];
    let data:any;
    let material = this.selected_sifter['materials'];
    this.selectedMaterial = material[index];
    console.log(this.selectedContainer);
    // this.service.get('store/dispensing.php?type=get_dispensing_Activity_By_Id&pm_hdr_id=' +this.selectedMaterial['pm_hdr_id']).subscribe((response) => {
    //      data = response;
        
    //    // this.available_ars = JSON.parse(data['containers']);
    //    this.isStart = false;
    //     this.isView = false;
    //     this.isViewDispensing = true;
    //   });
    this.service.get('store/dispensing.php?type=get_dispensing_Activity_By_Id&pm_hdr_id=' + this.selectedMaterial['pm_hdr_id'])
    .subscribe((response) => {
      const data = response; // Assuming data is declared using let or const
      // Perform actions with the response data here
      this.available_ars = data['ars'];
      this.isStart = false;
      this.isView = false;
      this.isViewDispensing = true;
      console.log(data); // This will log the data after the HTTP request is complete
    });
  


// console.log(data);
//       const myFunction = () => {
//         this.available_ars = data['ars'];
        
        
//        };
    
    
//     setTimeout(myFunction, 1000);

  }


  updateDispensingStatus() {

    let temp = {};
    this.service.post('store/dispensing.php?type=pm_update_dispense_complete_by_store&id=' + this.selectedResult['id'] + '&status=' + status+'&sift_id='+this.selected_sifter['id'], null).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Dispensing Completed Updated Successfully!');
        this.isDispensingCompleted = true;
        this.isView = false;
        this.getAcceptedRequests();
      } else {
        alertify.error('Failed: An error occured, please try again!');
        window.location.reload();
      }
    });
  }

  saveDispensingForm_Weighing() {
    // if (this.balance_qty != 0) {
    //   alertify.error('Balance Qty:' + this.balance_qty);
    //   return;
    // }
    if (this.emp_id == '') {
      alertify.error('Plese Select Done By');
      return;
    }
    // if (this.tare_total == 0) {
    //   alertify.error('Plese Enter Tare Weight');
    //   return;
    // }
    let temp = {};
    temp['lot_id'] = this.selectedMaterial['id'];
    temp['work_order_id'] = this.selectedResult['id'];
    temp['product_code'] = this.selectedResult['product_code'];
    temp['prod_batch_code'] = this.selectedResult['batch_number'];
    temp['done_by'] = this.emp_id;
    temp['qa_checking'] = this.qa_checking;
    temp['prod_checking'] = this.prod_checking;
    temp['available_ars'] = this.available_ars;
    temp['containers'] = this.available_ars;
    //   temp['ars'] = this.ars;
    //temp['available_ars'] = this.available_ars_data;
   temp['gross_total'] = this.net_total;
   temp['net_total'] = this.net_total;
    temp['tare_total'] = this.tare_total;
    temp['material_type'] = this.selectedMaterial['material_type'];
    temp['material_subtype'] = this.selectedMaterial['material_subtype'];
    temp['material_code'] = this.selectedMaterial['material_code'];
    temp['unit'] = this.selectedMaterial['unit'];
    temp['ar_no'] = this.selectedMaterial['ar_no'];
    temp['batch_no'] = this.selectedMaterial['batch_number'];
    temp['dispensing_room'] = this.dispensing_room;
    temp['rlaf_start'] = this.rlaf_start;
    temp['pressure_reading'] = this.pressure_reading;
    temp['pm_hdr_id'] = this.selectedMaterial['pm_hdr_id'];
    temp['disp_qty'] = this.selectedMaterial['actual_qtyy'];
    temp['lot'] = this.selected_sifter['lot'];


    console.log(temp);

    this.service.post('store/dispensing.php?type=dispensing_save_saipro&pm_hdr_id=' +this.selectedMaterial['bpm_id']+'&upid='+this.selectedMaterial['upid'],JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Material Dispensing Completed!');
          this.isStart = false;
          this.available_ars = [];
          this.ars = [];
          this.containers = [];
          this.selectedMaterial = [];
          temp = [];
          this.getAcceptedRequests();
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });

  }

  saveDispensingForm_Counting() {
    // if (this.balance_qty != 0) {
    //   alertify.error('Balance Qty:' + this.balance_qty);
    //   return;
    // }
    if (this.emp_id == '') {
      alertify.error('Plese Select Done By');
      return;
    }
    // if (this.tare_total == 0) {
    //   alertify.error('Plese Enter Tare Weight');
    //   return;
    // }
    let temp = {};
    temp['lot_id'] = this.selectedMaterial['work_order_id'];
    temp['work_order_id'] = this.selectedResult['work_order_id'];
    temp['product_code'] = this.selectedResult['product_code'];
    temp['prod_batch_code'] = this.selectedResult['batch_no'];
    temp['done_by'] = this.emp_id;
    temp['qa_checking'] = this.qa_checking;
    temp['prod_checking'] = this.prod_checking;
    temp['containers'] = this.available_ars;
    //   temp['ars'] = this.ars;
    temp['available_ars'] = this.available_ars_data;
    temp['gross_total'] = this.gross_total;
    temp['net_total'] = this.net_total;
    temp['tare_total'] = this.tare_total;
    temp['material_type'] = this.selectedMaterial['material_type'];
    temp['material_subtype'] = this.selectedMaterial['material_subtype'];
    temp['material_code'] = this.selectedMaterial['material_code'];
    temp['unit'] = this.selectedMaterial['unit'];
    temp['ar_no'] = this.selectedMaterial['ar_no'];
    temp['batch_no'] = this.selectedMaterial['batch_no'];
    temp['dispensing_room'] = this.dispensing_room;
    temp['rlaf_start'] = this.rlaf_start;
    temp['pressure_reading'] = this.pressure_reading;
    this.service.post('store/dispensing.php?type=saveDispensingForm_saipro&dispensing_no=' +this.selectedMaterial['dispensing_no'],JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Material Dispensing Completed!');
          this.isStart = false;
          this.available_ars = [];
          this.ars = [];
          this.containers = [];
          this.selectedMaterial = [];
          temp = [];
          this.getAcceptedRequests();
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }

  print(data) {
    this.service.open(
      'store/dispensing.php?type=printLabel&material_code=' +
        data +
        '&id=' +
        this.selectedResult['id']
    );
  }
}
