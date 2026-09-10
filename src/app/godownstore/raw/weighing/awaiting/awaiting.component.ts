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
  checkPointData: any;
  no = 100;
  isView = false;
  results;
  weighing_critiera;
  // weighing_type;
  weighing_type = 'Inhouse-Weighing';
  selectedResult = [];
  iscalibration = false;
  balances;
  batches = [];
  selectedBalance = [];
  selectedReport = [];
  isProceed = false;
  selectedBatch = [];
  pack_size;
  rootnum;
  total_cont;
  final_container;
  numbers = [];
  numbers1 = [];
  numbers2 = [];
  balance;
  isTen = false;
  selected_batch_idx = 0;
  isHundred = false;
  isRoot = false;
  employess;
  no_of_containers=0;
  accept_status = 'Accept';
  weighings = [
    { id: 1, particular: 'From Label & Documents', check: '' },
    { id: 2, particular: 'Weigh Bridge', check: '' },
  ];
  bridges: any=[];
  weighRow = {  vechile_no:0,
                invoice_qty:1,
                v_grosswt:1,
                container_no:0,
                v_tarewt:0,
                net_wt:0,
                container_tarewt:0,
                short_extra:0 } ;
                check;
  
                plant_id:any;

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.plant_id = this.service.getPlantConfigFields('plant_id');

    this.getPendingWeighingMaterials();
    this.getEmpoloyees();
    this.getCheckPointData();
  }
   //----------------------For Pagination---------------------------------//

  currentPage: number = 1;
  pageSize: number = 10; // Default page size

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * 10 ;
  }
  
  onPageChange(page: number) {
    this.currentPage = page;
    console.log(this.currentPage);
  }
  
  onPageSizeChange(event: any) {
    this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
  }
  viewf(){
    this.isView=false;
    //  this.getLogs();
    this.currentPage=1;
    this.pageSize =10;
    
  }
  // ---------------------------------------------------------------------//

  getCheckPointData(){
   
    this.service.get('master/checklist.php?type=getCheckPointByForm&module=Weighing&form=Weighing').subscribe(response => {
     this.checkPointData = response;
    
   }); 

 }

  updateWeighBridgeStatus(status, id) {
    this.service.get('store/raw.php?type=updateWeighBridgeStatus&status=' + status + '&id=' + id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Status Updated Successfully');
        this.getPendingWeighingMaterials();
        this.router.navigate(['store/raw/weighing/awaiting']);
      } else {
        alertify.error('Please Try Again');
      }
    })
  }
  allApproved;
  getPendingWeighingMaterials() {
    this.service.get('store/raw.php?type=getPendingWeighingMaterials').subscribe(response => {
      this.results = response;
    });
    // Assuming 'selectedResult' is an array of batches
    this.allApproved = this.selectedResult['batches'];

  }

  getEmpoloyees() {
    this.service.get('common.php?type=getStoreEmployee').subscribe(response => {
      this.employess = response;
    });
  }

  getDeptBalances() {
    this.service.get('equipments.php?type=getStoreBalance').subscribe(response => {
      this.balances = response;
    });
  }

  category ='';
  containers =0;

  viewResult(index) {

    this.allApproved=[];
    this.selectedResult = this.results[index];
    // this.selectedReport = this.results[index];
    // this.weighing_type = this.selectedResult['weighing_procedure'];
   // this.weighing_type=='Inhouse-Weighing';
    this.category = this.selectedResult['category'];
    this.containers = this.selectedResult['containers'];


    if(localStorage.getItem('plant_id') == '158'){
      this.weighing_critiera = '√n +1'
      this.selectBatch(this.weighing_critiera);

    }else{

      if(this.category == 'Active'){
        this.weighing_critiera = '100%'
        this.selectBatch(this.weighing_critiera);
      }
      else if(this.category == 'In Active'){
        this.weighing_critiera = '√n +1'
        this.selectBatch(this.weighing_critiera);
      }
      if(this.containers <= 10){
        this.weighing_critiera = '100%'
        this.selectBatch(this.weighing_critiera);
      }

    }
 
    this.getDeptBalances();
    this.isView = true;
    this.allApproved = this.selectedResult['batches'].every(batch => batch['status'] === 'approve');
console.log(this.allApproved);

  }


  selectBatch(value) {
    if (value == '10%') {
      this.isTen = true;
      this.isHundred = false;
      this.isRoot = false;

    } else if (value == '100%') {

      this.isTen = false;
      this.isHundred = true;
      this.isRoot = false;

    } else if (value == '√n +1') {
      this.isTen = false;
      this.isHundred = false;
      this.isRoot = true;
    }
  }

  proceed(index) {

     this.numbers =[];
 
    this.selected_batch_idx = index;
    this.batches = this.selectedResult["batches"]
    this.selectedBatch = this.batches[index];
    this.pack_size = this.selectedBatch['pack_size'];
    this.no_of_containers= this.selectedBatch['total_containers']
    console.log('pack_size', this.pack_size);
    let contain = this.selectedBatch['total_containers'];
    this.rootnum = Math.sqrt(contain);

 

    if (this.weighing_critiera == '√n +1') {
      this.numbers =[];
      this.final_container = parseFloat(this.rootnum + 1).toFixed(2);
      this.total_cont = Math.round(this.final_container);
      for (let i = 0; i < this.total_cont; i++) {
        let number = this.total_cont[i];
        let temp = {};
        temp['container_no'];
        temp['action']='';
        temp['acgross_weight'] = 0;
        temp['lbnet_weight'] = 0;
        temp['actare_weight'] = 0;
        // temp['tolerance'] = this.no*this.selectedResult['tolerance']*1/number['lbnet_weight'];
        temp['acnet_weight'] = this.pack_size;
        this.numbers[i] = temp;
       }

    }
    if (this.isHundred) {
      this.numbers =[];


      this.total_cont = Math.round(contain);

 
      for (let i = 0; i < this.total_cont; i++) {
        let temp = {};
        temp['container_no'];
        temp['action']='';
        temp['acgross_weight'] = 0;
        temp['lbnet_weight'] = 0;
        temp['actare_weight'] = 0;
        // temp['tolerance'] = this.no*this.selectedResult['tolerance']*1/number['lbnet_weight'];
        temp['acnet_weight'] = this.pack_size;
        this.numbers[i] = temp;

      }


    }
    if (this.weighing_critiera == '10%') {
      this.numbers =[];
      this.total_cont = Math.ceil(contain * 10 / 100);

      for (let i = 0; i < this.total_cont; i++) {
        let temp = {};
        temp['container_no'];
        temp['action']='';
        temp['acgross_weight'] = 0;
        temp['lbnet_weight'] = 0;
        temp['actare_weight'] = 0;
        // temp['tolerance'] = this.no*this.selectedResult['tolerance']*1/number['lbnet_weight'];
        temp['acnet_weight'] = this.pack_size;
        this.numbers[i] = temp;
      }

    }





    this.isProceed = true;
    this.getPendingWeighingMaterials();
  }


  saveWeighingsList(data) {

    if (!data.valid) {
      alertify.error('All Fields Are Mandatory');
      return;
    }

    let temp = this.selectedResult;
    temp['id'] = this.selectedResult['id'];
    temp['batch_no'] = this.selectedBatch['batch_no'];
    temp['root_container'] = this.total_cont;
    temp['weight'] = this.numbers;
 
 
    this.service.post('store/raw.php?type=saveWeighingsList', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isProceed = false;
        this.getPendingWeighingMaterials();
        this.batches[this.selected_batch_idx]['status'] = 'approve';
      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });
  }
 
 
  saveWeighingBridge() {
    let temp = this.selectedResult;
    temp['id'] = this.selectedResult['id'];
    temp['weighing_bridge'] = this.bridges;
    this.service.post('store/raw.php?type=saveWeighingBridge', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isProceed = false;
        this.getPendingWeighingMaterials();

      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });
  }







  isNumber(value){
    if(isNaN(value)){
      alert('Please Enter Numeric Value');
    }

 
  }


  isNumber1(i,value){
    if(isNaN(value)){
      alert('Please Enter Numeric Value');
    }

 
    let tol = ((this.numbers[i].acgross_weight - this.numbers[i].lbtare_weight)-(this.numbers[i].lbnet_weight))*100 / (this.numbers[i].lbnet_weight)


    if(tol < -2 || tol > 2){
      this.numbers[i].action = "Reject";
    }else{
      this.numbers[i].action = "Accept";
    }


  }



  
  weighBridgeSlip: File;
 
  onFileChangedpsb(event) {
    this.weighBridgeSlip = event.target.files[0];
  }
 


  selectBalance(index) {
    index = index - 1;
    this.selectedBalance = this.balances[index];
    console.log(this.selectedBalance['status']);
  }

  performcalibration() {
    this.iscalibration = true;
  }

  saveWeighings(data) {
    
  
    let temp = {};
    temp['id'] = this.selectedResult['id'];
    temp['challan_id']= this.selectedResult['challan_id'];
    temp['balance'] = this.selectedBalance['equipment_code'];
     temp['weight'] = this.numbers;
    temp['weight'] = this.numbers1;
    temp['weight'] = this.numbers2;
    temp['checklist'] = this.checkPointData;
    this.service.post('store/raw.php?type=saveWeighingMaterials', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isView = false;
        this.getPendingWeighingMaterials();
      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });
  }

  updateWeighing(value, i) {
    this.weighings[i].check = value;
  }

  number(value) {
    if (isNaN(value)) {
      alertify.error('Number Only');
      return false;
    }
  }

  calculateAcceptReject(number, no) {
    let value = (number.acgross_weight - number.lbtare_weight) - (number.lbnet_weight) * no / (number.lbnet_weight);

    if (value > 1) {
      this.accept_status = 'Accept';
    } else {
      this.accept_status = 'Reject';
    }

  }


  // calculation2(){
   
  //   this.total_tare=Number(this.tare) + Number(this.total_number);
  //   this.total_gross=Number(this.invoice) + Number(this.total_tare);
  //   this.net_wt= Number(this.v_grosswt) - Number(this.v_tarewt) - Number(this.total_tare);
  // }

  addWeighBridge(){
    this.weighRow.net_wt = this.weighRow.v_grosswt-this.weighRow.v_tarewt-(this.weighRow.container_no*this.weighRow.container_tarewt) ;
     
    this.bridges.push(this.weighRow);

    this.weighRow = {  vechile_no:0,
      invoice_qty:1,
      v_grosswt:1,
      container_no:0,
      v_tarewt:0,
      net_wt:0,
      container_tarewt:0,
      short_extra:0 } ;

  }

  del(index) {
    this.bridges.splice(index, 1);
  }

}
