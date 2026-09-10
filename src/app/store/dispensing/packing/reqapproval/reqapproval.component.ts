import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-reqapproval',
  templateUrl: './reqapproval.component.html',
  styleUrls: ['./reqapproval.component.css']
})
export class ReqapprovalComponent implements OnInit { 
  isView = false;
  results;
  remarks = '';
  stocks;
  material_type = '';
  selectedResult = [];
  pack_sizes:any = [];
  packs:any = [];
  remark;
  plant_id;
    emp_id: string;
    isDIGI: boolean=false
    status: any;
    isbutton: boolean=true
  constructor(private service: DataAccessService) { }


  ngOnInit() {
    this.getDispensingActivities();
    // this.getMaterialOutDetails();
    // this.getadddisp_chek();
    this.plant_id = this.service.getPlantConfigFields("plant_id")
  }
  checks;
  getadddisp_chek(){
    this.service.get('bmr/process.php?type=GET_adddisp_chek').subscribe(response=>{
      this.checks = response;
     
    });
  }
  getDispensingActivities() {
    this.service.get('store/dispensing.php?type=get_PM_Dispensing_Requests_Approval&material_type=Packing Material').subscribe(response => {
      this.results = response;

    }); 
  }
  getDispensingchecks() {
    this.service.get('store/dispensing.php?type=getDispensingchecks&wo_id='+this.selectedResult['a_id']+'&sift_id='+this.selectedResult['id']).subscribe(response => {
      this.checks = response;

    });
  }


  // getMaterialOutDetails() {
  //   this.service.get('store/bincard.php?type=getMaterials&material_type=' + this.material_type).subscribe(response => {
  //     this.stocks = response;
  //   });
  // }
  isShow = false;
  pm_store_remarks;
  view(index) {
    this.pack_sizes = [];
    this.packs = [];
    this.selectedResult = this.results[index];

    console.log(this.selectedResult);
    // this.pack_sizes = this.selectedResult['pack_sizes'];
    // this.pm_store_remarks = this.selectedResult['pm_store_remarks'];
    // this.packs = this.pack_sizes['packing_material'];
        console.log(this.packs);
    this.isShow = true;
    // this.get_int_sift();
  }
  // selected_sifter=[];
//   add(index){
//     this.pack_sizes = [];
//     this.packs = [];
//     this.selected_sifter=this.int_sifters[index]
//     console.log(this.selected_sifter)
//     this.pack_sizes = this.selected_sifter['pack_sizes'];
//     this.pm_store_remarks = this.selected_sifter['pm_store_remarks'];
//     this.packs = this.pack_sizes[index]['packing_material'];
// this.getDispensingchecks();
//     this.isView = true;
//     this.isShow = false;  
//   }
  // int_sifters;
  // get_int_sift() {
  //   this.service.get('production/product.php?type=get_savebmr_sift_pk_request_approve&id='+this.selectedResult['id']+'&a_id='+this.selectedResult['a_id']).subscribe(response => {
  //     this.int_sifters = response;
  //   });
  // }
  // updatePhysicalStock(status, idx) {
  //   this.selectedResult['packing_material'][idx]['physical_stock'] = status;
  // }
  save(remark) {
    if (remark != 'Accept') {
      alertify.error('Please enter remarks');
      return;
    }
    let materials = [];
    for (var i = 0; i < this.pack_sizes.length; i++) {
      for (let x = 0; x < this.pack_sizes[i]['packing_material'].length; x++) {
        let mat = {
          "id": this.pack_sizes[i]['packing_material'][x]['id'],
          "physical_stock_status": this.pack_sizes[i]['packing_material'][x]['physical_stock']
        }
        // if (mat['physical_stock_status'] == '' || mat['physical_stock_status'] == null || mat['physical_stock_status'] == undefined) {
        //   alertify.error('Please select physical stock status');
        //   return;
        // }
        materials.push(mat);
      }
    }
    let obj = {
      "id": this.selectedResult['id'],
      "work_order_id": this.selectedResult['a_id'],
      "status": remark,
      "remarks": this.remarks,
      "materials": materials,
      // "checklist":this.checks
    }
    this.service.post('store/dispensing.php?type=saveRequestPM&id=' + this.selectedResult['a_id'], JSON.stringify(obj)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('data save successfuly');
        this.getDispensingActivities();
        this.isView = false;
      } else {
        alertify.error('some error occured!');
      }
    });
  }

  openDigiSign(value){
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
    this.status=value
  }

  loginPassward ='';
  digiSign(data){

    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
 
    this.service.get('login.php?type=checkDigiSIgn&mpin=' + this.loginPassward +'&emp_id=' + this.emp_id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Digi-Sign Verified successfully');
        this.isDIGI = false;
        this.isbutton = false;
        this.loginPassward ='';
        this.save_saipro(this.status)
      } else {
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }
  

  save_saipro(remark) {
   
    
    let obj = {
      "sift_id": this.selectedResult['id'],
      "work_order_id": this.selectedResult['a_id'],
      "checklist":this.checks,
      "status": remark,
    }
    this.service.post('store/dispensing.php?type=saveApproveRequestPM_saipro&id=' + this.selectedResult['a_id'], JSON.stringify(obj)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('data save successfuly');
        this.isbutton=true
        this.getDispensingActivities();    
        this.isShow = false;  
      } else {
        alertify.error('some error occured!');
      }
    });
  }
}
