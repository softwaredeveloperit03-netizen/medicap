import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  
  isView = false;
  results;
  selectedResult = [];
  materials;
  balances;
  selectedBalance = [];
  isCountWeighing = false;
  isWeighing = false;
  isCount = false;
  countList= [];
  weighingList=[];
  result:number=0

 
  
 
  constructor(private service: DataAccessService) { }

  process = 0;
  total_received = 0;
  expected_weighing = 0;
  actual_weighing = 0;
  differance = 0;

  

calculateResult(){

      if(this.process > 0){
        let oneNosWeight = 0;
        oneNosWeight = this.process / 100;
        this.expected_weighing = oneNosWeight * this.total_received;
        const largerNumber = Math.max(this.expected_weighing, this.actual_weighing);
        const smallerNumber = Math.min(this.expected_weighing, this.actual_weighing);
        this.differance =   largerNumber - smallerNumber;

      }else{
        alertify.error('Please Enter Weight For 100 Nos');

      }
  
 }

  


 calculate(i: number) {
  const largerNumber = Math.max(this.weighingList[i].pack_size, this.weighingList[i].actual_count);
  const smallerNumber = Math.min(this.weighingList[i].pack_size, this.weighingList[i].actual_count);
  this.weighingList[i].difference = largerNumber - smallerNumber;

  console.log(this.weighingList);
}



 weighingList1 =[];

 packsizecalculation(value){
  let pack_size = 0;
  let received_qty =0;
  let criteria = 0;
  this.weighingList = [];
  criteria = value;
    pack_size = this.selectedResult['pack_size'];
    received_qty = this.selectedResult['received_qty'] ;
  let len = received_qty / pack_size;
  let cri = Math.round((criteria / 100) * len);
  for(let i = 0;i<cri;i++){
    this.weighingList[i]= { pack_no: '', pack_size: 0, actual_count: 0, difference: 0 };
  }



 }

 Clearlength(){
  this.weighingList = [];
 }

//  addCount(data,i){

//   if (!data.valid) {
//     alertify.error('All fields are required!');
//     return;
//   }

//   let temp = data.value;

//   console.log(temp);
//   this.weighingList[i].pack_no = temp[i]['pack_no'];
//   this.weighingList[i].pack_size = temp[i]['pack_size'];
//   this.weighingList[i].actual_count = temp[i]['actual_count'];
//   this.weighingList[i].differance = temp[i]['differance'];
//   this.weighingList[i].btn = 1;
//   data.resetForm();
// }

  ngOnInit(): void {
    this.getPendingWeighingMaterials();
    this.getLabours();
  }

  labors;
  getLabours() {
    this.service.get('hr/employee.php?type=getOperators').subscribe(response => {
      this.labors = response;
    });
  } 
  getPendingWeighingMaterials() {
    this.service.get('store/packing.php?type=getPendingWeighingMaterials').subscribe(response => {
      this.results = response;
    });
  }

  getDeptBalances() {
    this.service.get('balance.php?type=getDeptBalances').subscribe(response => {
      this.balances = response;
    });
  }

  viewResult(index) {
    this.selectedResult = this.results[index];
    this.getDeptBalances();
    this.isView = true;
  }

  selectResult(value){
    console.log(value);
    if (value == 'Counting') {
      this.isCount = true;
      this.isWeighing = false;
      this.isCountWeighing = false;

    } else if (value == 'Weighing') {
      //  this.getProducts();
      this.isCount = false;
      this.isWeighing = true;
      this.isCountWeighing = false;

    }else if (value == 'Counting Based on Weighing'){
      this.isCount = false;
      this.isWeighing = false;
      this.isCountWeighing = true;
    }
  }
test(){
  console.log(1);
}


  delCount(index) {
    this.weighingList.splice(index, 1);
  
  }
  addWeighing(data){
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.weighingList[this.weighingList.length] = temp;
    data.resetForm();
  }

  delWeighing(index) {
    this.weighingList.splice(index, 1);
  
  }

  selectBalance(index) {
    index = index - 1;
    this.selectedBalance = this.balances[index];
  }

   

  receiving_type;
  done_by='';
  emp_id='';


  data;
  isDIGI = false;
  
  openDigiSign(data){

    if (!data.valid) {
      alertify.error('All Fields Are Mandatory');
      return;
    }
 

    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
    this.data=data
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
        this.loginPassward ='';
        this.saveWeighings(this.data)
      }
      else{
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }
  

 

  saveWeighings(data) {
  
  let temp = this.selectedResult;
  
  temp['id'] = this.selectedResult['id'];
  temp['weighingList'] = this.weighingList;
  temp['receiving_type'] = this.receiving_type;
  temp['done_by'] = this.done_by;

  this.service.post('store/packing.php?type=saveWeighingMaterials', JSON.stringify(temp)).subscribe(response => {
    if (response['status'] == 'success') {
      alertify.success(this.service.t('common.savedSuccess'));
      this.getPendingWeighingMaterials();
      this.isView = false;
    } else {
      alertify.error('Failed: An error occured, Please try again!');
    }
  });
}
 

}
 

